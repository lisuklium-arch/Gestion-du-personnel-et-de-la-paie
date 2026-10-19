<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['rh']);
require_once __DIR__ . '/donnees_demo_rh.php';

$espace = 'rh';
$active = 'rh_employes';
$pageTitle = 'Employés';
$sousTitre = $dossiers['total'] . ' dossiers conservés • ' . $dossiers['actif'] . ' actifs • ' . $dossiers['suspendu'] . ' suspendu • ' . $dossiers['sorti'] . ' sorti';
$scripts = ['gestion.js'];

$ficheUrl = 'employe_fiche_rh.php';
$creerUrl = 'compte_creation_rh.php';
$listeUrl = 'employes_rh.php';

include __DIR__ . '/../layout/debut_page.php';
include __DIR__ . '/../gestion/corps_employes.php';
include __DIR__ . '/../layout/fin_page.php';
