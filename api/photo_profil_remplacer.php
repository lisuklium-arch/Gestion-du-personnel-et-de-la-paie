<?php
require __DIR__ . '/../config/init.php'; require_once __DIR__ . '/../config/api.php'; require_once __DIR__ . '/../services/PhotoProfilService.php';
exigerRole(['employe', 'chef', 'rh'], true); exigerPost();
$service = new PhotoProfilService($pdo, new Employe($pdo), new JournalService(new JournalAction($pdo)), __DIR__ . '/../storage/documents');
$resultat = $service->remplacer((int) $_SESSION['utilisateur_id'], $_FILES['photo'] ?? null);
reponseJson($resultat['ok'] ? 200 : 422, $resultat);
