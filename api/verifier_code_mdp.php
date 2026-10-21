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

$email = $_SESSION['reset_email'] ?? '';
$code  = $_POST['code'] ?? '';

if ($email === '') {
    repondre(400, ['ok' => false, 'message' => 'Session expirée. Recommencez la procédure.', 'redirection' => 'mot_de_passe_oublie.php']);
}
if (!is_string($code) || $code === '') {
    repondre(400, ['ok' => false, 'message' => 'Saisissez le code reçu par e-mail.']);
}

$service = new MotDePasseService(
    new Utilisateur($pdo),
    new JetonReinitialisation($pdo),
    new MailService($cfg['mail']),
    new JournalService(new JournalAction($pdo))
);
$resultat = $service->verifierCode($email, $code);

if (!$resultat['ok']) {
    repondre(400, ['ok' => false, 'message' => $resultat['message']]);
}

$_SESSION['reset_user_id'] = $resultat['utilisateur_id'];
$_SESSION['reset_jeton']   = $resultat['jeton'];

repondre(200, ['ok' => true, 'redirection' => 'mot_de_passe_nouveau.php']);
