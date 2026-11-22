<?php

class ParametreSysteme
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function lister(): array
    {
        return $this->pdo->query(
            'SELECT cle, valeur, description, date_modification FROM parametre_systeme ORDER BY cle'
        )->fetchAll();
    }

    public function trouver(string $cle): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT cle, valeur, description, date_modification FROM parametre_systeme WHERE cle = :cle'
        );
        $stmt->execute([':cle' => $cle]);
        $ligne = $stmt->fetch();
        return $ligne === false ? null : $ligne;
    }

    public function modifierValeur(string $cle, string $valeur): bool
    {
        $stmt = $this->pdo->prepare(
            'UPDATE parametre_systeme SET valeur = :valeur, date_modification = NOW() WHERE cle = :cle'
        );
        $stmt->execute([':valeur' => $valeur, ':cle' => $cle]);
        return $stmt->rowCount() === 1;
    }

    public function supprimer(string $cle): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM parametre_systeme WHERE cle = :cle');
        $stmt->execute([':cle' => $cle]);
        return $stmt->rowCount() === 1;
    }
}
