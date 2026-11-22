<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/ParametreSystemeService.php';

exigerRole(['rh'], true);
exigerPost();

$cle = is_string($_POST['cle'] ?? null) ? trim($_POST['cle']) : '';
if ($cle === '' || strlen($cle) > 60) {
    reponseJson(400, ['ok' => false, 'message' => 'Paramètre invalide.']);
}

$service = new ParametreSystemeService(new ParametreSysteme($pdo), new JournalService(new JournalAction($pdo)));
$resultat = $service->modifier(auteurCourant()['id'], $cle, $_POST['valeur'] ?? '');

reponseJson($resultat['ok'] ? 200 : 422, $resultat);
