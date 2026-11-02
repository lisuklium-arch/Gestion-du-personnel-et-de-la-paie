<?php
declare(strict_types=1);

namespace App\Paie;

abstract class Modele
{
    protected \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    protected function requete(string $sql, array $params = []): \PDOStatement
    {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function verifierEnum(string $valeur, array $valeursAutorisees, string $libelle): void
    {
        if (!in_array($valeur, $valeursAutorisees, true)) {
            throw new \InvalidArgumentException(sprintf('%s invalide : %s', $libelle, $valeur));
        }
    }
}
