<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/NotificationService.php';

exigerConnexion(true);
exigerPost();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    reponseJson(400, ['ok' => false, 'message' => 'Notification invalide.']);
}

$utilisateurId = (int) $_SESSION['utilisateur_id'];
$service = new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']));

if (!$service->marquerLue($id, $utilisateurId)) {
    reponseJson(404, ['ok' => false, 'message' => 'Notification introuvable.']);
}

reponseJson(200, ['ok' => true] + $service->resume($utilisateurId));
