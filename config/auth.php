<?php
// Outils de garde : qui est connecté, et a-t-il le droit d'être ici ?
// À appeler en première ligne des pages protégées (après init.php).

const PAGE_CONNEXION = '/public/login.php';

function utilisateurConnecte(): bool
{
    return isset($_SESSION['utilisateur_id']);
}

function roleCourant(): ?string
{
    return $_SESSION['role'] ?? null;
}

/** Page d'accueil d'un rôle (rôles de la base : employe, chef, rh, dg). */
function accueilPourRole(?string $role): ?string
{
    return match ($role) {
        'employe' => '/views/employe/accueil_employe.php',
        'chef'    => '/views/cd/equipe_cd.php',
        'rh'      => '/views/rh/tableau_bord_rh.php',
        'dg'      => '/views/dg/tableau_bord_dg.php',
        default   => null,
    };
}

function accueilPourRoleCourant(): ?string
{
    return accueilPourRole(roleCourant());
}

/** Coupe la requête : JSON pour une API, message simple pour une page. */
function refuserAcces(int $code, string $message, bool $json): never
{
    http_response_code($code);

    if ($json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'message' => $message], JSON_UNESCAPED_UNICODE);
    } else {
        header('Content-Type: text/html; charset=utf-8');
        echo '<p>' . h($message) . '</p>';
        $accueil = accueilPourRoleCourant();
        if ($accueil !== null) {
            echo '<p><a href="' . h($accueil) . '">Retour à mon espace</a></p>';
        }
    }
    exit;
}

/** Laisse passer un utilisateur connecté, sinon renvoie vers la connexion (401 pour une API). */
function exigerConnexion(bool $json = false): void
{
    // Empêche le bouton « Précédent » de réafficher une page sensible après déconnexion
    if (!headers_sent()) {
        header('Cache-Control: no-store');
    }

    if (utilisateurConnecte()) {
        return;
    }

    if ($json) {
        refuserAcces(401, 'Connexion requise.', true);
    }

    header('Location: ' . PAGE_CONNEXION);
    exit;
}

/** Laisse passer un utilisateur connecté dont le rôle est dans la liste, sinon 403. */
function exigerRole(array $rolesAutorises, bool $json = false): void
{
    exigerConnexion($json);

    if (!in_array(roleCourant(), $rolesAutorises, true)) {
        refuserAcces(403, 'Accès refusé.', $json);
    }
}
