<?php
declare(strict_types=1);

// Menus latéraux par profil (d'après les maquettes). 3e valeur : true = page déjà créée, false = à venir.
$espaceEmploye = ['titre' => 'Mon espace employé', 'items' => [
    ['Accueil',            '/employe',               true],
    ['Mes congés',         '/employe/conges',        false],
    ['Mes fiches de paie', '/employe/fiches',        false],
    ['Mon coffre-fort',    '/employe/documents',     false],
    ['Mon dossier',        '/employe/dossier',       false],
    ['Organigramme',       '/employe/organigramme',  false],
    ['Notifications',      '/employe/notifications', false],
]];

return [
    'EMPLOYE' => [$espaceEmploye],
    'CHEF_DEPARTEMENT' => [
        ['titre' => 'Mon équipe', 'items' => [
            ['Validations', '/chef',            true],
            ['Présence',    '/chef/presence',   false],
            ['Calendrier',  '/chef/calendrier', false],
        ]],
        ['titre' => 'Mon espace employé', 'items' => [['Mon espace', '/employe', true]]],
    ],
    'RESPONSABLE_RH' => [
        ['titre' => 'Gestion RH', 'items' => [
            ['Tableau de bord',        '/rh',              true],
            ['Employés',               '/rh/employes',     true],
            ['Départements et postes', '/rh/organisation', true],
            ['Congés',                 '/rh/conges',       false],
            ['Heures supp.',           '/rh/heures-supp',  false],
            ['Paie',                   '/rh/paie',         false],
            ['Demandes',               '/rh/demandes',     false],
            ['Paramètres',             '/rh/parametres',   false],
        ]],
        ['titre' => 'Mon espace employé', 'items' => [['Mon espace', '/employe', true]]],
    ],
    'DIRECTEUR_GENERAL' => [
        ['titre' => 'Direction', 'items' => [
            ['Tableau de bord global', '/direction',         true],
            ['Comptes RH',             '/direction/comptes', false],
            ['Congés RH',              '/direction/conges',  false],
            ['Journal des actions',    '/direction/journal', false],
        ]],
    ],
];
