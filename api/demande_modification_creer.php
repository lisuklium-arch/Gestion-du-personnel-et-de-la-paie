<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/DemandeModificationService.php';

exigerRole(['employe', 'chef', 'rh'], true);
exigerPost();

$service = new DemandeModificationService(
    $pdo,
    new DemandeModification($pdo),
    new Employe($pdo),
    new JournalService(new JournalAction($pdo)),
    new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']))
);
$resultat = $service->creerPourUtilisateur((int) $_SESSION['utilisateur_id'], $_POST);
reponseJson($resultat['ok'] ? 201 : 422, $resultat);
