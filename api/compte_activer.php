<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/CompteService.php';

exigerRole(['dg'], true);
exigerPost();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    reponseJson(400, ['ok' => false, 'message' => 'Compte invalide.']);
}

$commentaire = is_string($_POST['commentaire'] ?? null) ? trim($_POST['commentaire']) : '';
if (mb_strlen($commentaire) > 500) {
    reponseJson(400, ['ok' => false, 'message' => 'Commentaire trop long (500 caractères maximum).']);
}

$service = new CompteService(new Utilisateur($pdo), new MailService($cfg['mail']), new JournalService(new JournalAction($pdo)), new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail'])));
$resultat = $service->activer(auteurCourant(), $id, $commentaire === '' ? null : $commentaire);

reponseJson($resultat['ok'] ? 200 : 400, $resultat);
