<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../models/DocumentEmploye.php';

exigerConnexion();
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    http_response_code(404); exit('Document introuvable.');
}
$document = (new DocumentEmploye($pdo))->trouver($id);
if ($document === null) {
    http_response_code(404); exit('Document introuvable.');
}
$role = roleCourant();
if (!in_array($role, ['rh', 'dg'], true) && (int) $document['utilisateur_id'] !== (int) $_SESSION['utilisateur_id']) {
    http_response_code(403); exit('Accès refusé.');
}
$relatif = str_replace('\\', '/', $document['chemin_prive']);
if (!preg_match('#^documents/[a-f0-9]{32}\.(pdf|jpg|png|docx)$#', $relatif)) {
    http_response_code(404); exit('Document introuvable.');
}
$fichier = realpath(__DIR__ . '/../storage/' . $relatif);
$racine = realpath(__DIR__ . '/../storage/documents');
if ($fichier === false || $racine === false || !str_starts_with($fichier, $racine . DIRECTORY_SEPARATOR) || !is_file($fichier)) {
    http_response_code(404); exit('Document introuvable.');
}
$extensions = ['pdf' => 'application/pdf', 'jpg' => 'image/jpeg', 'png' => 'image/png', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$extension = pathinfo($fichier, PATHINFO_EXTENSION);
header('Content-Type: ' . ($extensions[$extension] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($fichier));
header('Content-Disposition: attachment; filename="' . rawurlencode(basename($document['nom_fichier'])) . '"');
header('X-Content-Type-Options: nosniff');
readfile($fichier);
exit;
