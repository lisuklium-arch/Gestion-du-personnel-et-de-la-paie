<?php
declare(strict_types=1);

namespace App\Core;

/** Gestion sécurisée de la session PHP. */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $cfg = Config::get('session');
        ini_set('session.use_strict_mode', '1');     // refuse un identifiant de session inventé par l'attaquant
        ini_set('session.use_only_cookies', '1');    // jamais d'identifiant dans l'URL
        ini_set('session.use_trans_sid', '0');
        session_name($cfg['name']);
        session_set_cookie_params([
            'lifetime' => 0,                         // cookie supprimé à la fermeture du navigateur
            'path'     => base_path() . '/',
            'secure'   => is_https(),                // cookie envoyé seulement en HTTPS quand le site est en HTTPS
            'httponly' => true,                      // illisible par JavaScript
            'samesite' => 'Lax',                     // pas envoyé depuis un autre site (protection CSRF supplémentaire)
        ]);
        session_start();

        // Déconnexion automatique après une période d'inactivité.
        $maintenant = time();
        if (isset($_SESSION['_derniere_activite']) && $maintenant - $_SESSION['_derniere_activite'] > $cfg['lifetime']) {
            self::destroy();
            session_start();
            self::flash('info', 'Votre session a expiré. Reconnectez-vous.');
        }
        $_SESSION['_derniere_activite'] = $maintenant;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 42000, 'path' => $p['path'], 'domain' => $p['domain'],
                'secure' => $p['secure'], 'httponly' => true, 'samesite' => $p['samesite'],
            ]);
        }
        session_destroy();
    }

    /** Message affiché une seule fois à la prochaine page. Types : success, danger, warning, info. */
    public static function flash(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function pullFlash(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return $messages;
    }
}
