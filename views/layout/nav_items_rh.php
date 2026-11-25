<?php

require __DIR__ . '/nav_items.php';

$rhBase = '/views/rh/';

/** Entrées du menu principal RH (hors sous-menu Congés). */
$rhItems = [
    'rh_tableau'    => ['label' => 'Tableau de bord',    'short' => 'Accueil',  'icon' => 'fa-solid fa-table-columns', 'url' => $rhBase . 'tableau_bord_rh.php'],
    'rh_employes'   => ['label' => 'Employés',           'short' => 'Employés', 'icon' => 'fa-solid fa-users',         'url' => $rhBase . 'employes_rh.php'],
    'rh_compte'     => ['label' => 'Création de compte', 'short' => 'Compte',   'icon' => 'fa-solid fa-user-plus',     'url' => $rhBase . 'compte_creation_rh.php'],
    'rh_paie'       => ['label' => 'Paie',               'short' => 'Paie',     'icon' => 'fa-solid fa-wallet',        'url' => $rhBase . 'paie_rh.php'],
    'rh_parametres' => ['label' => 'Paramètres',         'short' => 'Réglages', 'icon' => 'fa-solid fa-gear',          'url' => $rhBase . 'parametres_rh.php'],
    'rh_journal'    => ['label' => 'Journal',            'short' => 'Journal',  'icon' => 'fa-solid fa-clock-rotate-left', 'url' => $rhBase . 'journal_rh.php'],
];

/**
 * Sous-menu « Congés » (domaine C) — une seule entrée dans la barre principale.
 * Clés utilisées aussi comme $active dans les pages du domaine.
 */
$rhCongesItems = [
    'rh_conges'           => ['label' => 'Décisions de congés',  'short' => 'Décisions', 'icon' => 'fa-regular fa-calendar',   'url' => $rhBase . 'conges_rh.php'],
    'rh_demandes'         => ['label' => 'Demandes à traiter',   'short' => 'Demandes',  'icon' => 'fa-solid fa-inbox',        'url' => $rhBase . 'demandes_rh.php'],
    'rh_pilotage_conges'  => ['label' => 'Pilotage',             'short' => 'Pilotage',  'icon' => 'fa-solid fa-chart-line',   'url' => $rhBase . 'pilotage_conges_rh.php'],
    'rh_types_conge'      => ['label' => 'Types de congés',      'short' => 'Types',     'icon' => 'fa-solid fa-tags',         'url' => $rhBase . 'types_conge_rh.php'],
    'rh_politiques_conge' => ['label' => 'Politiques',           'short' => 'Règles',    'icon' => 'fa-solid fa-sliders',      'url' => $rhBase . 'politiques_conge_rh.php'],
    'rh_soldes_annee'     => ['label' => 'Soldes / années',      'short' => 'Soldes',    'icon' => 'fa-solid fa-calendar-plus','url' => $rhBase . 'soldes_annee_rh.php'],
];

/** Barre du bas (mobile) : peu d’entrées. */
$rhBottom = ['rh_tableau', 'rh_conges', 'rh_employes', 'rh_paie'];
