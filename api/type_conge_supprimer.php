<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/ReferentielService.php';

exigerRole(['rh'], true);
exigerPost();

$id = filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false) {
    reponseJson(400, ['ok' => false, 'message' => 'Élément invalide.']);
}

$service = new ReferentielService(new Referentiel($pdo), new JournalService(new JournalAction($pdo)));
$resultat = $service->supprimerTypeConge(auteurCourant()['id'], $id);

reponseJson($resultat['ok'] ? 200 : 422, $resultat);
