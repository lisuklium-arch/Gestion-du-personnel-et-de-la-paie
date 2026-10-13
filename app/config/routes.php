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
    ['method' => 'GET',  'path' => '/direction', 'action' => 'DashboardController@direction', 'middleware' => ['auth'], 'roles' => ['DIRECTEUR_GENERAL']],
];
