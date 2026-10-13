<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Journal des actions (traçabilité) : table journal_action. */
final class Journal
{
    public static function enregistrer(int $idUtilisateur, string $action, string $entite, ?int $idEntite, ?string $details, ?string $ip): void
    {
        Database::run(
            'INSERT INTO journal_action (id_utilisateur, action, entite, id_entite, details, adresse_ip)
             VALUES (:u, :a, :e, :i, :d, :ip)',
            ['u' => $idUtilisateur, 'a' => $action, 'e' => $entite, 'i' => $idEntite, 'd' => $details, 'ip' => $ip]
        );
    }
}
