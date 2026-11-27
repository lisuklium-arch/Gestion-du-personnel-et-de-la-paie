<?php
require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/Employe.php';
exigerConnexion();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$employe = $id === false ? null : (new Employe($pdo))->trouver((int) $id);
$chemin = $employe['photo_chemin'] ?? '';
if ($employe === null || !is_string($chemin) || !preg_match('#^documents/[a-f0-9]{32}\.(jpg|png)$#', $chemin)) {
    http_response_code(404); exit;
}
$fichier = realpath(__DIR__ . '/../storage/' . $chemin);
$racine = realpath(__DIR__ . '/../storage/documents');
if ($fichier === false || $racine === false || !str_starts_with($fichier, $racine . DIRECTORY_SEPARATOR) || !is_file($fichier)) { http_response_code(404); exit; }
$type = pathinfo($fichier, PATHINFO_EXTENSION) === 'png' ? 'image/png' : 'image/jpeg';
header('Content-Type: ' . $type); header('Content-Length: ' . filesize($fichier));
header('Cache-Control: private, max-age=86400'); header('X-Content-Type-Options: nosniff');
readfile($fichier); exit;
