<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['dg']);
require_once __DIR__ . '/../gestion/aides_gestion.php';
require_once __DIR__ . '/../../models/Utilisateur.php';

$espace = 'dg';
$active = 'dg_employes';
$pageTitle = 'Employés';
$dossiers = (new Utilisateur($pdo))->compterDossiers();
$sousTitre = $dossiers['total'] . ' dossiers conservés • ' . $dossiers['actif'] . ' actifs • ' . $dossiers['suspendu'] . ' suspendu • ' . $dossiers['sorti'] . ' sorti';
$scripts = ['gestion.js'];

$ficheUrl = 'employe_fiche_dg.php';
$creerUrl = 'compte_creation_dg.php';
$listeUrl = 'employes_dg.php';

include __DIR__ . '/../layout/debut_page.php';
include __DIR__ . '/../gestion/corps_employes.php';
include __DIR__ . '/../layout/fin_page.php';
