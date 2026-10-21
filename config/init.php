<?php
// Chargé en première ligne de chaque page : config, erreurs, fuseau, session, PDO.

$cfg = require __DIR__ . '/config.php';

require __DIR__.'/csrf.php';
require __DIR__.'/auth.php';

// Affichage des erreurs : visibles en développement, masquées (mais journalisées) sinon
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors', $cfg['app']['debug'] ? '1' : '0');

date_default_timezone_set($cfg['app']['timezone']);


if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function h($valeur): string
{
    return htmlspecialchars((string) $valeur, ENT_QUOTES, 'UTF-8');
}

$pdo = require __DIR__ . '/db.php';
