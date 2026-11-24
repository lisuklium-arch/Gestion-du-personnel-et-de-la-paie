<?php
require_once __DIR__ . '/JournalSoldeService.php';
require_once __DIR__ . '/JoursOuvrablesService.php';
require_once __DIR__ . '/JustificatifStorage.php';
require_once __DIR__ . '/CongesNotificationHelper.php';

class DemandeCongeService
{
    private PDO $pdo;
    private JournalSoldeService $journal;
    private JoursOuvrablesService $jours;
    private JustificatifStorage $files;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->journal = new JournalSoldeService($pdo);
        $this->jours = new JoursOuvrablesService($pdo);
        $this->files = new JustificatifStorage();
    }

    public function typesActifs(): array
    {
        return $this->pdo->query(
            'SELECT * FROM type_conge WHERE actif = 1 ORDER BY libelle'
        )->fetchAll();
    }

    public function soldesEmploye(int $employeId, int $annee): array
    {
        $st = $this->pdo->prepare(
            'SELECT s.*, t.libelle, t.code, t.decompte_solde
             FROM solde_conge s
             JOIN type_conge t ON t.id = s.type_conge_id
             WHERE s.employe_id = ? AND s.annee = ?
             ORDER BY t.libelle'
        );
        $st->execute([$employeId, $annee]);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['jours_disponibles'] = $this->journal->disponible($r);
        }
        return $rows;
    }

    public function profilEmploye(int $employeId): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT e.*, d.nom AS departement, p.intitule AS poste,
                    chef.nom AS chef_nom, chef.prenom AS chef_prenom,
                    u.role AS role_utilisateur
             FROM employe e
             JOIN departement d ON d.id = e.departement_id
             JOIN poste p ON p.id = e.poste_id
             JOIN utilisateur u ON u.id = e.utilisateur_id
             LEFT JOIN employe chef ON chef.id = d.chef_id
             WHERE e.id = ?'
        );
        $st->execute([$employeId]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function colleguesDepartement(int $employeId): array
    {
        $st = $this->pdo->prepare(
            'SELECT e2.id, e2.matricule, e2.nom, e2.prenom
             FROM employe e
             JOIN employe e2 ON e2.departement_id = e.departement_id AND e2.id <> e.id
             WHERE e.id = ? AND e2.statut = \'actif\'
             ORDER BY e2.nom, e2.prenom'
        );
        $st->execute([$employeId]);
        return $st->fetchAll();
    }

    public function demandesEmploye(int $employeId): array
    {
        $st = $this->pdo->prepare(
            'SELECT d.*, t.libelle AS type_libelle
             FROM demande_conge d
             JOIN type_conge t ON t.id = d.type_conge_id
             WHERE d.employe_id = ?
             ORDER BY d.date_soumission DESC'
        );
        $st->execute([$employeId]);
        return $st->fetchAll();
    }

    public function validationsDe(int $demandeId): array
    {
        $st = $this->pdo->prepare(
            'SELECT v.*, u.email,
                    e.nom AS decideur_nom, e.prenom AS decideur_prenom
             FROM validation_conge v
             JOIN utilisateur u ON u.id = v.decideur_id
             LEFT JOIN employe e ON e.utilisateur_id = u.id
             WHERE v.demande_conge_id = ?
             ORDER BY v.date_decision'
        );
        $st->execute([$demandeId]);
        return $st->fetchAll();
    }

    public function trouver(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT d.*, t.libelle AS type_libelle, t.decompte_solde, t.justificatif_requis,
                    e.nom, e.prenom, e.matricule, e.departement_id, e.utilisateur_id AS employe_utilisateur_id,
                    u.role AS role_demandeur, dep.nom AS departement, po.intitule AS poste
             FROM demande_conge d
             JOIN type_conge t ON t.id = d.type_conge_id
             JOIN employe e ON e.id = d.employe_id
             JOIN utilisateur u ON u.id = e.utilisateur_id
             JOIN departement dep ON dep.id = e.departement_id
             JOIN poste po ON po.id = e.poste_id
             WHERE d.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function apercuCalcul(array $in): array
    {
        $debut = (string) ($in['date_debut'] ?? '');
        $fin = (string) ($in['date_fin'] ?? '');
        $pd = (string) ($in['periode_debut'] ?? 'MATIN');
        $pf = (string) ($in['periode_fin'] ?? 'APRES_MIDI');
        return $this->jours->detail($debut, $fin, $pd, $pf);
    }

    public function deposer(int $employeId, int $utilisateurId, array $in, ?array $upload): int
    {
        $typeId = (int) ($in['type_conge_id'] ?? 0);
        $debut = (string) ($in['date_debut'] ?? '');
        $fin = (string) ($in['date_fin'] ?? '');
        $pd = (string) ($in['periode_debut'] ?? 'MATIN');
        $pf = (string) ($in['periode_fin'] ?? 'APRES_MIDI');
        $motif = trim((string) ($in['motif'] ?? ''));
        $contact = trim((string) ($in['contact_urgence'] ?? ''));
        $remplacantId = (int) ($in['remplacant_id'] ?? 0) ?: null;

        $type = $this->pdo->prepare('SELECT * FROM type_conge WHERE id = ? AND actif = 1');
        $type->execute([$typeId]);
        $t = $type->fetch();
        if (!$t) {
            throw new RuntimeException('Type de congé invalide ou inactif.');
        }

        $calc = $this->jours->detail($debut, $fin, $pd, $pf);
        $nb = $calc['jours_decomptes'];
        if ($nb <= 0) {
            throw new RuntimeException('Aucun jour ouvrable sur la période choisie.');
        }

        $this->controlerPolitiques($employeId, $debut, $fin, $nb, $t);
        $this->controlerChevauchement($employeId, $debut, $fin, null);

        if ((int) $t['justificatif_requis'] === 1) {
            $err = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
            if ($err === UPLOAD_ERR_NO_FILE) {
                throw new RuntimeException('Justificatif obligatoire pour ce type de congé.');
            }
        }

        $annee = (int) substr($debut, 0, 4);
        $this->pdo->beginTransaction();
        try {
            $solde = null;
            $dispoAvant = null;
            if ((int) $t['decompte_solde'] === 1) {
                $solde = $this->journal->soldeOuvert($employeId, $typeId, $annee);
                if ($solde === null) {
                    throw new RuntimeException('Aucun solde ouvert pour cette année et ce type.');
                }
                $dispoAvant = $this->journal->disponible($solde);
                if ($dispoAvant + 1e-9 < $nb) {
                    throw new RuntimeException(
                        'Solde insuffisant : ' . $dispoAvant . ' j. disponibles, ' . $nb . ' j. demandés.'
                    );
                }
            }

            $role = $this->roleEmploye($employeId);
            // RH : uniquement le DG décide (pas d'auto-validation RH)
            if ($role === 'rh') {
                $statutInitial = 'en_attente_dg';
            } elseif ($this->aChef($employeId)) {
                $statutInitial = 'en_attente_chef';
            } else {
                // Pas de chef renseigné sur le département → passage direct RH
                $statutInitial = 'en_attente_rh';
            }

            // Colonnes enrichies (tolère migration non encore jouée)
            $cols = 'employe_id, type_conge_id, date_debut, date_fin, nb_jours, motif, statut';
            $vals = '?, ?, ?, ?, ?, ?, ?';
            $params = [$employeId, $typeId, $debut, $fin, $nb, $motif !== '' ? $motif : null, $statutInitial];
            if ($this->colonneExiste('demande_conge', 'periode_debut')) {
                $cols .= ', periode_debut, periode_fin, contact_urgence, remplacant_id, date_reprise';
                $vals .= ', ?, ?, ?, ?, ?';
                $params[] = $pd;
                $params[] = $pf;
                $params[] = $contact !== '' ? $contact : null;
                $params[] = $remplacantId;
                $params[] = $calc['date_reprise'];
            }
            if ($this->colonneExiste('demande_conge', 'heure_debut')) {
                $hd = trim((string) ($in['heure_debut'] ?? ''));
                $hf = trim((string) ($in['heure_fin'] ?? ''));
                $cols .= ', heure_debut, heure_fin';
                $vals .= ', ?, ?';
                $params[] = $hd !== '' ? $hd : null;
                $params[] = $hf !== '' ? $hf : null;
            }
            $st = $this->pdo->prepare("INSERT INTO demande_conge ($cols) VALUES ($vals)");
            $st->execute($params);
            $id = (int) $this->pdo->lastInsertId();

            if ($upload && ($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $chemin = $this->files->enregistrer($upload, JustificatifStorage::CONGES, $id);
                if ($chemin) {
                    $this->pdo->prepare('UPDATE demande_conge SET justificatif_chemin = ? WHERE id = ?')
                        ->execute([$chemin, $id]);
                }
            }

            if ($solde !== null) {
                $this->journal->poster(
                    (int) $solde['id'], $id, $utilisateurId, 'RESERVATION',
                    0, $nb, 0, 'RES:' . $id, 'Dépôt demande #' . $id
                );
            }
            $this->pdo->commit();
            try {
                $demNotif = $this->trouver($id);
                if ($demNotif) {
                    (new CongesNotificationHelper($this->pdo))->apresDepot($demNotif, $employeId);
                }
            } catch (Throwable $ignore) {
            }
            return $id;
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function avisChef(int $demandeId, int $decideurId, string $decision, ?string $commentaire): void
    {
        $dem = $this->trouver($demandeId);
        if ($dem === null) {
            throw new RuntimeException('Demande introuvable.');
        }
        if (!$this->estChefDuDepartement((int) $dem['employe_id'], $decideurId)) {
            throw new RuntimeException('Seul le chef du département de l’employé peut donner un avis sur cette demande.');
        }
        $this->deciderEtape($demandeId, $decideurId, 'chef', $decision, $commentaire, 'en_attente_chef', 'en_attente_rh');
    }

    public function decisionRh(int $demandeId, int $decideurId, string $decision, ?string $commentaire): void
    {
        $dem = $this->trouver($demandeId);
        if ($dem === null) {
            throw new RuntimeException('Demande introuvable.');
        }
        if (($dem['role_demandeur'] ?? '') === 'rh') {
            throw new RuntimeException('Les congés d’un responsable RH sont validés uniquement par le directeur général.');
        }
        $this->deciderEtape($demandeId, $decideurId, 'rh', $decision, $commentaire, 'en_attente_rh', null, true);
    }

    public function decisionDg(int $demandeId, int $decideurId, string $decision, ?string $commentaire): void
    {
        $this->deciderEtape($demandeId, $decideurId, 'dg', $decision, $commentaire, 'en_attente_dg', null, true);
    }

    public function annuler(int $demandeId, int $employeId, int $utilisateurId): void
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('SELECT * FROM demande_conge WHERE id = ? AND employe_id = ? FOR UPDATE');
            $st->execute([$demandeId, $employeId]);
            $d = $st->fetch();
            if (!$d) {
                throw new RuntimeException('Demande introuvable.');
            }
            if (!in_array($d['statut'], ['en_attente_chef', 'en_attente_rh', 'en_attente_dg'], true)) {
                throw new RuntimeException('Cette demande ne peut plus être annulée.');
            }
            $this->pdo->prepare("UPDATE demande_conge SET statut = 'annulee' WHERE id = ?")->execute([$demandeId]);
            $this->libererSiBesoin($d, $utilisateurId, 'LIBERATION', 'ANN:' . $demandeId, 'Annulation demande #' . $demandeId);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    public function fileChef(int $chefUtilisateurId): array
    {
        $st = $this->pdo->prepare(
            "SELECT d.*, t.libelle AS type_libelle, e.nom, e.prenom, e.matricule
             FROM demande_conge d
             JOIN type_conge t ON t.id = d.type_conge_id
             JOIN employe e ON e.id = d.employe_id
             JOIN departement dep ON dep.id = e.departement_id
             JOIN employe chef ON chef.id = dep.chef_id
             WHERE chef.utilisateur_id = ?
             ORDER BY FIELD(d.statut,'en_attente_chef') DESC, d.date_soumission DESC"
        );
        $st->execute([$chefUtilisateurId]);
        return $st->fetchAll();
    }

    public function fileRh(): array
    {
        return $this->pdo->query(
            "SELECT d.*, t.libelle AS type_libelle, e.nom, e.prenom, e.matricule, u.role AS role_demandeur
             FROM demande_conge d
             JOIN type_conge t ON t.id = d.type_conge_id
             JOIN employe e ON e.id = d.employe_id
             JOIN utilisateur u ON u.id = e.utilisateur_id
             WHERE u.role <> 'rh'
               AND d.statut IN ('en_attente_rh','en_attente_chef','approuvee','refusee')
             ORDER BY FIELD(d.statut,'en_attente_rh') DESC, d.date_soumission DESC"
        )->fetchAll();
    }

    public function fileDg(): array
    {
        return $this->pdo->query(
            "SELECT d.*, t.libelle AS type_libelle, e.nom, e.prenom, e.matricule
             FROM demande_conge d
             JOIN type_conge t ON t.id = d.type_conge_id
             JOIN employe e ON e.id = d.employe_id
             JOIN utilisateur u ON u.id = e.utilisateur_id
             WHERE u.role = 'rh' AND d.statut IN ('en_attente_dg','approuvee','refusee')
             ORDER BY FIELD(d.statut,'en_attente_dg') DESC, d.date_soumission DESC"
        )->fetchAll();
    }

    public function parametre(string $cle, string $defaut = '0'): string
    {
        try {
            $st = $this->pdo->prepare('SELECT valeur FROM parametre_systeme WHERE cle = ?');
            $st->execute([$cle]);
            $v = $st->fetchColumn();
            return $v === false ? $defaut : (string) $v;
        } catch (Throwable $e) {
            return $defaut;
        }
    }

    private function controlerPolitiques(int $employeId, string $debut, string $fin, float $nb, array $type): void
    {
        if ($debut > $fin) {
            throw new RuntimeException('La date de fin doit être postérieure ou égale à la date de début.');
        }
        // Date de début : jamais antérieure à aujourd’hui
        if ($debut < date('Y-m-d')) {
            throw new RuntimeException('La date de début ne peut pas être antérieure à aujourd’hui.');
        }
        if ($fin < $debut) {
            throw new RuntimeException('La date de fin doit être postérieure ou égale à la date de début.');
        }
        $preavis = (int) $this->parametre('conge_preavis_min_jours', '0');
        if ($preavis > 0) {
            $minDebut = (new DateTimeImmutable('today'))->modify('+' . $preavis . ' days')->format('Y-m-d');
            if ($debut < $minDebut) {
                throw new RuntimeException("Préavis insuffisant : déposez au moins $preavis jour(s) avant le début.");
            }
        }
        $maxPol = (float) $this->parametre('conge_duree_max_jours', '30');
        if ($maxPol > 0 && $nb > $maxPol + 1e-9) {
            throw new RuntimeException("Durée maximale autorisée : $maxPol jour(s) ouvrés.");
        }
        if (isset($type['duree_max_jours']) && $type['duree_max_jours'] !== null && $nb > (float) $type['duree_max_jours'] + 1e-9) {
            throw new RuntimeException('Durée supérieure au maximum autorisé pour ce type de congé.');
        }
        if (isset($type['duree_min_jours']) && $nb + 1e-9 < (float) $type['duree_min_jours']) {
            throw new RuntimeException('Durée inférieure au minimum autorisé pour ce type de congé.');
        }
        $maxOpen = (int) $this->parametre('conge_max_demandes_ouvertes', '3');
        if ($maxOpen > 0) {
            $st = $this->pdo->prepare(
                "SELECT COUNT(*) FROM demande_conge
                 WHERE employe_id = ? AND statut IN ('en_attente_chef','en_attente_rh','en_attente_dg')"
            );
            $st->execute([$employeId]);
            if ((int) $st->fetchColumn() >= $maxOpen) {
                throw new RuntimeException("Vous avez déjà $maxOpen demande(s) en cours.");
            }
        }
    }

    private function controlerChevauchement(int $employeId, string $debut, string $fin, ?int $saufId): void
    {
        $sql = "SELECT id FROM demande_conge
                WHERE employe_id = ?
                  AND statut IN ('en_attente_chef','en_attente_rh','en_attente_dg','approuvee')
                  AND date_debut <= ? AND date_fin >= ?";
        $params = [$employeId, $fin, $debut];
        if ($saufId) {
            $sql .= ' AND id <> ?';
            $params[] = $saufId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        if ($st->fetch()) {
            throw new RuntimeException('Cette période chevauche une autre demande déjà en cours ou approuvée.');
        }
        // Absence déjà marquée
        try {
            $st = $this->pdo->prepare(
                'SELECT id FROM absence WHERE employe_id = ? AND date_absence BETWEEN ? AND ? LIMIT 1'
            );
            $st->execute([$employeId, $debut, $fin]);
            if ($st->fetch()) {
                throw new RuntimeException('Une absence est déjà enregistrée sur une partie de cette période.');
            }
        } catch (RuntimeException $e) {
            throw $e;
        } catch (Throwable $e) {
            // ignore si table absente
        }
    }

    private function deciderEtape(
        int $demandeId,
        int $decideurId,
        string $etape,
        string $decision,
        ?string $commentaire,
        string $statutAttendu,
        ?string $statutSuivant,
        bool $finaliser = false
    ): void {
        $mapDecision = [
            'favorable' => 'favorable',
            'defavorable' => 'defavorable',
            'approuve' => 'approuve',
            'refuse' => 'refuse',
        ];
        if (!isset($mapDecision[$decision])) {
            throw new RuntimeException('Décision invalide.');
        }
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('SELECT * FROM demande_conge WHERE id = ? FOR UPDATE');
            $st->execute([$demandeId]);
            $d = $st->fetch();
            if (!$d || $d['statut'] !== $statutAttendu) {
                throw new RuntimeException('Cette demande n\'est plus en attente à cette étape.');
            }
            // Le demandeur ne valide pas lui-même
            $emp = $this->pdo->prepare('SELECT utilisateur_id FROM employe WHERE id = ?');
            $emp->execute([(int) $d['employe_id']]);
            if ((int) $emp->fetchColumn() === $decideurId) {
                throw new RuntimeException('Vous ne pouvez pas valider votre propre demande.');
            }
            $this->pdo->prepare(
                'INSERT INTO validation_conge (demande_conge_id, etape, decision, commentaire, decideur_id)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE decision = VALUES(decision), commentaire = VALUES(commentaire),
                   decideur_id = VALUES(decideur_id), date_decision = CURRENT_TIMESTAMP'
            )->execute([$demandeId, $etape, $mapDecision[$decision], $commentaire, $decideurId]);

            if ($etape === 'chef') {
                $this->pdo->prepare("UPDATE demande_conge SET statut = 'en_attente_rh' WHERE id = ?")
                    ->execute([$demandeId]);
            } elseif ($finaliser) {
                if (in_array($decision, ['approuve', 'favorable'], true)) {
                    $this->pdo->prepare("UPDATE demande_conge SET statut = 'approuvee' WHERE id = ?")
                        ->execute([$demandeId]);
                    $this->approuverSolde($d, $decideurId);
                } else {
                    $this->pdo->prepare("UPDATE demande_conge SET statut = 'refusee' WHERE id = ?")
                        ->execute([$demandeId]);
                    $this->libererSiBesoin($d, $decideurId, 'LIBERATION', 'REF:' . $demandeId, 'Refus demande #' . $demandeId);
                }
            } elseif ($statutSuivant !== null) {
                $this->pdo->prepare('UPDATE demande_conge SET statut = ? WHERE id = ?')
                    ->execute([$statutSuivant, $demandeId]);
            }
            $this->pdo->commit();
            try {
                $dem2 = $this->trouver($demandeId);
                if ($dem2) {
                    $uidEmp = (int) ($dem2['employe_utilisateur_id'] ?? 0);
                    if ($uidEmp <= 0) {
                        $q = $this->pdo->prepare('SELECT utilisateur_id FROM employe WHERE id = ?');
                        $q->execute([(int) $dem2['employe_id']]);
                        $uidEmp = (int) $q->fetchColumn();
                    }
                    if ($uidEmp > 0) {
                        (new CongesNotificationHelper($this->pdo))->apresDecision(
                            $demandeId,
                            $uidEmp,
                            (string) $dem2['statut'],
                            'Statut : ' . $dem2['statut']
                        );
                    }
                }
            } catch (Throwable $ignore) {
            }
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function approuverSolde(array $d, int $utilisateurId): void
    {
        $type = $this->pdo->prepare('SELECT decompte_solde FROM type_conge WHERE id = ?');
        $type->execute([(int) $d['type_conge_id']]);
        $t = $type->fetch();
        if (!$t || (int) $t['decompte_solde'] !== 1) {
            return;
        }
        $annee = (int) substr((string) $d['date_debut'], 0, 4);
        $solde = $this->journal->soldeOuvert((int) $d['employe_id'], (int) $d['type_conge_id'], $annee);
        if ($solde === null) {
            return;
        }
        $nb = (float) $d['nb_jours'];
        $this->journal->poster(
            (int) $solde['id'], (int) $d['id'], $utilisateurId, 'APPROBATION',
            0, -$nb, $nb, 'APPR:' . $d['id'], 'Approbation demande #' . $d['id']
        );
    }

    private function libererSiBesoin(array $d, int $utilisateurId, string $type, string $ref, string $motif): void
    {
        $typeRow = $this->pdo->prepare('SELECT decompte_solde FROM type_conge WHERE id = ?');
        $typeRow->execute([(int) $d['type_conge_id']]);
        $t = $typeRow->fetch();
        if (!$t || (int) $t['decompte_solde'] !== 1) {
            return;
        }
        $annee = (int) substr((string) $d['date_debut'], 0, 4);
        $solde = $this->journal->soldeOuvert((int) $d['employe_id'], (int) $d['type_conge_id'], $annee);
        if ($solde === null) {
            return;
        }
        $nb = (float) $d['nb_jours'];
        if ($type === 'LIBERATION') {
            $this->journal->poster((int) $solde['id'], (int) $d['id'], $utilisateurId, 'LIBERATION', 0, -$nb, 0, $ref, $motif);
        }
    }

    private function roleEmploye(int $employeId): string
    {
        $st = $this->pdo->prepare(
            'SELECT u.role FROM employe e JOIN utilisateur u ON u.id = e.utilisateur_id WHERE e.id = ?'
        );
        $st->execute([$employeId]);
        return (string) ($st->fetchColumn() ?: 'employe');
    }

    private function aChef(int $employeId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT dep.chef_id FROM employe e JOIN departement dep ON dep.id = e.departement_id WHERE e.id = ?'
        );
        $st->execute([$employeId]);
        $v = $st->fetchColumn();
        return $v !== false && $v !== null && (int) $v > 0;
    }

    /** Le décideur est-il le chef (utilisateur) du département de cet employé ? */
    private function estChefDuDepartement(int $employeId, int $utilisateurChefId): bool
    {
        $st = $this->pdo->prepare(
            'SELECT 1
             FROM employe e
             JOIN departement dep ON dep.id = e.departement_id
             JOIN employe chef ON chef.id = dep.chef_id
             WHERE e.id = ? AND chef.utilisateur_id = ?
             LIMIT 1'
        );
        $st->execute([$employeId, $utilisateurChefId]);
        return (bool) $st->fetchColumn();
    }

    private function colonneExiste(string $table, string $col): bool
    {
        try {
            $st = $this->pdo->prepare(
                'SELECT 1 FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
            );
            $st->execute([$table, $col]);
            return (bool) $st->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public function actualiserStatutsTemps(): void
    {
        try {
            $this->pdo->exec(
                "UPDATE demande_conge SET statut = 'en_cours'
                 WHERE statut = 'approuvee' AND date_debut <= CURDATE() AND date_fin >= CURDATE()"
            );
            $this->pdo->exec(
                "UPDATE demande_conge SET statut = 'terminee'
                 WHERE statut IN ('approuvee','en_cours') AND date_fin < CURDATE()"
            );
        } catch (Throwable $e) {
            // ENUM pas encore migré
        }
    }

    public function sauvegarderBrouillon(int $employeId, int $utilisateurId, array $in): int
    {
        $typeId = (int) ($in['type_conge_id'] ?? 0);
        $debut = (string) ($in['date_debut'] ?? date('Y-m-d'));
        $fin = (string) ($in['date_fin'] ?? $debut);
        $pd = (string) ($in['periode_debut'] ?? 'MATIN');
        $pf = (string) ($in['periode_fin'] ?? 'APRES_MIDI');
        $motif = trim((string) ($in['motif'] ?? ''));
        $calc = $this->jours->detail($debut, $fin, $pd, $pf);
        $nb = max(0.5, $calc['jours_decomptes'] ?: 0.5);
        if ($typeId <= 0) {
            throw new RuntimeException('Choisissez un type de congé pour le brouillon.');
        }
        $cols = 'employe_id, type_conge_id, date_debut, date_fin, nb_jours, motif, statut';
        $vals = '?, ?, ?, ?, ?, ?, \'brouillon\'';
        $params = [$employeId, $typeId, $debut, $fin, $nb, $motif !== '' ? $motif : null];
        if ($this->colonneExiste('demande_conge', 'periode_debut')) {
            $cols .= ', periode_debut, periode_fin, contact_urgence, remplacant_id, date_reprise';
            $vals .= ', ?, ?, ?, ?, ?';
            $params[] = $pd;
            $params[] = $pf;
            $params[] = trim((string)($in['contact_urgence'] ?? '')) ?: null;
            $params[] = (int)($in['remplacant_id'] ?? 0) ?: null;
            $params[] = $calc['date_reprise'];
        }
        $this->pdo->prepare("INSERT INTO demande_conge ($cols) VALUES ($vals)")->execute($params);
        return (int) $this->pdo->lastInsertId();
    }

    public function soumettreBrouillon(int $demandeId, int $employeId, int $utilisateurId, ?array $upload = null): void
    {
        $st = $this->pdo->prepare('SELECT * FROM demande_conge WHERE id = ? AND employe_id = ?');
        $st->execute([$demandeId, $employeId]);
        $d = $st->fetch();
        if (!$d || $d['statut'] !== 'brouillon') {
            throw new RuntimeException('Brouillon introuvable.');
        }
        $in = [
            'type_conge_id' => $d['type_conge_id'],
            'date_debut' => $d['date_debut'],
            'date_fin' => $d['date_fin'],
            'periode_debut' => $d['periode_debut'] ?? 'MATIN',
            'periode_fin' => $d['periode_fin'] ?? 'APRES_MIDI',
            'motif' => $d['motif'] ?? '',
            'contact_urgence' => $d['contact_urgence'] ?? '',
            'remplacant_id' => $d['remplacant_id'] ?? '',
        ];
        // supprimer brouillon puis redéposer proprement (avec contrôles + réservation)
        $this->pdo->prepare('DELETE FROM demande_conge WHERE id = ? AND statut = \'brouillon\'')->execute([$demandeId]);
        $this->deposer($employeId, $utilisateurId, $in, $upload);
    }

    public function modifierAvantValidation(int $demandeId, int $employeId, int $utilisateurId, array $in, ?array $upload): void
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare('SELECT * FROM demande_conge WHERE id = ? AND employe_id = ? FOR UPDATE');
            $st->execute([$demandeId, $employeId]);
            $d = $st->fetch();
            if (!$d) {
                throw new RuntimeException('Demande introuvable.');
            }
            if (!in_array($d['statut'], ['brouillon', 'en_attente_chef'], true)) {
                throw new RuntimeException('Modification impossible après le début du circuit de validation.');
            }
            // libérer ancienne réservation si existait
            if ($d['statut'] !== 'brouillon') {
                $this->libererSiBesoin($d, $utilisateurId, 'LIBERATION', 'MOD-LIB:'.$demandeId, 'Modification demande');
            }
            $this->pdo->prepare('DELETE FROM demande_conge WHERE id = ?')->execute([$demandeId]);
            // Les validations liées partent en CASCADE si FK ON DELETE CASCADE
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) $this->pdo->rollBack();
            throw $e;
        }
        $this->deposer($employeId, $utilisateurId, $in, $upload);
    }

    public function dupliquer(int $demandeId, int $employeId): int
    {
        $d = $this->trouver($demandeId);
        if ($d === null || (int)$d['employe_id'] !== $employeId) {
            throw new RuntimeException('Demande introuvable.');
        }
        return $this->sauvegarderBrouillon($employeId, 0, [
            'type_conge_id' => $d['type_conge_id'],
            'date_debut' => $d['date_debut'],
            'date_fin' => $d['date_fin'],
            'periode_debut' => $d['periode_debut'] ?? 'MATIN',
            'periode_fin' => $d['periode_fin'] ?? 'APRES_MIDI',
            'motif' => $d['motif'] ?? '',
            'contact_urgence' => $d['contact_urgence'] ?? '',
            'remplacant_id' => $d['remplacant_id'] ?? '',
        ]);
    }

    public function mouvementsSolde(int $soldeId): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM mouvement_solde_conge WHERE solde_id = ? ORDER BY date_mouvement, id'
        );
        $st->execute([$soldeId]);
        return $st->fetchAll();
    }

}
