<?php
declare(strict_types=1);

// Démarrage de l'application : chemins, chargement des classes, configuration, sécurité, session.

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('STORAGE_PATH', ROOT_PATH . '/storage');

require APP_PATH . '/core/helpers.php';

// Chargeur automatique : App\Core\Router => app/core/Router.php, etc.
spl_autoload_register(function (string $class): void {
    $dossiers = ['App\\Core\\' => 'core', 'App\\Controllers\\' => 'controllers', 'App\\Models\\' => 'models'];
    foreach ($dossiers as $prefixe => $dossier) {
        if (str_starts_with($class, $prefixe)) {
            $fichier = APP_PATH . '/' . $dossier . '/' . substr($class, strlen($prefixe)) . '.php';
            if (is_file($fichier)) {
                require $fichier;
            }
            return;
        }
    }
});

use App\Core\Config;
use App\Core\Session;

Config::load();
date_default_timezone_set((string) Config::get('app.timezone'));
mb_internal_encoding('UTF-8');

// Les erreurs ne s'affichent à l'écran qu'en développement ; elles sont toujours écrites dans storage/logs.
error_reporting(E_ALL);
ini_set('display_errors', Config::get('app.debug') ? '1' : '0');
ini_set('log_errors', '1');
ini_set('error_log', STORAGE_PATH . '/logs/php-errors.log');

// Toute erreur non prévue : page d'erreur simple, sans détail technique en production.
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    $code = 500;
    $titre = 'Erreur du serveur';
    $message = Config::get('app.debug') ? $e->getMessage() : 'Une erreur est survenue. Réessayez plus tard.';
    $user = null;
    $flash = [];
    ob_start();
    require APP_PATH . '/views/errors/erreur.php';
    $content = ob_get_clean();
    require APP_PATH . '/views/layouts/auth.php';
});

send_security_headers();
Session::start();
