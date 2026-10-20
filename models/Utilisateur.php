<?php

class Utilisateur
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function trouverParEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE email = :email');
        $stmt->execute(['email' => $email]);
        return $stmt->fetch() ?: null;
    }

    public function trouverParId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM utilisateur WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }


    public function majDerniereConnexion(int $id): bool
    {
        $stmt = $this->pdo->prepare('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id = :id');
        return $stmt->execute(['id' => $id]);
    }

    public function majMotDePasse(int $id, string $hash): bool
{
    $stmt = $this->pdo->prepare(
        'UPDATE utilisateur SET mot_de_passe_hash = :hash, doit_changer_mdp = 0 WHERE id = :id'
    );
    return $stmt->execute([':hash' => $hash, ':id' => $id]);
}
}
