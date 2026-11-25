<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['rh']);
$espace = 'rh'; $active = 'rh_employes'; $scripts = ['gestion.js'];
$listeUrl = 'employes_rh.php';
require __DIR__ . '/../gestion/fiche_employe_reelle.php';
