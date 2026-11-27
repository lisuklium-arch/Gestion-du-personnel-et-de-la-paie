<?php
require_once __DIR__ . '/aides_gestion.php';
require_once __DIR__ . '/../../models/Employe.php';
require_once __DIR__ . '/../../models/Contrat.php';
require_once __DIR__ . '/../../models/DocumentEmploye.php';
require_once __DIR__ . '/avatar.php';

$matricule = is_string($_GET['id'] ?? null) ? trim($_GET['id']) : '';
$employe = $matricule === '' ? null : (new Employe($pdo))->trouverParMatricule($matricule);
$pageTitle = 'Fiche employé';
if ($employe === null) {
    http_response_code(404);
    $sousTitre = '<a class="breadcrumb-link" href="' . h($listeUrl) . '">Employés</a> / Introuvable';
    include __DIR__ . '/../layout/debut_page.php';
    ?>
    <section class="card-panel"><h2 class="card-panel__title">Dossier introuvable</h2><p>Ce dossier n’existe pas ou n’est plus accessible.</p><div class="app-actions"><a href="<?= h($listeUrl) ?>" class="btn btn-neutral text-decoration-none">Retour aux employés</a></div></section>
    <?php include __DIR__ . '/../layout/fin_page.php'; return;
}
$contrat = (new Contrat($pdo))->getContratEnCours((int) $employe['id']);
$documents = (new DocumentEmploye($pdo))->listerPourEmploye((int) $employe['id']);
$sousTitre = h($employe['prenom'] . ' ' . $employe['nom']) . ' • ' . h($employe['matricule']) . ' • ' . h($employe['poste']) . ' • ' . h($employe['departement']);
if (($espace ?? '') === 'rh') {
    $scripts[] = 'matricule-correction.js';
}
include __DIR__ . '/../layout/debut_page.php';
?>
<div class="duo">
  <section class="card-panel"><div class="card-panel__head"><?= avatarEmploye($employe, 'lg') ?><h2 class="card-panel__title">Identité et rattachement</h2></div><dl class="field-grid">
    <?php champLecture('Nom', $employe['nom']); champLecture('Prénom', $employe['prenom']); champLecture('Matricule', $employe['matricule']); champLecture('Date de naissance', $employe['date_naissance'] ? (new DateTime($employe['date_naissance']))->format('d/m/Y') : '—'); champLecture('Téléphone', $employe['telephone'] ?? '—'); champLecture('Lieu de résidence', $employe['lieu_residence'] ?: 'Non renseigné'); champLecture('Département', $employe['departement']); champLecture('Poste', $employe['poste']); champLecture('Chef de département', trim(($employe['chef_prenom'] ?? '') . ' ' . ($employe['chef_nom'] ?? '')) ?: 'Non désigné'); ?>
  </dl></section>
  <section class="card-panel"><div class="card-panel__head"><h2 class="card-panel__title">Contrat et compte</h2></div><dl class="field-grid">
    <?php champLecture('E-mail professionnel', $employe['email']); champLecture('Rôle applicatif', $employe['role']); champLecture('Date d’embauche', (new DateTime($employe['date_embauche']))->format('d/m/Y')); ?>
    <?php if ($contrat): champLecture('Contrat en cours', $contrat['type']); champLecture('Période', (new DateTime($contrat['date_debut']))->format('d/m/Y') . ($contrat['date_fin'] ? ' au ' . (new DateTime($contrat['date_fin']))->format('d/m/Y') : ' • sans date de fin')); champLecture('Salaire de base', number_format((float) $contrat['salaire_base'], 0, ',', ' ') . ' FCFA'); champLecture('Heures mensuelles', $contrat['heures_mensuelles'] . ' h'); else: champLecture('Contrat en cours', 'Aucun'); endif; ?>
  </dl></section>
</div>
<section class="card-panel"><div class="card-panel__head"><h2 class="card-panel__title">Documents du dossier</h2></div><div class="table-scroll"><table class="data-table"><thead><tr><th>Document</th><th>Date</th><th>Accès</th></tr></thead><tbody><?php if (!$documents): ?><tr><td colspan="3">Aucun document.</td></tr><?php endif; ?><?php foreach ($documents as $document): ?><tr><td><?= h($document['nom_fichier']) ?></td><td><?= h((new DateTime($document['date_ajout']))->format('d/m/Y')) ?></td><td><a class="data-table__ref" href="/api/document_telecharger.php?id=<?= (int) $document['id'] ?>">Télécharger</a></td></tr><?php endforeach; ?></tbody></table></div></section>
<?php if (($espace ?? '') === 'rh'): ?>
<section class="card-panel card-panel--form"><div class="card-panel__head"><span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i></span><h2 class="card-panel__title">Correction exceptionnelle du matricule</h2></div>
  <p class="card-panel__note">Réservé à la RH. L’ancien matricule restera définitivement indisponible.</p>
  <div id="correction-matricule-retour" class="alert d-none" role="status"></div>
  <form id="form-correction-matricule" class="needs-validation" novalidate>
    <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="employe_id" value="<?= (int) $employe['id'] ?>">
    <label for="correction-matricule" class="form-label">Nouveau matricule</label>
    <input id="correction-matricule" name="matricule" class="form-control" value="<?= h($employe['matricule']) ?>" pattern="EMP-[0-9]{4}" maxlength="8" required>
    <div class="invalid-feedback">Format attendu : EMP-0000.</div>
    <div class="app-form-actions"><button type="submit" class="btn btn-danger-outline">Corriger le matricule</button></div>
  </form>
</section>
<?php endif; ?>
<div class="app-actions"><a href="<?= h($listeUrl) ?>" class="btn btn-neutral text-decoration-none">Retour aux employés</a></div>
<?php include __DIR__ . '/../layout/fin_page.php'; ?>
