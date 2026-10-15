<?php

require __DIR__ . '/nav_items.php';

$cdBase = '/views/cd/';

$cdItems = [
    'equipe'     => ['label' => 'Mon équipe',             'short' => 'Équipe',     'icon' => 'fa-solid fa-users',           'url' => $cdBase . 'equipe_cd.php'],
    'heures'     => ['label' => 'Heures supplémentaires', 'short' => 'Heures',     'icon' => 'fa-regular fa-clock',         'url' => $cdBase . 'heures_sup_cd.php'],
    'avis'       => ['label' => 'Avis sur un congé',      'short' => 'Avis',       'icon' => 'fa-regular fa-square-check',  'url' => $cdBase . 'avis_conges_cd.php'],
    'calendrier' => ['label' => 'Calendrier d’équipe',    'short' => 'Calendrier', 'icon' => 'fa-regular fa-calendar-days', 'url' => $cdBase . 'calendrier_equipe_cd.php'],
];

$cdBottom = array_keys($cdItems);
