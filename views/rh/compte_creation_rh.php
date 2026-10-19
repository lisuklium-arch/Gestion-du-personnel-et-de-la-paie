<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['rh']);
require_once __DIR__ . '/donnees_demo_rh.php';

$espace = 'rh';
$active = 'rh_compte';
$pageTitle = 'Création de compte';
$sousTitre = 'Nouveau dossier employé et compte de connexion • Formulaire non encore enregistré';
$scripts = ['forms.js', 'gestion.js'];

$modeCompte = 'rh';
$listeUrl = 'employes_rh.php';

include __DIR__ . '/../layout/debut_page.php';
include __DIR__ . '/../gestion/corps_compte.php';
include __DIR__ . '/../layout/fin_page.php';
