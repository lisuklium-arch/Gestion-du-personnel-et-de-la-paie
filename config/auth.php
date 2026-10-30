<?php
// Outils de garde : qui est connecté, et a-t-il le droit d'être ici ?
// À appeler en première ligne des pages protégées (après init.php).

const PAGE_CONNEXION = '/public/login.php';
const PAGE_CHANGEMENT_MDP = '/public/changer_mot_de_passe.php';

// Scripts accessibles tant que le mot de passe temporaire n'a pas été changé
const SCRIPTS_SANS_CHANGEMENT_MDP = ['changer_mot_de_passe.php', 'changer_mdp_temporaire.php', 'logout.php'];

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

/** Détruit la session (données, cookie, fichier de session). */
function fermerSession(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/**
 * Compare la session à l'état réel du compte en base, à chaque requête :
 * compte désactivé, dossier suspendu ou sorti => session fermée ; rôle modifié => session mise à jour.
 * Retourne l'état à jour du compte.
 */
function verifierEtatCompte(bool $json): array
{
    global $pdo;
    require_once __DIR__ . '/../models/Utilisateur.php';

    $etat = (new Utilisateur($pdo))->etatSession((int) $_SESSION['utilisateur_id']);

    $refuse = $etat === null
        || $etat['statut_compte'] !== 'actif'
        || in_array($etat['statut_dossier'], ['suspendu', 'sorti'], true);

    if ($refuse) {
        fermerSession();
        if ($json) {
            refuserAcces(401, 'Votre accès a été retiré. Reconnectez-vous.', true);
        }
        header('Location: ' . PAGE_CONNEXION);
        exit;
    }

    $_SESSION['role'] = $etat['role'];
    $_SESSION['doit_changer_mdp'] = (bool) $etat['doit_changer_mdp'];
    return $etat;
}

/** Laisse passer un utilisateur connecté, sinon renvoie vers la connexion (401 pour une API). */
function exigerConnexion(bool $json = false): void
{
    // Empêche le bouton « Précédent » de réafficher une page sensible après déconnexion
    if (!headers_sent()) {
        header('Cache-Control: no-store');
    }

    if (utilisateurConnecte()) {
        $etat = verifierEtatCompte($json);

        // Mot de passe temporaire : rien d'autre n'est accessible tant qu'il n'est pas changé
        $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ((int) $etat['doit_changer_mdp'] === 1 && !in_array($script, SCRIPTS_SANS_CHANGEMENT_MDP, true)) {
            if ($json) {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => false, 'message' => 'Changement de mot de passe requis.', 'redirection' => PAGE_CHANGEMENT_MDP], JSON_UNESCAPED_UNICODE);
                exit;
            }
            header('Location: ' . PAGE_CHANGEMENT_MDP);
            exit;
        }
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
