<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/ReferentielService.php';

exigerRole(['rh'], true);
exigerPost();

$service = new ReferentielService(new Referentiel($pdo), new JournalService(new JournalAction($pdo)));
$resultat = $service->enregistrerDepartement(auteurCourant()['id'], $_POST);

reponseJson($resultat['ok'] ? 200 : 422, $resultat);
