<?php
require_once __DIR__ . '/donnees_demo_dg.php';

$espace = 'dg';
$active = 'dg_compte';
$pageTitle = 'Création de compte';
$sousTitre = 'Nouveau dossier employé et compte de connexion • Formulaire non encore enregistré';
$scripts = ['forms.js', 'gestion.js'];

$modeCompte = 'dg';
$listeUrl = 'employes_dg.php';

include __DIR__ . '/../layout/debut_page.php';
include __DIR__ . '/../gestion/corps_compte.php';
include __DIR__ . '/../layout/fin_page.php';
