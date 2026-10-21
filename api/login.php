<?php
// Endpoint de connexion : reçoit email + password (POST), répond en JSON.

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../services/AuthService.php';

header('Content-Type: application/json; charset=utf-8');

function repondre(int $code, array $donnees): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

// 1. Seule la méthode POST est acceptée
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    repondre(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
}

// 2. Protection CSRF
csrfExiger();

// 3. Lecture et contrôle de l'entrée
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!is_string($email) || !is_string($password) || trim($email) === '' || $password === '') {
    repondre(400, ['ok' => false, 'message' => 'E-mail et mot de passe requis.']);
}

// 4. Authentification (règle métier dans le service)
$auth     =    new AuthService(new Utilisateur($pdo), new Employe($pdo), new JournalService(new JournalAction($pdo)));
$resultat = $auth->connecter($email, $password);

if (!$resultat['ok']) {
    repondre(401, ['ok' => false, 'message' => $resultat['message']]);
}

// 5. Page d'accueil selon le rôle (table définie dans config/auth.php)
repondre(200, [
    'ok'               => true,
    'role'             => $resultat['role'],
    'redirection'      => accueilPourRole($resultat['role']) ?? PAGE_CONNEXION,
    'doit_changer_mdp' => $resultat['doit_changer_mdp'],
]);
