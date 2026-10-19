<?php

$cfg ??= require __DIR__ . '/config.php';

$dsn = "mysql:host={$cfg['db']['host']};port={$cfg['db']['port']};dbname={$cfg['db']['name']};charset={$cfg['db']['charset']}";

try {
    $pdo = new PDO($dsn, $cfg['db']['user'], $cfg['db']['pass'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log('Connexion BD impossible : ' . $e->getMessage());
    http_response_code(500);
    exit($cfg['app']['debug'] ? 'Erreur BD : ' . $e->getMessage() : 'Service indisponible.');
}

return $pdo;
