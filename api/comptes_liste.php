<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/CompteService.php';

exigerRole(['rh', 'dg'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    reponseJson(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
}

$role = is_string($_GET['role'] ?? null) ? $_GET['role'] : null;
$statut = is_string($_GET['statut'] ?? null) ? $_GET['statut'] : null;

$service = new CompteService(new Utilisateur($pdo), new MailService($cfg['mail']), new JournalService(new JournalAction($pdo)));

reponseJson(200, ['ok' => true, 'comptes' => $service->lister(auteurCourant(), $role, $statut)]);
