<?php
declare(strict_types=1);

// Réglages de l'application. Les valeurs viennent du fichier .env (voir .env.example).
return [
    'app' => [
        'name'     => env('APP_NAME', 'Personnel et paie'),
        'debug'    => filter_var(env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOLEAN),
        'timezone' => env('APP_TIMEZONE', 'Africa/Douala'),
    ],
    'db' => [
        'host'    => env('DB_HOST', '127.0.0.1'),
        'port'    => (int) env('DB_PORT', 3306),
        'name'    => env('DB_NAME', 'personnel_paie'),
        'user'    => env('DB_USER', 'paie_app'),
        'pass'    => env('DB_PASS', ''),
        'charset' => 'utf8mb4',
    ],
    'session' => [
        'name'     => 'PAIESESSID',
        'lifetime' => (int) env('SESSION_LIFETIME_MIN', 30) * 60,
    ],
    'auth' => [
        'max_attempts'    => (int) env('MAX_LOGIN_ATTEMPTS', 5),
        'lockout_minutes' => (int) env('LOCKOUT_MINUTES', 15),
    ],
];
