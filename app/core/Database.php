<?php
declare(strict_types=1);

namespace App\Core;

use PDO;

/** Connexion unique à MySQL via PDO. Toutes les requêtes sont PRÉPARÉES (protection contre l'injection SQL). */
final class Database
{
    private static ?PDO $pdo = null;

    public static function pdo(): PDO
    {
        if (self::$pdo === null) {
            $c = Config::get('db');
            $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s', $c['host'], $c['port'], $c['name'], $c['charset']);
            self::$pdo = new PDO($dsn, $c['user'], $c['pass'], [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,   // erreurs SQL = exceptions
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,                    // vraies requêtes préparées
            ]);
        }
        return self::$pdo;
    }

    /** SELECT qui renvoie toutes les lignes. */
    public static function all(string $sql, array $params = []): array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    /** SELECT qui renvoie la première ligne (ou null). */
    public static function one(string $sql, array $params = []): ?array
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        $ligne = $st->fetch();
        return $ligne === false ? null : $ligne;
    }

    /** SELECT qui renvoie une seule valeur (ex. COUNT). */
    public static function value(string $sql, array $params = []): mixed
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchColumn();
    }

    /** INSERT / UPDATE / DELETE : renvoie le nombre de lignes touchées. */
    public static function run(string $sql, array $params = []): int
    {
        $st = self::pdo()->prepare($sql);
        $st->execute($params);
        return $st->rowCount();
    }
}
