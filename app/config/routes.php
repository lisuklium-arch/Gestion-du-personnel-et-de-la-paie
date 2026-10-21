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
];
