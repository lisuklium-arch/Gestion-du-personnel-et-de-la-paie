<?php
require __DIR__ . '/../config/init.php'; require_once __DIR__ . '/../config/api.php'; require_once __DIR__ . '/../models/Employe.php'; require_once __DIR__ . '/../services/RappelPhotoProfilService.php';
exigerRole(['employe','chef','rh'], true); exigerPost();
$employe = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
if ($employe === null) reponseJson(404, ['ok'=>false,'message'=>'Dossier introuvable.']);
(new RappelPhotoProfilService($pdo, new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']))))->masquer((int) $employe['id']);
reponseJson(200, ['ok'=>true]);
