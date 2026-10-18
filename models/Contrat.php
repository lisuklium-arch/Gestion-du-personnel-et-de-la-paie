<?php

class Contrat
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function lister(?int $employeId = null): array
    {
        $sql = 'SELECT c.*, e.matricule, e.nom, e.prenom
                FROM contrat c
                JOIN employe e ON e.id = c.employe_id';
        if ($employeId !== null) {
            $stmt = $this->pdo->prepare($sql . ' WHERE c.employe_id = :employe_id ORDER BY c.date_debut DESC');
            $stmt->execute(['employe_id' => $employeId]);
            return $stmt->fetchAll();
        }

        return $this->pdo->query($sql . ' ORDER BY e.nom, e.prenom, c.date_debut DESC')->fetchAll();
    }

    public function trouver(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM contrat WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function getContratEnCours(int $employeId): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT *
             FROM contrat
             WHERE employe_id = :employe_id
               AND statut = \'en_cours\'
               AND date_debut <= CURRENT_DATE()
               AND (date_fin IS NULL OR date_fin >= CURRENT_DATE())
             ORDER BY date_debut DESC, id DESC
             LIMIT 1'
        );
        $stmt->execute(['employe_id' => $employeId]);
        return $stmt->fetch() ?: null;
    }

    public function getTauxHoraire(int $employeId): ?float
    {
        $contrat = $this->getContratEnCours($employeId);
        if ($contrat === null) {
            return null;
        }

        $heuresMensuelles = (float) $contrat['heures_mensuelles'];
        if ($heuresMensuelles <= 0) {
            throw new UnexpectedValueException('Le contrat contient un nombre d’heures mensuelles invalide.');
        }

        return (float) $contrat['salaire_base'] / $heuresMensuelles;
    }

    public function creer(array $contrat): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO contrat
                (employe_id, type, date_debut, date_fin, salaire_base,
                 heures_mensuelles, statut, fichier_chemin)
             VALUES
                (:employe_id, :type, :date_debut, :date_fin, :salaire_base,
                 :heures_mensuelles, :statut, :fichier_chemin)'
        );
        $stmt->execute($contrat);
        return (int) $this->pdo->lastInsertId();
    }

    public function modifier(int $id, array $contrat): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE contrat
             SET employe_id = :employe_id, type = :type, date_debut = :date_debut,
                 date_fin = :date_fin, salaire_base = :salaire_base,
                 heures_mensuelles = :heures_mensuelles, statut = :statut,
                 fichier_chemin = :fichier_chemin
             WHERE id = :id'
        );
        return $stmt->execute($contrat + ['id' => $id]);
    }

    public function supprimer(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM contrat WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
