<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Comptes de connexion : tables utilisateur + employe. */
final class Utilisateur
{
    public static function trouverParEmail(string $email): ?array
    {
        return Database::one(
            'SELECT u.id_utilisateur, u.email, u.mot_de_passe_hash, u.role, u.statut,
                    e.id_employe, e.prenom, e.nom
             FROM utilisateur u
             JOIN employe e ON e.id_utilisateur = u.id_utilisateur
             WHERE u.email = :email',
            ['email' => $email]
        );
    }

    /** Nombre d'échecs de connexion récents, depuis la dernière connexion réussie. */
    public static function echecsRecents(int $idUtilisateur, int $minutes): int
    {
        return (int) Database::value(
            "SELECT COUNT(*) FROM journal_action
             WHERE id_utilisateur = :id AND action = 'ECHEC_CONNEXION'
               AND date_action > (NOW() - INTERVAL $minutes MINUTE)
               AND date_action > COALESCE((SELECT MAX(date_action) FROM journal_action
                                           WHERE id_utilisateur = :id2 AND action = 'CONNEXION'), '1970-01-01')",
            ['id' => $idUtilisateur, 'id2' => $idUtilisateur]
        );
    }

    public static function majConnexion(int $idUtilisateur, ?string $nouveauHash): void
    {
        Database::run('UPDATE utilisateur SET derniere_connexion = NOW() WHERE id_utilisateur = :id', ['id' => $idUtilisateur]);
        if ($nouveauHash !== null) {
            Database::run('UPDATE utilisateur SET mot_de_passe_hash = :h WHERE id_utilisateur = :id', ['h' => $nouveauHash, 'id' => $idUtilisateur]);
        }
    }
}
