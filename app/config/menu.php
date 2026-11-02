<?php
declare(strict_types=1);

$espaceEmploye = ['titre' => 'Mon espace employé', 'items' => [
    ['Accueil',            '/employe',               true],
    ['Mes congés',         '/employe/conges',        true],
    ['Mes demandes',       '/employe/demandes',      true],
    ['Mes fiches de paie', '/employe/fiches',        true],
    ['Mon coffre-fort',    '/employe/documents',     true],
    ['Mon dossier',        '/employe/dossier',       true],
    ['Organigramme',       '/employe/organigramme',  true],
    ['Notifications',      '/notifications',         true],
]];

return [
    'EMPLOYE' => [$espaceEmploye],
    'CHEF_DEPARTEMENT' => [
        ['titre' => 'Mon équipe', 'items' => [
            ['Validations', '/chef',            true],
            ['Congés',      '/chef/conges',     true],
            ['Absences',    '/chef/absences',   true],
            ['Calendrier',  '/chef/calendrier', true],
        ]],
        ['titre' => 'Mon espace employé', 'items' => [
            ['Mon espace', '/employe', true],
            ['Mes congés', '/employe/conges', true],
            ['Mes demandes', '/employe/demandes', true],
            ['Mon dossier', '/employe/dossier', true],
            ['Notifications', '/notifications', true],
        ]],
    ],
    'RESPONSABLE_RH' => [
        ['titre' => 'Gestion RH', 'items' => [
            ['Tableau de bord',        '/rh',              true],
            ['Employés',               '/rh/employes',     true],
            ['Départements et postes', '/rh/organisation', true],
            ['Congés',                 '/rh/conges',       true],
            ['Heures supp.',           '/rh/heures-supp',  true],
            ['Paie',                   '/rh/paie',         true],
            ['Demandes',               '/rh/demandes',     true],
            ['Paramètres',             '/rh/parametres',   true],
        ]],
        ['titre' => 'Mon espace employé', 'items' => [
            ['Mon espace', '/employe', true],
            ['Mes congés', '/employe/conges', true],
            ['Mes demandes', '/employe/demandes', true],
            ['Mon dossier', '/employe/dossier', true],
            ['Notifications', '/notifications', true],
        ]],
    ],
    'DIRECTEUR_GENERAL' => [
        ['titre' => 'Direction', 'items' => [
            ['Tableau de bord',     '/direction',          true],
            ['Comptes RH',          '/direction/comptes',  true],
            ['Congés RH',           '/direction/conges',   true],
            ['Absences Direction',  '/direction/absences', true],
            ['Journal des actions', '/direction/journal',  true],
            ['Notifications',       '/notifications',      true],
        ]],
    ],
];
