<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/NouvelEmployeService.php';

exigerRole(['rh', 'dg'], true);
exigerPost();

$journal = new JournalService(new JournalAction($pdo));
$service = new NouvelEmployeService(
    $pdo,
    new CompteService(new Utilisateur($pdo), new MailService($cfg['mail']), $journal, new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']))),
    new Employe($pdo),
    new Contrat($pdo),
    $journal,
    __DIR__ . '/../storage/contrats'
);

$resultat = $service->creer(auteurCourant(), $_POST, $_FILES['contrat_signe'] ?? null);

if (!$resultat['ok']) {
    reponseJson(400, ['ok' => false, 'message' => $resultat['message']]);
}

reponseJson(201, [
    'ok' => true,
    'message' => $resultat['message'],
    'utilisateur_id' => $resultat['utilisateur_id'],
    'employe_id' => $resultat['employe_id'],
    'statut' => $resultat['statut'],
]);
