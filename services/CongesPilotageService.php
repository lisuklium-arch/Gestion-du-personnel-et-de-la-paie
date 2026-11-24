<?php
class CongesPilotageService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function kpis(): array
    {
        $attente = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM demande_conge
             WHERE statut IN ('en_attente_chef','en_attente_rh','en_attente_dg')"
        )->fetchColumn();
        $approuveesMois = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM demande_conge
             WHERE statut IN ('approuvee','en_cours','terminee')
               AND YEAR(date_soumission) = YEAR(CURDATE())
               AND MONTH(date_soumission) = MONTH(CURDATE())"
        )->fetchColumn();
        $absNonJust = 0;
        try {
            $absNonJust = (int) $this->pdo->query(
                "SELECT COUNT(*) FROM absence
                 WHERE justifiee = 0
                   AND YEAR(date_absence) = YEAR(CURDATE())
                   AND MONTH(date_absence) = MONTH(CURDATE())"
            )->fetchColumn();
        } catch (Throwable $e) {
        }
        $prochains = (int) $this->pdo->query(
            "SELECT COUNT(*) FROM demande_conge
             WHERE statut IN ('approuvee','en_cours')
               AND date_debut BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)"
        )->fetchColumn();
        return [
            'en_attente' => $attente,
            'approuvees_mois' => $approuveesMois,
            'absences_non_justifiees_mois' => $absNonJust,
            'departs_30j' => $prochains,
        ];
    }

    public function fileParEtape(): array
    {
        return $this->pdo->query(
            "SELECT statut, COUNT(*) AS n FROM demande_conge
             WHERE statut IN ('en_attente_chef','en_attente_rh','en_attente_dg','brouillon')
             GROUP BY statut"
        )->fetchAll();
    }

    /** Événements mois pour calendrier (département optionnel). */
    public function evenementsMois(int $annee, int $mois, ?int $departementId = null): array
    {
        $debut = sprintf('%04d-%02d-01', $annee, $mois);
        $fin = date('Y-m-t', strtotime($debut));
        $sql = "SELECT d.id, d.date_debut, d.date_fin, d.statut, d.nb_jours,
                       e.nom, e.prenom, e.matricule, e.departement_id, t.libelle AS type_libelle
                FROM demande_conge d
                JOIN employe e ON e.id = d.employe_id
                JOIN type_conge t ON t.id = d.type_conge_id
                WHERE d.statut IN ('en_attente_chef','en_attente_rh','en_attente_dg','approuvee','en_cours','terminee')
                  AND d.date_debut <= ? AND d.date_fin >= ?";
        $params = [$fin, $debut];
        if ($departementId) {
            $sql .= ' AND e.departement_id = ?';
            $params[] = $departementId;
        }
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        $conges = $st->fetchAll();

        $absences = [];
        try {
            $sqlA = "SELECT a.id, a.date_absence, a.justifiee, e.nom, e.prenom, e.matricule, e.departement_id
                     FROM absence a
                     JOIN employe e ON e.id = a.employe_id
                     WHERE a.date_absence BETWEEN ? AND ?";
            $p = [$debut, $fin];
            if ($departementId) {
                $sqlA .= ' AND e.departement_id = ?';
                $p[] = $departementId;
            }
            $st = $this->pdo->prepare($sqlA);
            $st->execute($p);
            $absences = $st->fetchAll();
        } catch (Throwable $e) {
        }
        return ['conges' => $conges, 'absences' => $absences, 'debut' => $debut, 'fin' => $fin];
    }

    /**
     * Alerte si >= seuil employés du même département absents (congé approuvé) sur une journée.
     */
    public function alertesChevauchementEquipe(string $debut, string $fin, int $seuil = 2): array
    {
        $alertes = [];
        $d0 = new DateTimeImmutable($debut);
        $d1 = new DateTimeImmutable($fin);
        for ($c = $d0; $c <= $d1; $c = $c->modify('+1 day')) {
            if ((int) $c->format('N') >= 6) {
                continue;
            }
            $jour = $c->format('Y-m-d');
            $st = $this->pdo->prepare(
                "SELECT e.departement_id, dep.nom AS departement, COUNT(DISTINCT d.employe_id) AS n
                 FROM demande_conge d
                 JOIN employe e ON e.id = d.employe_id
                 JOIN departement dep ON dep.id = e.departement_id
                 WHERE d.statut IN ('approuvee','en_cours','en_attente_chef','en_attente_rh','en_attente_dg')
                   AND d.date_debut <= ? AND d.date_fin >= ?
                 GROUP BY e.departement_id, dep.nom
                 HAVING n >= ?"
            );
            $st->execute([$jour, $jour, $seuil]);
            foreach ($st->fetchAll() as $row) {
                $alertes[] = [
                    'date' => $jour,
                    'departement_id' => (int) $row['departement_id'],
                    'departement' => $row['departement'],
                    'nb' => (int) $row['n'],
                ];
            }
        }
        return $alertes;
    }

    public function absencesJustificatifEnRetard(int $delaiJours = 3): array
    {
        try {
            $st = $this->pdo->prepare(
                "SELECT a.*, e.nom, e.prenom, e.matricule
                 FROM absence a
                 JOIN employe e ON e.id = a.employe_id
                 WHERE a.justifiee = 0
                   AND a.date_absence < DATE_SUB(CURDATE(), INTERVAL ? DAY)
                 ORDER BY a.date_absence DESC
                 LIMIT 100"
            );
            $st->execute([$delaiJours]);
            return $st->fetchAll();
        } catch (Throwable $e) {
            return [];
        }
    }

    public function exportDemandes(?string $depuis, ?string $jusque, ?string $statut): array
    {
        $sql = "SELECT d.id, d.date_soumission, d.date_debut, d.date_fin, d.nb_jours, d.statut, d.motif,
                       e.matricule, e.nom, e.prenom, t.libelle AS type_conge, dep.nom AS departement
                FROM demande_conge d
                JOIN employe e ON e.id = d.employe_id
                JOIN type_conge t ON t.id = d.type_conge_id
                JOIN departement dep ON dep.id = e.departement_id
                WHERE 1=1";
        $params = [];
        if ($depuis) {
            $sql .= ' AND d.date_debut >= ?';
            $params[] = $depuis;
        }
        if ($jusque) {
            $sql .= ' AND d.date_fin <= ?';
            $params[] = $jusque;
        }
        if ($statut) {
            $sql .= ' AND d.statut = ?';
            $params[] = $statut;
        }
        $sql .= ' ORDER BY d.date_soumission DESC';
        $st = $this->pdo->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }
}
