<?php
declare(strict_types=1);

namespace App\Core;

/** Accès aux réglages : Config::get('db.host'). Lit d'abord le fichier .env. */
final class Config
{
    private static array $items = [];

    public static function load(): void
    {
        $fichier = ROOT_PATH . '/.env';
        if (is_file($fichier)) {
            foreach (file($fichier, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $ligne) {
                $ligne = trim($ligne);
                if ($ligne === '' || $ligne[0] === '#' || !str_contains($ligne, '=')) {
                    continue;
                }
                [$cle, $valeur] = explode('=', $ligne, 2);
                $valeur = preg_replace('/\s+#.*$/', '', $valeur);          // commentaire en fin de ligne
                $_ENV[trim($cle)] = trim(trim($valeur), "\"'");
            }
        }
        self::$items = require APP_PATH . '/config/config.php';
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $valeur = self::$items;
        foreach (explode('.', $key) as $morceau) {
            if (!is_array($valeur) || !array_key_exists($morceau, $valeur)) {
                return $default;
            }
            $valeur = $valeur[$morceau];
        }
        return $valeur;
    }
}
