<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../services/MotDePasseService.php';
require_once __DIR__ . '/../config/api.php';

exigerConnexion(true);
exigerPost();

$actuel       = $_POST['mot_de_passe_actuel'] ?? '';
$nouveau      = $_POST['nouveau_mot_de_passe'] ?? '';
$confirmation = $_POST['confirmation'] ?? '';
if (!is_string($actuel) || !is_string($nouveau) || !is_string($confirmation)
    || $actuel === '' || $nouveau === '' || $confirmation === '') {
    reponseJson(400, ['ok' => false, 'message' => 'Tous les champs sont requis.']);
}

$service = new MotDePasseService(
    new Utilisateur($pdo),
    new JetonReinitialisation($pdo),
    new MailService($cfg['mail']),
    new JournalService(new JournalAction($pdo)),
    new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']))
);
$resultat = $service->changerMotDePasseTemporaire((int) $_SESSION['utilisateur_id'], $actuel, $nouveau, $confirmation);

if (!$resultat['ok']) {
    reponseJson(400, ['ok' => false, 'message' => $resultat['message']]);
}

$_SESSION['doit_changer_mdp'] = false;
reponseJson(200, [
    'ok' => true,
    'message' => $resultat['message'],
    'redirection' => accueilPourRoleCourant() ?? PAGE_CONNEXION,
]);
