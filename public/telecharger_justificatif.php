<?php
/**
 * Téléchargement sécurisé d'un justificatif stocké sur le serveur
 * (accessible depuis n'importe quel ordinateur authentifié et autorisé).
 */
require_once __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../services/JustificatifStorage.php';
require_once __DIR__ . '/../models/Employe.php';

exigerRole(['employe', 'chef', 'rh', 'dg']);

$type = $_GET['type'] ?? '';
$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0 || !in_array($type, ['conge', 'absence'], true)) {
    http_response_code(400);
    exit('Requête invalide.');
}

$cheminRel = null;
$autorise = false;
$uid = (int) $_SESSION['utilisateur_id'];
$role = $_SESSION['role'] ?? '';

if ($type === 'conge') {
    $st = $pdo->prepare(
        'SELECT d.justificatif_chemin, d.employe_id, e.utilisateur_id
         FROM demande_conge d JOIN employe e ON e.id = d.employe_id WHERE d.id = ?'
    );
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row && !empty($row['justificatif_chemin'])) {
        $cheminRel = $row['justificatif_chemin'];
        $autorise = in_array($role, ['rh', 'dg', 'chef'], true)
            || (int) $row['utilisateur_id'] === $uid;
    }
} else {
    $st = $pdo->prepare(
        'SELECT a.justificatif_chemin, a.employe_id, e.utilisateur_id
         FROM absence a JOIN employe e ON e.id = a.employe_id WHERE a.id = ?'
    );
    $st->execute([$id]);
    $row = $st->fetch();
    if ($row && !empty($row['justificatif_chemin'])) {
        $cheminRel = $row['justificatif_chemin'];
        $autorise = in_array($role, ['rh', 'dg', 'chef'], true)
            || (int) $row['utilisateur_id'] === $uid;
    }
}

if (!$autorise || $cheminRel === null) {
    http_response_code(403);
    exit('Accès refusé.');
}

$storage = new JustificatifStorage();
$abs = $storage->cheminAbsolu($cheminRel);
if ($abs === null) {
    http_response_code(404);
    exit('Fichier introuvable sur le serveur.');
}

$mime = mime_content_type($abs) ?: 'application/octet-stream';
$nom = basename($abs);
header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($abs));
header('Content-Disposition: attachment; filename="' . $nom . '"');
header('X-Content-Type-Options: nosniff');
readfile($abs);
exit;
