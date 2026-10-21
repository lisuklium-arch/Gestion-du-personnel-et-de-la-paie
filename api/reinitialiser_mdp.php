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

$utilisateurId = $_SESSION['reset_user_id'] ?? null;
$jeton         = $_SESSION['reset_jeton'] ?? null;
if ($utilisateurId === null || $jeton === null) {
    repondre(403, ['ok' => false, 'message' => 'Vérification du code requise.', 'redirection' => 'mot_de_passe_oublie.php']);
}

$nouveau      = $_POST['nouveau_mot_de_passe'] ?? '';
$confirmation = $_POST['confirmation'] ?? '';
if (!is_string($nouveau) || !is_string($confirmation) || $nouveau === '' || $confirmation === '') {
    repondre(400, ['ok' => false, 'message' => 'Tous les champs sont requis.']);
}

$service = new MotDePasseService(
    new Utilisateur($pdo),
    new JetonReinitialisation($pdo),
    new MailService($cfg['mail']),
    new JournalService(new JournalAction($pdo))
);
$resultat = $service->changerMotDePasse((int) $utilisateurId, $jeton, $nouveau, $confirmation);

if (!$resultat['ok']) {
    if (!empty($resultat['recommencer'])) {
        unset($_SESSION['reset_user_id'], $_SESSION['reset_jeton'], $_SESSION['reset_email']);
        repondre(410, ['ok' => false, 'message' => $resultat['message'], 'redirection' => 'mot_de_passe_oublie.php']);
    }
    repondre(400, ['ok' => false, 'message' => $resultat['message']]);
}

unset($_SESSION['reset_user_id'], $_SESSION['reset_jeton'], $_SESSION['reset_email']);

repondre(200, ['ok' => true, 'message' => $resultat['message'], 'redirection' => PAGE_CONNEXION]);
