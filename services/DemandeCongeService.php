<?php
require_once __DIR__ . '/JournalSoldeService.php';
require_once __DIR__ . '/JoursOuvrablesService.php';
require_once __DIR__ . '/JustificatifStorage.php';

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
            'SELECT id, code, libelle, decompte_solde, justificatif_requis
             FROM type_conge WHERE actif = 1 ORDER BY libelle'
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

    public function trouver(int $id): ?array
    {
        $st = $this->pdo->prepare(
            'SELECT d.*, t.libelle AS type_libelle, t.decompte_solde, t.justificatif_requis,
                    e.nom, e.prenom, e.matricule, e.departement_id, e.utilisateur_id AS employe_utilisateur_id,
                    u.role AS role_demandeur
             FROM demande_conge d
             JOIN type_conge t ON t.id = d.type_conge_id
             JOIN employe e ON e.id = d.employe_id
             JOIN utilisateur u ON u.id = e.utilisateur_id
             WHERE d.id = ?'
        );
        $st->execute([$id]);
        $row = $st->fetch();
        return $row ?: null;
    }

    public function deposer(int $employeId, int $utilisateurId, array $in, ?array $upload): int
    {
        $typeId = (int) ($in['type_conge_id'] ?? 0);
        $debut = (string) ($in['date_debut'] ?? '');
        $fin = (string) ($in['date_fin'] ?? '');
        $motif = trim((string) ($in['motif'] ?? ''));
        $nb = $this->jours->compter($debut, $fin);
        if ($nb <= 0) {
            throw new RuntimeException('Aucun jour ouvrable sur la période choisie.');
        }
        $type = $this->pdo->prepare('SELECT * FROM type_conge WHERE id = ? AND actif = 1');
        $type->execute([$typeId]);
        $t = $type->fetch();
        if (!$t) {
            throw new RuntimeException('Type de congé invalide.');
        }
        $annee = (int) substr($debut, 0, 4);
        $this->pdo->beginTransaction();
        try {
            $solde = null;
            if ((int) $t['decompte_solde'] === 1) {
                $solde = $this->journal->soldeOuvert($employeId, $typeId, $annee);
                if ($solde === null) {
                    throw new RuntimeException('Aucun solde ouvert pour cette année.');
                }
                if ($this->journal->disponible($solde) + 1e-9 < $nb) {
                    throw new RuntimeException('Solde insuffisant (' . $this->journal->disponible($solde) . ' j. disponibles).');
                }
            }
            $st = $this->pdo->prepare(
                'INSERT INTO demande_conge
                  (employe_id, type_conge_id, date_debut, date_fin, nb_jours, motif, statut)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $role = $this->roleEmploye($employeId);
            $statutInitial = $role === 'rh' ? 'en_attente_rh' : 'en_attente_chef';
            // Si pas de chef de département, bascule RH
            if ($statutInitial === 'en_attente_chef' && !$this->aChef($employeId)) {
                $statutInitial = 'en_attente_rh';
            }
            $st->execute([$employeId, $typeId, $debut, $fin, $nb, $motif !== '' ? $motif : null, $statutInitial]);
            $id = (int) $this->pdo->lastInsertId();

            if ($upload && (int) $t['justificatif_requis'] === 1) {
                $chemin = $this->files->enregistrer($upload, JustificatifStorage::CONGES, $id);
                if ($chemin) {
                    $this->pdo->prepare('UPDATE demande_conge SET justificatif_chemin = ? WHERE id = ?')
                        ->execute([$chemin, $id]);
                } elseif ((int) $t['justificatif_requis'] === 1) {
                    throw new RuntimeException('Justificatif obligatoire pour ce type de congé.');
                }
            }

            if ($solde !== null) {
                $this->journal->poster(
                    (int) $solde['id'], $id, $utilisateurId, 'RESERVATION',
                    0, $nb, 0, 'RES:' . $id, 'Dépôt demande #' . $id
                );
            }
            $this->pdo->commit();
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
        $this->deciderEtape($demandeId, $decideurId, 'chef', $decision, $commentaire, 'en_attente_chef', 'en_attente_rh');
    }

    public function decisionRh(int $demandeId, int $decideurId, string $decision, ?string $commentaire): void
    {
        $dem = $this->trouver($demandeId);
        if ($dem === null) {
            throw new RuntimeException('Demande introuvable.');
        }
        // Congé d'un RH → après instruction RH, passe au DG si approbation en cours
        if (($dem['role_demandeur'] ?? '') === 'rh' && $decision === 'approuve') {
            $this->deciderEtape($demandeId, $decideurId, 'rh', $decision, $commentaire, 'en_attente_rh', 'en_attente_dg', false);
            return;
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

    /** @return list<array> */
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
            "SELECT d.*, t.libelle AS type_libelle, e.nom, e.prenom, e.matricule
             FROM demande_conge d
             JOIN type_conge t ON t.id = d.type_conge_id
             JOIN employe e ON e.id = d.employe_id
             WHERE d.statut IN ('en_attente_rh','en_attente_chef','approuvee','refusee')
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
            $this->pdo->prepare(
                'INSERT INTO validation_conge (demande_conge_id, etape, decision, commentaire, decideur_id)
                 VALUES (?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE decision = VALUES(decision), commentaire = VALUES(commentaire),
                   decideur_id = VALUES(decideur_id), date_decision = CURRENT_TIMESTAMP'
            )->execute([$demandeId, $etape, $mapDecision[$decision], $commentaire, $decideurId]);

            if ($etape === 'chef') {
                // avis consultatif : on passe toujours à la RH
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
}
