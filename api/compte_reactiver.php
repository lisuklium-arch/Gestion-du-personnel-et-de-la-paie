<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/CompteService.php';

exigerRole(['rh', 'dg'], true);
exigerPost();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    reponseJson(400, ['ok' => false, 'message' => 'Compte invalide.']);
}

$service = new CompteService(new Utilisateur($pdo), new MailService($cfg['mail']), new JournalService(new JournalAction($pdo)), new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail'])));
$resultat = $service->reactiver(auteurCourant(), $id);

reponseJson($resultat['ok'] ? 200 : 400, $resultat);
