<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../services/MotDePasseService.php';

header('Content-Type: application/json; charset=utf-8');

function repondre(int $code, array $donnees): never
{
    http_response_code($code);
    echo json_encode($donnees, JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    repondre(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
}

csrfExiger();

$email = $_POST['email'] ?? ($_SESSION['reset_email'] ?? '');
if (!is_string($email) || trim($email) === '' || !filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
    repondre(400, ['ok' => false, 'message' => 'Adresse e-mail invalide.']);
}

$service = new MotDePasseService(
    new Utilisateur($pdo),
    new JetonReinitialisation($pdo),
    new MailService($cfg['mail']),
    new JournalService(new JournalAction($pdo))
);
$resultat = $service->demanderCode($email);

$_SESSION['reset_email'] = trim($email);
unset($_SESSION['reset_user_id'], $_SESSION['reset_jeton']);

repondre(200, ['ok' => true, 'message' => $resultat['message']]);
