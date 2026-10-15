<?php

$active = $active ?? '';

$employeBase = '/views/employe/';

$logoutUrl = '/public/login.html';

$navItems = [
    'accueil'      => ['label' => 'Accueil',        'short' => 'Accueil', 'icon' => 'fa-solid fa-house',               'file' => 'accueil_employe.php'],
    'conges'       => ['label' => 'Congés',         'short' => 'Congés',  'icon' => 'fa-regular fa-calendar',          'file' => 'conges_employe.php'],
    'paie'         => ['label' => 'Fiches de paie', 'short' => 'Paie',    'icon' => 'fa-solid fa-file-invoice-dollar', 'file' => 'fiches_paie_employe.php'],
    'avances'      => ['label' => 'Avances',        'short' => 'Avances', 'icon' => 'fa-solid fa-hand-holding-dollar', 'file' => 'avances_employe.php'],
    'documents'    => ['label' => 'Documents',      'short' => 'Documents', 'icon' => 'fa-regular fa-file-lines',      'file' => 'documents_employe.php'],
    'profil'       => ['label' => 'Profil',         'short' => 'Profil',  'icon' => 'fa-regular fa-user',              'file' => 'profil_employe.php'],
    'organigramme' => ['label' => 'Organigramme',   'short' => 'Organigramme', 'icon' => 'fa-solid fa-sitemap',        'file' => 'organigramme_employe.php'],
];

$navBottom = ['accueil', 'conges', 'paie', 'profil'];
$navPlus   = ['avances', 'documents', 'organigramme'];

$role = $_COOKIE['perpay_role'] ?? '';
