<?php
// Déconnexion : détruit la session puis renvoie vers la page de connexion.

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../services/AuthService.php';

$auth =    new AuthService(new Utilisateur($pdo), new Employe($pdo), new JournalService(new JournalAction($pdo)));
$auth->deconnecter();

header('Cache-Control: no-store');
header('Location: ' . PAGE_CONNEXION);
exit;
