<?php

class Poste
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function lister(): array
    {
        return $this->pdo->query(
            'SELECT id, intitule, description FROM poste ORDER BY intitule'
        )->fetchAll();
    }

    public function trouver(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, intitule, description FROM poste WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function creer(array $poste): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO poste (intitule, description) VALUES (:intitule, :description)'
        );
        $stmt->execute($poste);
        return (int) $this->pdo->lastInsertId();
    }

    public function modifier(int $id, array $poste): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE poste SET intitule = :intitule, description = :description WHERE id = :id'
        );
        return $stmt->execute($poste + ['id' => $id]);
    }

    public function supprimer(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM poste WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }
}
