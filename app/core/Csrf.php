<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Protection CSRF : chaque formulaire contient un jeton secret lié à la session.
 * Un site pirate ne connaît pas ce jeton, donc ne peut pas faire poster un formulaire à la place de l'utilisateur.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    /** Champ caché à mettre dans tout formulaire POST : <?= csrf_field() ?> */
    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function check(): void
    {
        $envoye = $_POST['_csrf'] ?? null;
        if (!is_string($envoye) || empty($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $envoye)) {
            throw new HttpException(419);
        }
    }
}
