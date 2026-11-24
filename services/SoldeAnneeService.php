<?php
require_once __DIR__ . '/JournalSoldeService.php';

/**
 * Ouverture / report / lecture des soldes multi-années (domaine C).
 * N'efface jamais l'historique : uniquement INSERT + mouvements journal.
 */
class SoldeAnneeService
{
    private PDO $pdo;
    private JournalSoldeService $journal;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->journal = new JournalSoldeService($pdo);
    }

    /** Ouvre les soldes pour tous les employés actifs et types à décompte. */
    public function ouvrirAnnee(int $annee, int $auteurUtilisateurId): array
    {
        if ($annee < 2020 || $annee > 2100) {
            throw new RuntimeException('Année invalide.');
        }
        $types = $this->pdo->query(
            'SELECT id, droit_annuel_jours FROM type_conge WHERE actif = 1 AND decompte_solde = 1'
        )->fetchAll();
        $employes = $this->pdo->query(
            "SELECT id FROM employe WHERE statut = 'actif'"
        )->fetchAll();

        $crees = 0;
        $ignores = 0;
        $this->pdo->beginTransaction();
        try {
            foreach ($employes as $e) {
                foreach ($types as $t) {
                    $exist = $this->journal->soldeOuvert((int) $e['id'], (int) $t['id'], $annee);
                    if ($exist !== null) {
                        $ignores++;
                        continue;
                    }
                    $droit = (float) $t['droit_annuel_jours'];
                    $st = $this->pdo->prepare(
                        'INSERT INTO solde_conge (employe_id, type_conge_id, annee, jours_acquis, jours_pris, jours_reserves)
                         VALUES (?, ?, ?, ?, 0, 0)'
                    );
                    // jours_reserves may not exist on very old schema — try with then without
                    try {
                        $st->execute([(int) $e['id'], (int) $t['id'], $annee, $droit]);
                    } catch (Throwable $ex) {
                        $this->pdo->prepare(
                            'INSERT INTO solde_conge (employe_id, type_conge_id, annee, jours_acquis, jours_pris)
                             VALUES (?, ?, ?, ?, 0)'
                        )->execute([(int) $e['id'], (int) $t['id'], $annee, $droit]);
                    }
                    $soldeId = (int) $this->pdo->lastInsertId();
                    if ($droit > 0) {
                        $this->journal->poster(
                            $soldeId, null, $auteurUtilisateurId, 'ACQUISITION',
                            $droit, 0, 0, 'OUV:' . $annee . ':' . $soldeId,
                            'Ouverture droits ' . $annee
                        );
                    }
                    $crees++;
                }
            }
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
        return ['crees' => $crees, 'ignores' => $ignores, 'annee' => $annee];
    }

    /**
     * Report du reliquat N → N+1 pour types reportables.
     * Reliquat = max(0, acquis + reportes - pris - reserves).
     */
    public function reporterVers(int $anneeSource, int $auteurUtilisateurId): array
    {
        $cible = $anneeSource + 1;
        $this->ouvrirAnnee($cible, $auteurUtilisateurId);

        $sql = 'SELECT s.*, t.reportable
                FROM solde_conge s
                JOIN type_conge t ON t.id = s.type_conge_id
                WHERE s.annee = ? AND t.decompte_solde = 1';
        $rows = $this->pdo->prepare($sql);
        $rows->execute([$anneeSource]);
        $nb = 0;
        $total = 0.0;
        foreach ($rows->fetchAll() as $s) {
            if (empty($s['reportable']) && !isset($s['reportable'])) {
                // colonne absente → skip report policy
                continue;
            }
            if (isset($s['reportable']) && (int) $s['reportable'] !== 1) {
                continue;
            }
            $dispo = (float) $s['jours_acquis']
                + (float) ($s['jours_reportes'] ?? 0)
                - (float) $s['jours_pris']
                - (float) ($s['jours_reserves'] ?? 0);
            if ($dispo <= 0) {
                continue;
            }
            $cibleSolde = $this->journal->soldeOuvert((int) $s['employe_id'], (int) $s['type_conge_id'], $cible);
            if ($cibleSolde === null) {
                continue;
            }
            if ($this->colonne('solde_conge', 'jours_reportes')) {
                $this->pdo->prepare(
                    'UPDATE solde_conge SET jours_reportes = jours_reportes + ? WHERE id = ?'
                )->execute([$dispo, (int) $cibleSolde['id']]);
            } else {
                $this->pdo->prepare(
                    'UPDATE solde_conge SET jours_acquis = jours_acquis + ? WHERE id = ?'
                )->execute([$dispo, (int) $cibleSolde['id']]);
            }
            $this->journal->poster(
                (int) $cibleSolde['id'], null, $auteurUtilisateurId, 'ACQUISITION',
                $dispo, 0, 0, 'REP:' . $anneeSource . ':' . $s['id'],
                'Report reliquat ' . $anneeSource
            );
            $nb++;
            $total += $dispo;
        }
        return ['reports' => $nb, 'jours' => $total, 'annee_source' => $anneeSource, 'annee_cible' => $cible];
    }

    public function anneesConnues(): array
    {
        $rows = $this->pdo->query('SELECT DISTINCT annee FROM solde_conge ORDER BY annee DESC')->fetchAll();
        return array_map(static fn ($r) => (int) $r['annee'], $rows);
    }

    public function historiqueSolde(int $soldeId): array
    {
        try {
            $st = $this->pdo->prepare(
                'SELECT m.*, u.email
                 FROM mouvement_solde_conge m
                 LEFT JOIN utilisateur u ON u.id = m.auteur_id
                 WHERE m.solde_id = ?
                 ORDER BY m.date_mouvement, m.id'
            );
            $st->execute([$soldeId]);
            return $st->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function soldesEmploye(int $employeId, ?int $annee = null): array
    {
        $sql = 'SELECT s.*, t.libelle, t.code
                FROM solde_conge s
                JOIN type_conge t ON t.id = s.type_conge_id
                WHERE s.employe_id = ?';
        $params = [$employeId];
        if ($annee !== null) {
            $sql .= ' AND s.annee = ?';
            $params[] = $annee;
        }
        $sql .= ' ORDER BY s.annee DESC, t.libelle';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['disponible'] = (float) $r['jours_acquis']
                + (float) ($r['jours_reportes'] ?? 0)
                - (float) $r['jours_pris']
                - (float) ($r['jours_reserves'] ?? 0);
        }
        return $rows;
    }

    public function corriger(int $soldeId, int $auteurId, float $deltaAcquis, string $motif): void
    {
        $motif = trim($motif);
        if ($motif === '') {
            throw new RuntimeException('Le motif de correction est obligatoire.');
        }
        if (abs($deltaAcquis) < 1e-9) {
            throw new RuntimeException('Indiquez un écart non nul.');
        }
        $st = $this->pdo->prepare('SELECT * FROM solde_conge WHERE id = ? FOR UPDATE');
        $this->pdo->beginTransaction();
        try {
            $st->execute([$soldeId]);
            $s = $st->fetch();
            if (!$s) {
                throw new RuntimeException('Solde introuvable.');
            }
            $this->journal->poster(
                $soldeId, null, $auteurId, 'CORRECTION',
                $deltaAcquis, 0, 0, 'CORR:' . $soldeId . ':' . time(),
                $motif
            );
            $this->pdo->prepare(
                'UPDATE solde_conge SET jours_acquis = jours_acquis + ? WHERE id = ?'
            )->execute([$deltaAcquis, $soldeId]);
            $this->pdo->commit();
        } catch (Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
    }

    private function colonne(string $table, string $col): bool
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
}
