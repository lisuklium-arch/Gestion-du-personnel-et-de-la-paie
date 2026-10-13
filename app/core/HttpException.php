<?php
declare(strict_types=1);

namespace App\Core;

/** Erreur HTTP "attendue" : 403 (interdit), 404 (introuvable), 405 (méthode), 419 (jeton CSRF invalide). */
final class HttpException extends \RuntimeException
{
    public const MESSAGES = [
        403 => ['Accès refusé', "Vous n'avez pas le droit d'ouvrir cette page."],
        404 => ['Page introuvable', "Cette page n'existe pas."],
        405 => ['Méthode non autorisée', "Cette action n'est pas permise sur cette adresse."],
        419 => ['Session expirée', 'Le formulaire a expiré ou est invalide. Rechargez la page et réessayez.'],
    ];

    public function __construct(public readonly int $status)
    {
        parent::__construct(self::MESSAGES[$status][0] ?? 'Erreur', $status);
    }
}
