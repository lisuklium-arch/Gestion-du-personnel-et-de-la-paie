<?php
declare(strict_types=1);

// Table des routes : méthode HTTP + adresse => Contrôleur@méthode.
//  'middleware' : 'guest' = réservé aux visiteurs non connectés ; 'auth' = réservé aux utilisateurs connectés.
//  'roles'      : profils autorisés (le chef de département et le responsable RH héritent aussi du profil employé).
// Toute requête POST est automatiquement protégée par le jeton CSRF (voir Router).
return [
    ['method' => 'GET',  'path' => '/',          'action' => 'DashboardController@index',     'middleware' => ['auth']],
    ['method' => 'GET',  'path' => '/login',     'action' => 'AuthController@showLogin',      'middleware' => ['guest']],
    ['method' => 'POST', 'path' => '/login',     'action' => 'AuthController@login',          'middleware' => ['guest']],
    ['method' => 'POST', 'path' => '/logout',    'action' => 'AuthController@logout',         'middleware' => ['auth']],

    ['method' => 'GET',  'path' => '/employe',   'action' => 'DashboardController@employe',   'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'GET',  'path' => '/chef',      'action' => 'DashboardController@chef',      'middleware' => ['auth'], 'roles' => ['CHEF_DEPARTEMENT']],
    ['method' => 'GET',  'path' => '/rh',        'action' => 'DashboardController@rh',        'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    // Gestion des employés (Responsable RH)
    ['method' => 'GET',  'path' => '/rh/employes',            'action' => 'EmployeController@index',       'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/employes/recherche',  'action' => 'EmployeController@recherche',   'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/employes/nouveau',    'action' => 'EmployeController@nouveau',     'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/employes',            'action' => 'EmployeController@creer',       'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/employes/voir',       'action' => 'EmployeController@voir',        'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/employes/modifier',   'action' => 'EmployeController@modifier',    'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/employes/modifier',   'action' => 'EmployeController@mettreAJour', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],

    // Départements, postes et contrats (Responsable RH)
    ['method' => 'GET', 'path' => '/rh/organisation', 'action' => 'OrganisationController@index', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET', 'path' => '/rh/departements/nouveau', 'action' => 'OrganisationController@departementNouveau', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/departements', 'action' => 'OrganisationController@departementCreer', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET', 'path' => '/rh/departements/modifier', 'action' => 'OrganisationController@departementModifier', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/departements/modifier', 'action' => 'OrganisationController@departementMettreAJour', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/departements/supprimer', 'action' => 'OrganisationController@departementSupprimer', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET', 'path' => '/rh/postes/nouveau', 'action' => 'OrganisationController@posteNouveau', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/postes', 'action' => 'OrganisationController@posteCreer', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET', 'path' => '/rh/postes/modifier', 'action' => 'OrganisationController@posteModifier', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/postes/modifier', 'action' => 'OrganisationController@posteMettreAJour', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/postes/supprimer', 'action' => 'OrganisationController@posteSupprimer', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET', 'path' => '/rh/contrats/nouveau', 'action' => 'OrganisationController@contratNouveau', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/contrats', 'action' => 'OrganisationController@contratCreer', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],

    ['method' => 'GET',  'path' => '/direction', 'action' => 'DashboardController@direction', 'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],

    ['method' => 'GET',  'path' => '/employe/conges',              'action' => 'CongeController@mesConges',    'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'GET',  'path' => '/employe/conges/nouvelle',     'action' => 'CongeController@nouvelle',     'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'POST', 'path' => '/employe/conges',              'action' => 'CongeController@deposer',      'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'GET',  'path' => '/employe/conges/calcul',       'action' => 'CongeController@calcul',       'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'GET',  'path' => '/employe/conges/voir',         'action' => 'CongeController@voirEmploye',  'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'POST', 'path' => '/employe/conges/annuler',      'action' => 'CongeController@annuler',      'middleware' => ['auth'], 'roles' => ['EMPLOYE']],

    ['method' => 'GET',  'path' => '/chef/conges',                 'action' => 'CongeController@fileChef',     'middleware' => ['auth'], 'roles' => ['CHEF_DEPARTEMENT']],
    ['method' => 'GET',  'path' => '/chef/conges/voir',            'action' => 'CongeController@voirChef',     'middleware' => ['auth'], 'roles' => ['CHEF_DEPARTEMENT']],
    ['method' => 'POST', 'path' => '/chef/conges/avis',            'action' => 'CongeController@avis',         'middleware' => ['auth'], 'roles' => ['CHEF_DEPARTEMENT']],
    ['method' => 'GET',  'path' => '/chef/absences',               'action' => 'AbsenceController@index',      'middleware' => ['auth'], 'roles' => ['CHEF_DEPARTEMENT']],
    ['method' => 'POST', 'path' => '/chef/absences',               'action' => 'AbsenceController@marquer',    'middleware' => ['auth'], 'roles' => ['CHEF_DEPARTEMENT']],
    ['method' => 'GET',  'path' => '/chef/calendrier',             'action' => 'CongeController@calendrier',   'middleware' => ['auth'], 'roles' => ['CHEF_DEPARTEMENT']],

    ['method' => 'GET',  'path' => '/rh/conges',                   'action' => 'CongeController@fileRh',       'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/conges/voir',              'action' => 'CongeController@voirRh',       'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/conges/decision',          'action' => 'CongeController@decisionRh',   'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/conges/annuler',           'action' => 'CongeController@annulerRh',    'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],

    ['method' => 'GET',  'path' => '/direction/conges',            'action' => 'CongeController@fileDg',       'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
    ['method' => 'GET',  'path' => '/direction/conges/voir',       'action' => 'CongeController@voirDg',       'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
    ['method' => 'POST', 'path' => '/direction/conges/decision',   'action' => 'CongeController@decisionDg',   'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
    ['method' => 'GET',  'path' => '/direction/absences',          'action' => 'AbsenceController@index',      'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
    ['method' => 'POST', 'path' => '/direction/absences',          'action' => 'AbsenceController@marquer',    'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],

    ['method' => 'GET',  'path' => '/conges/justificatif',         'action' => 'CongeController@justificatif', 'middleware' => ['auth']],
    ['method' => 'GET',  'path' => '/notifications',               'action' => 'NotificationController@index', 'middleware' => ['auth']],
    ['method' => 'POST', 'path' => '/notifications/lire',          'action' => 'NotificationController@lire',  'middleware' => ['auth']],

    ['method' => 'GET',  'path' => '/direction', 'action' => 'DashboardController@direction', 'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],


    // Heures supplémentaires (RH)
    ['method' => 'GET',  'path' => '/rh/heures-supp',          'action' => 'HeureSupController@index',   'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/heures-supp/nouvelle', 'action' => 'HeureSupController@nouveau',  'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/heures-supp',          'action' => 'HeureSupController@creer',    'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/heures-supp/decision', 'action' => 'HeureSupController@decision', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],

    // Paie (RH)
    ['method' => 'GET',  'path' => '/rh/paie',         'action' => 'PaieController@index',   'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/paie/voir',    'action' => 'PaieController@voir',    'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/paie/generer', 'action' => 'PaieController@generer', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/paie/statut',  'action' => 'PaieController@statut',  'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],

    // Demandes RH + employé
    ['method' => 'GET',  'path' => '/rh/demandes',              'action' => 'DemandeController@index',                'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/demandes/attestation',  'action' => 'DemandeController@traiterAttestation',  'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/demandes/modification','action' => 'DemandeController@traiterModification', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/demandes/avance',       'action' => 'DemandeController@traiterAvance',       'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/employe/demandes',              'action' => 'DemandeController@monEspace',           'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'POST', 'path' => '/employe/demandes/attestation',  'action' => 'DemandeController@deposerAttestation',  'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'POST', 'path' => '/employe/demandes/modification', 'action' => 'DemandeController@deposerModification', 'middleware' => ['auth'], 'roles' => ['EMPLOYE']],

    // Paramètres RH
    ['method' => 'GET',  'path' => '/rh/parametres',                'action' => 'ParametreController@index',          'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/parametres/paie',           'action' => 'ParametreController@majPaie',        'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/parametres/ferie',          'action' => 'ParametreController@ajouterFerie',   'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/parametres/ferie/supprimer','action' => 'ParametreController@supprimerFerie', 'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'POST', 'path' => '/rh/parametres/type-conge',     'action' => 'ParametreController@basculerType',   'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],

    // Direction
    ['method' => 'GET',  'path' => '/direction/comptes',          'action' => 'DirectionController@comptes',     'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
    ['method' => 'POST', 'path' => '/direction/comptes',          'action' => 'DirectionController@creerCompte', 'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
    ['method' => 'POST', 'path' => '/direction/comptes/basculer', 'action' => 'DirectionController@basculer',    'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
    ['method' => 'GET',  'path' => '/direction/journal',          'action' => 'DirectionController@journal',     'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],

    ['method' => 'GET',  'path' => '/employe/fiches/export',     'action' => 'ExportController@ficheEmploye',  'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'GET',  'path' => '/employe/dossier/export',    'action' => 'ExportController@dossier',       'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
    ['method' => 'GET',  'path' => '/rh/paie/export',            'action' => 'ExportController@ficheRh',       'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/rh/attestations/export',    'action' => 'ExportController@attestation',   'middleware' => ['auth'], 'roles' => ['RESPONSABLE_RH']],
    ['method' => 'GET',  'path' => '/employe/attestations/export','action' => 'ExportController@attestation',  'middleware' => ['auth'], 'roles' => ['EMPLOYE']],
];


