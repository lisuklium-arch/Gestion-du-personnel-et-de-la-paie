<?php
// Chargé en première ligne de chaque page : config, erreurs, fuseau, session, PDO.

require_once dirname(__DIR__) . '/app/core/Config.php';
\App\Core\Config::loadEnvFile(dirname(__DIR__));

$cfg = require __DIR__ . '/config.php';
$dbEnv = [
    'host' => 'LEGACY_DB_HOST',
    'port' => 'LEGACY_DB_PORT',
    'name' => 'LEGACY_DB_NAME',
    'user' => 'LEGACY_DB_USER',
    'pass' => 'LEGACY_DB_PASS',
];
foreach ($dbEnv as $key => $envKey) {
    $value = $_ENV[$envKey] ?? getenv($envKey);
    if ($value !== false && $value !== null) {
        $cfg['db'][$key] = $key === 'port' ? (int) $value : $value;
    }
}
$cfg['db']['host'] = ltrim(trim((string) $cfg['db']['host']), '@');
if ($cfg['db']['host'] === '') {
    $cfg['db']['host'] = '127.0.0.1';
}

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
