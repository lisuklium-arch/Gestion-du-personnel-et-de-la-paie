<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/NotificationService.php';

exigerConnexion(true);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    reponseJson(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
}

header('Cache-Control: no-store');

$service = new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']));
reponseJson(200, ['ok' => true] + $service->resume((int) $_SESSION['utilisateur_id']));
