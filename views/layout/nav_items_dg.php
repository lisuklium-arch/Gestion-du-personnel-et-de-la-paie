<?php

$active = $active ?? '';
$logoutUrl = $logoutUrl ?? '/public/logout.php';

$dgBase = '/views/dg/';

$dgItems = [
    'dg_tableau'    => ['label' => 'Tableau de bord global', 'short' => 'Accueil',  'icon' => 'fa-solid fa-table-columns',     'url' => $dgBase . 'tableau_bord_dg.php'],
    'dg_employes'   => ['label' => 'Employés',               'short' => 'Employés', 'icon' => 'fa-solid fa-users',             'url' => $dgBase . 'employes_dg.php'],
    'dg_compte'     => ['label' => 'Création de compte',     'short' => 'Compte',   'icon' => 'fa-solid fa-user-plus',         'url' => $dgBase . 'compte_creation_dg.php'],
    'dg_comptes_rh' => ['label' => 'Comptes RH',             'short' => 'Comptes',  'icon' => 'fa-solid fa-user-shield',       'url' => $dgBase . 'comptes_rh_dg.php', 'badge' => 1],
    'dg_conges_rh'  => ['label' => 'Congés des RH',          'short' => 'Congés',   'icon' => 'fa-regular fa-calendar',        'url' => $dgBase . 'conges_rh_dg.php', 'badge' => 1],
    'dg_journal'    => ['label' => 'Journal',                'short' => 'Journal',  'icon' => 'fa-solid fa-clock-rotate-left', 'url' => $dgBase . 'journal_dg.php'],
];

$dgBottom = ['dg_tableau', 'dg_employes', 'dg_comptes_rh', 'dg_conges_rh'];
