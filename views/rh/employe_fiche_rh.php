<?php
require_once __DIR__ . '/donnees_demo_rh.php';

$espace = 'rh';
$active = 'rh_employes';
$scripts = ['gestion.js'];

$id = (string) ($_GET['id'] ?? 'EMP-0042');
$onglet = (string) ($_GET['onglet'] ?? 'identite');
if (!in_array($onglet, ['identite', 'contrat', 'documents', 'conges', 'paie'], true)) {
    $onglet = 'identite';
}

$e = $employes[$id] ?? null;
if ($e === null) {
    http_response_code(404);
    $pageTitle = 'Fiche employé';
    $sousTitre = '<a class="breadcrumb-link" href="employes_rh.php">Employés</a> / Introuvable';
    include __DIR__ . '/../layout/debut_page.php';
    ?>
        <section class="card-panel card-panel--cd-form">
          <h2 class="card-panel__title">Dossier introuvable</h2>
          <p>Ce dossier n’existe pas ou n’est plus accessible.</p>
          <div class="app-actions"><a href="employes_rh.php" class="btn btn-neutral text-decoration-none">Retour aux employés</a></div>
        </section>
    <?php
    include __DIR__ . '/../layout/fin_page.php';
    return;
}

$pageTitle = 'Fiche employé';
$sousTitre = h(nomComplet($e)) . ' • ' . h($id) . ' • ' . h($e['poste']) . ' • ' . h($e['dept']);

$ficheUrl = 'employe_fiche_rh.php';
$listeUrl = 'employes_rh.php';
$demandesUrl = 'demandes_rh.php';
$lienPaie = 'paie_rh.php';

$libellesTraiter = [
    'conges'        => 'Traiter la demande de congé',
    'modifications' => 'Traiter la modification',
    'attestations'  => 'Traiter l’attestation',
    'avances'       => 'Traiter l’avance',
];
$actionTraiter = null;
foreach ($demandes as $typeDemande => $liste) {
    foreach ($liste as $refDemande => $demande) {
        if ($demande['emp'] === $id && $actionTraiter === null) {
            $actionTraiter = ['url' => 'demandes_rh.php?type=' . $typeDemande . '&ref=' . $refDemande, 'label' => $libellesTraiter[$typeDemande]];
        }
    }
}

include __DIR__ . '/../layout/debut_page.php';
include __DIR__ . '/../gestion/corps_fiche.php';
include __DIR__ . '/../layout/fin_page.php';
