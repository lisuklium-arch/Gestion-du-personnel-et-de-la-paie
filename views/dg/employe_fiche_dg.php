<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['dg']);
$espace = 'dg'; $active = 'dg_employes'; $scripts = ['gestion.js'];
$listeUrl = 'employes_dg.php';
require __DIR__ . '/../gestion/fiche_employe_reelle.php';
