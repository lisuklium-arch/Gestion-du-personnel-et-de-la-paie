<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../models/Employe.php';
require_once __DIR__ . '/../models/DemandeDocument.php';

exigerRole(['employe', 'chef', 'rh'], true);
exigerPost();
$employe = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
$type = is_string($_POST['type_attestation'] ?? null) ? trim($_POST['type_attestation']) : '';
$motif = is_string($_POST['motif'] ?? null) ? trim($_POST['motif']) : '';
if ($employe === null || !in_array($type, ['attestation_travail', 'attestation_salaire'], true) || $motif === '' || mb_strlen($motif) > 255) {
    reponseJson(422, ['ok' => false, 'message' => 'Type d’attestation ou motif invalide.']);
}
$id = (new DemandeDocument($pdo))->creer((int) $employe['id'], $type, $motif);
reponseJson(201, ['ok' => true, 'message' => 'Demande d’attestation envoyée à la RH.', 'id' => $id]);
