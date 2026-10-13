<?php
declare(strict_types=1);

// Point d'entrée UNIQUE de l'application : toutes les pages passent par ce fichier.
require dirname(__DIR__) . '/app/bootstrap.php';

use App\Core\Router;

(new Router(require APP_PATH . '/config/routes.php'))
    ->dispatch($_SERVER['REQUEST_METHOD'], request_path());
