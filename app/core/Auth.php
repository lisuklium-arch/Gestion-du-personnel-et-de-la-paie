<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\Journal;
use App\Models\Utilisateur;

/** Connexion, déconnexion et droits d'accès par profil. */
final class Auth
{
    // Hachage bidon : sert à faire durer le calcul même si l'e-mail n'existe pas (évite de deviner quels comptes existent).
    private const FAUX_HASH = '$2y$10$W56l3Lc.SMifJ5rDM1.YVujZhOsZIOY/z1kN6fWVxf6yQxQ2WyTXe';

    // Qui peut faire quoi. D'après le diagramme de cas d'utilisation : le chef de département et le responsable RH
    // ont aussi un espace employé ; le directeur général a seulement ses propres pages.
    private const ROLES_EFFECTIFS = [
        'EMPLOYE'           => ['EMPLOYE'],
        'CHEF_DEPARTEMENT'  => ['CHEF_DEPARTEMENT', 'EMPLOYE'],
        'RESPONSABLE_RH'    => ['RESPONSABLE_RH', 'EMPLOYE'],
        'DIRECTEUR_GENERAL' => ['DIRECTEUR_GENERAL'],
    ];

    private const ACCUEIL = [
        'EMPLOYE'           => '/employe',
        'CHEF_DEPARTEMENT'  => '/chef',
        'RESPONSABLE_RH'    => '/rh',
        'DIRECTEUR_GENERAL' => '/direction',
    ];

    private const MESSAGE_ECHEC = 'Adresse e-mail ou mot de passe incorrect, ou compte temporairement bloqué.';

    /** Tente une connexion. Renvoie ['ok' => bool, 'message' => ?string]. */
    public static function attempt(string $email, string $motDePasse, ?string $ip): array
    {
        $user = Utilisateur::trouverParEmail($email);

        if ($user === null) {
            password_verify($motDePasse, self::FAUX_HASH);
            return ['ok' => false, 'message' => self::MESSAGE_ECHEC];
        }

        // Blocage temporaire après trop d'échecs (contre les essais de mots de passe en série).
        $max = (int) Config::get('auth.max_attempts');
        $minutes = (int) Config::get('auth.lockout_minutes');
        if (Utilisateur::echecsRecents((int) $user['id_utilisateur'], $minutes) >= $max) {
            return ['ok' => false, 'message' => self::MESSAGE_ECHEC];
        }

        if (!password_verify($motDePasse, $user['mot_de_passe_hash'])) {
            Journal::enregistrer((int) $user['id_utilisateur'], 'ECHEC_CONNEXION', 'utilisateur', (int) $user['id_utilisateur'], 'Mot de passe incorrect', $ip);
            return ['ok' => false, 'message' => self::MESSAGE_ECHEC];
        }

        // Le mot de passe est bon : on vérifie maintenant que le compte est actif.
        if ($user['statut'] !== 'ACTIF') {
            Journal::enregistrer((int) $user['id_utilisateur'], 'CONNEXION_REFUSEE', 'utilisateur', (int) $user['id_utilisateur'], 'Compte ' . $user['statut'], $ip);
            return ['ok' => false, 'message' => "Votre compte n'est pas actif. Contactez le service RH."];
        }

        // Connexion réussie. Nouvel identifiant de session (contre la fixation de session) et nouveau jeton CSRF.
        session_regenerate_id(true);
        unset($_SESSION['_csrf']);
        $_SESSION['user'] = [
            'id_utilisateur' => (int) $user['id_utilisateur'],
            'id_employe'     => (int) $user['id_employe'],
            'role'           => $user['role'],
            'prenom'         => $user['prenom'],
            'nom'            => $user['nom'],
            'email'          => $user['email'],
        ];
        // Le mot de passe est re-haché si l'algorithme par défaut de PHP a évolué.
        $nouveauHash = password_needs_rehash($user['mot_de_passe_hash'], PASSWORD_DEFAULT) ? password_hash($motDePasse, PASSWORD_DEFAULT) : null;
        Utilisateur::majConnexion((int) $user['id_utilisateur'], $nouveauHash);
        Journal::enregistrer((int) $user['id_utilisateur'], 'CONNEXION', 'utilisateur', (int) $user['id_utilisateur'], null, $ip);

        return ['ok' => true, 'message' => null];
    }

    public static function logout(): void
    {
        if (self::check()) {
            Journal::enregistrer(self::user()['id_utilisateur'], 'DECONNEXION', 'utilisateur', self::user()['id_utilisateur'], null, client_ip());
        }
        Session::destroy();
        Session::start();   // nouvelle session vide (pour afficher le message "déconnecté")
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function role(): ?string
    {
        return self::user()['role'] ?? null;
    }

    /** L'utilisateur connecté a-t-il l'un des profils demandés (en tenant compte de l'héritage) ? */
    public static function allows(array $rolesRequis): bool
    {
        $role = self::role();
        return $role !== null && array_intersect(self::ROLES_EFFECTIFS[$role] ?? [], $rolesRequis) !== [];
    }

    public static function home(?string $role): string
    {
        return self::ACCUEIL[$role] ?? '/login';
    }
}
