<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/MatriculeService.php';

exigerRole(['rh'], true);
exigerPost();

$employeId = filter_var($_POST['employe_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$matricule = is_string($_POST['matricule'] ?? null) ? $_POST['matricule'] : '';
if ($employeId === false) {
    reponseJson(422, ['ok' => false, 'message' => 'Dossier employé invalide.']);
}

$journal = new JournalService(new JournalAction($pdo));
$service = new MatriculeService($pdo, new Employe($pdo), $journal);
$resultat = $service->corriger((int) $_SESSION['utilisateur_id'], $employeId, $matricule);
reponseJson($resultat['ok'] ? 200 : 422, $resultat);
