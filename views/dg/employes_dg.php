<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['dg']);
require_once __DIR__ . '/donnees_demo_dg.php';

$espace = 'dg';
$active = 'dg_employes';
$pageTitle = 'Employés';
$sousTitre = $dossiers['total'] . ' dossiers conservés • ' . $dossiers['actif'] . ' actifs • ' . $dossiers['suspendu'] . ' suspendu • ' . $dossiers['sorti'] . ' sorti • Consultation';
$scripts = ['gestion.js'];

$ficheUrl = 'employe_fiche_dg.php';
$creerUrl = 'compte_creation_dg.php';
$listeUrl = 'employes_dg.php';

include __DIR__ . '/../layout/debut_page.php';
include __DIR__ . '/../gestion/corps_employes.php';
include __DIR__ . '/../layout/fin_page.php';
