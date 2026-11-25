<?php

require __DIR__ . '/../config/init.php';
require_once __DIR__ . '/../config/api.php';
require_once __DIR__ . '/../models/Utilisateur.php';
require_once __DIR__ . '/../services/CompteService.php';

exigerRole(['rh', 'dg'], true);

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    header('Allow: GET');
    reponseJson(405, ['ok' => false, 'message' => 'Méthode non autorisée.']);
}

$texte = static fn(string $cle): string => is_string($_GET[$cle] ?? null) ? trim($_GET[$cle]) : '';
$recherche = mb_substr($texte('q'), 0, 100);
$departement = $texte('dept');
$poste = $texte('poste');
$statut = $texte('statut');
$compte = $texte('compte');

$departements = $pdo->query('SELECT id FROM departement')->fetchAll(PDO::FETCH_COLUMN);
$postes = $pdo->query('SELECT id FROM poste')->fetchAll(PDO::FETCH_COLUMN);

$filtres = [
    'avec_dossier' => true,
    'q' => $recherche,
    'departement_id' => in_array((int) $departement, array_map('intval', $departements), true) ? $departement : '',
    'poste_id' => in_array((int) $poste, array_map('intval', $postes), true) ? $poste : '',
    'statut_dossier' => in_array($statut, ['actif', 'suspendu', 'sorti'], true) ? $statut : '',
    'statut_compte' => in_array($compte, ['en_attente', 'actif', 'desactive'], true) ? $compte : '',
];

$auteur = auteurCourant();
$lignes = (new Utilisateur($pdo))->rechercherComptes($filtres);

foreach ($lignes as &$ligne) {
    $ligne['action'] = CompteService::actionPossible($auteur, $ligne);
}
unset($ligne);

reponseJson(200, ['ok' => true, 'employes' => $lignes]);
