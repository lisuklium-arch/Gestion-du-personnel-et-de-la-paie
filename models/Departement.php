<?php

class Departement
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function lister(): array
    {
        return $this->pdo->query(
            'SELECT d.id, d.nom, d.description, d.parent_id, d.chef_id,
                    p.nom AS departement_parent,
                    CONCAT(e.prenom, \' \', e.nom) AS chef
             FROM departement d
             LEFT JOIN departement p ON p.id = d.parent_id
             LEFT JOIN employe e ON e.id = d.chef_id
             ORDER BY d.nom'
        )->fetchAll();
    }

    public function trouver(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, nom, description, parent_id, chef_id FROM departement WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function creer(array $departement): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO departement (nom, description, parent_id, chef_id)
             VALUES (:nom, :description, :parent_id, :chef_id)'
        );
        $stmt->execute($departement);
        return (int) $this->pdo->lastInsertId();
    }

    public function modifier(int $id, array $departement): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE departement
             SET nom = :nom, description = :description,
                 parent_id = :parent_id, chef_id = :chef_id
             WHERE id = :id'
        );
        return $stmt->execute($departement + ['id' => $id]);
    }

    public function supprimer(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM departement WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
