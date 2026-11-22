<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/NotificationService.php';

exigerConnexion(true);
exigerPost();

$utilisateurId = (int) $_SESSION['utilisateur_id'];
$service = new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']));
$service->toutesLues($utilisateurId);

reponseJson(200, ['ok' => true] + $service->resume($utilisateurId));
