<?php
require_once __DIR__ . '/JustificatifStorage.php';

class AbsenceService
{
    private PDO $pdo;
    private JustificatifStorage $files;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->files = new JustificatifStorage();
    }

    public function equipeDuChef(int $chefUtilisateurId): array
    {
        $st = $this->pdo->prepare(
            'SELECT e.id, e.matricule, e.nom, e.prenom
             FROM employe e
             JOIN departement d ON d.id = e.departement_id
             JOIN employe chef ON chef.id = d.chef_id
             WHERE chef.utilisateur_id = ? AND e.statut = \'actif\'
             ORDER BY e.nom, e.prenom'
        );
        $st->execute([$chefUtilisateurId]);
        return $st->fetchAll();
    }

    public function listerPourEmployes(array $employeIds, string $debut, string $fin): array
    {
        if ($employeIds === []) {
            return [];
        }
        $in = implode(',', array_fill(0, count($employeIds), '?'));
        $params = array_map('intval', $employeIds);
        $params[] = $debut;
        $params[] = $fin;
        $st = $this->pdo->prepare(
            "SELECT a.*, e.nom, e.prenom, e.matricule
             FROM absence a JOIN employe e ON e.id = a.employe_id
             WHERE a.employe_id IN ($in) AND a.date_absence BETWEEN ? AND ?
             ORDER BY a.date_absence DESC"
        );
        $st->execute($params);
        return $st->fetchAll();
    }

    public function marquer(int $employeId, string $date, ?string $motif, int $marqueurId, ?array $upload): int
    {
        $this->pdo->beginTransaction();
        try {
            $st = $this->pdo->prepare(
                'INSERT INTO absence (employe_id, date_absence, motif, justifiee, marquee_par_id)
                 VALUES (?, ?, ?, 0, ?)
                 ON DUPLICATE KEY UPDATE motif = VALUES(motif), marquee_par_id = VALUES(marquee_par_id)'
            );
            $st->execute([$employeId, $date, $motif, $marqueurId]);
            $id = (int) $this->pdo->lastInsertId();
            if ($id === 0) {
                $q = $this->pdo->prepare('SELECT id FROM absence WHERE employe_id = ? AND date_absence = ?');
                $q->execute([$employeId, $date]);
                $id = (int) $q->fetchColumn();
            }
            if ($upload) {
                $chemin = $this->files->enregistrer($upload, JustificatifStorage::ABSENCES, $id);
                if ($chemin) {
                    $this->pdo->prepare(
                        'UPDATE absence SET justificatif_chemin = ?, justifiee = 1 WHERE id = ?'
                    )->execute([$chemin, $id]);
                }
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

    public function justifier(int $absenceId, int $acteurId, ?array $upload, bool $estRh = false): void
    {
        $st = $this->pdo->prepare('SELECT * FROM absence WHERE id = ?');
        $st->execute([$absenceId]);
        $a = $st->fetch();
        if (!$a) {
            throw new RuntimeException('Absence introuvable.');
        }
        $chemin = null;
        if ($upload) {
            $chemin = $this->files->enregistrer($upload, JustificatifStorage::ABSENCES, $absenceId);
        }
        if ($chemin) {
            $this->pdo->prepare(
                'UPDATE absence SET justificatif_chemin = ?, justifiee = 1 WHERE id = ?'
            )->execute([$chemin, $absenceId]);
        } elseif ($estRh) {
            $this->pdo->prepare('UPDATE absence SET justifiee = 1 WHERE id = ?')->execute([$absenceId]);
        } else {
            throw new RuntimeException('Fichier justificatif requis.');
        }
    }

    /** Contrat paie : absences non justifiées du mois. */
    public function nonJustifieesDuMois(int $employeId, int $mois, int $annee): array
    {
        $st = $this->pdo->prepare(
            'SELECT * FROM absence
             WHERE employe_id = ? AND justifiee = 0
               AND MONTH(date_absence) = ? AND YEAR(date_absence) = ?
             ORDER BY date_absence'
        );
        $st->execute([$employeId, $mois, $annee]);
        return $st->fetchAll();
    }
}
