<?php

require __DIR__ . '/nav_items.php';

$rhBase = '/views/rh/';

$rhItems = [
    'rh_tableau'    => ['label' => 'Tableau de bord',     'short' => 'Accueil',   'icon' => 'fa-solid fa-table-columns',     'url' => $rhBase . 'tableau_bord_rh.php'],
    'rh_demandes'   => ['label' => 'Demandes à traiter',  'short' => 'Demandes',  'icon' => 'fa-solid fa-inbox',             'url' => $rhBase . 'demandes_rh.php', 'badge' => 8],
    'rh_employes'   => ['label' => 'Employés',            'short' => 'Employés',  'icon' => 'fa-solid fa-users',             'url' => $rhBase . 'employes_rh.php'],
    'rh_compte'     => ['label' => 'Création de compte',  'short' => 'Compte',    'icon' => 'fa-solid fa-user-plus',         'url' => $rhBase . 'compte_creation_rh.php'],
    'rh_paie'       => ['label' => 'Paie',                'short' => 'Paie',      'icon' => 'fa-solid fa-wallet',            'url' => $rhBase . 'paie_rh.php'],
    'rh_parametres' => ['label' => 'Paramètres',          'short' => 'Réglages',  'icon' => 'fa-solid fa-gear',              'url' => $rhBase . 'parametres_rh.php'],
    'rh_journal'    => ['label' => 'Journal',             'short' => 'Journal',   'icon' => 'fa-solid fa-clock-rotate-left', 'url' => $rhBase . 'journal_rh.php'],
];

$rhBottom = ['rh_tableau', 'rh_demandes', 'rh_employes', 'rh_paie'];
