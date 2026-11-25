<?php

class DocumentEmploye
{
    public function __construct(private PDO $pdo)
    {
    }

    public function creer(int $employeId, string $type, string $nomFichier, string $cheminPrive, int $ajouteParId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO document_employe (employe_id, type, nom_fichier, chemin_prive, ajoute_par_id)
             VALUES (:employe_id, :type, :nom_fichier, :chemin_prive, :ajoute_par_id)'
        );
        $stmt->execute([
            ':employe_id' => $employeId,
            ':type' => $type,
            ':nom_fichier' => $nomFichier,
            ':chemin_prive' => $cheminPrive,
            ':ajoute_par_id' => $ajouteParId,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listerPourEmploye(int $employeId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, type, nom_fichier, chemin_prive, date_ajout FROM document_employe
             WHERE employe_id = :employe_id ORDER BY date_ajout DESC, id DESC'
        );
        $stmt->execute([':employe_id' => $employeId]);
        return $stmt->fetchAll();
    }

    public function trouver(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT de.id, de.employe_id, de.nom_fichier, de.chemin_prive, e.utilisateur_id
             FROM document_employe de JOIN employe e ON e.id = de.employe_id WHERE de.id = :id'
        );
        $stmt->execute([':id' => $id]);
        return $stmt->fetch() ?: null;
    }
}
