<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../services/DemandeModificationService.php';

exigerRole(['rh'], true);
exigerPost();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$decision = is_string($_POST['decision'] ?? null) ? $_POST['decision'] : '';
if ($id === false) {
    reponseJson(422, ['ok' => false, 'message' => 'Demande invalide.']);
}

$service = new DemandeModificationService(
    $pdo,
    new DemandeModification($pdo),
    new Employe($pdo),
    new Utilisateur($pdo),
    new JournalService(new JournalAction($pdo)),
    new NotificationService(new Notification($pdo), new Utilisateur($pdo), new MailService($cfg['mail']))
);
$resultat = $service->traiter((int) $_SESSION['utilisateur_id'], $id, $decision, $_POST['motif_rejet'] ?? null);
reponseJson($resultat['ok'] ? 200 : 422, $resultat);
