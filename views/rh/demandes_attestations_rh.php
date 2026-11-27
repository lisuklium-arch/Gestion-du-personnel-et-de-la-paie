<?php
require_once __DIR__ . '/../../models/DemandeDocument.php';
require_once __DIR__ . '/../gestion/avatar.php';
$demandesAttestation = (new DemandeDocument($pdo))->listerEnAttente();
$espace = 'rh'; $active = 'rh_demandes'; $pageTitle = 'Demandes d’attestation'; $titrePage = 'Demandes à traiter — Attestations';
$sousTitre = count($demandesAttestation) . ' demande' . (count($demandesAttestation) > 1 ? 's' : '') . ' en attente de décision RH';
$scripts = ['gestion.js', 'demandes-attestations-rh.js'];
include __DIR__ . '/../layout/debut_page.php';
?>
<nav aria-label="Types de demande"><ul class="tabs-pills"><li><a class="tab-pill" href="?type=conges">Congés</a></li><li><a class="tab-pill" href="?type=modifications">Modifications</a></li><li><a class="tab-pill is-active" href="?type=attestations" aria-current="page">Attestations (<?= count($demandesAttestation) ?>)</a></li><li><a class="tab-pill" href="?type=avances">Avances</a></li></ul></nav>
<div id="demande-attestation-retour" class="alert d-none" role="status" aria-live="polite"></div>
<section class="card-panel"><div class="card-panel__head"><span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-clock" aria-hidden="true"></i></span><h2 class="card-panel__title">Attestations en attente</h2></div>
<?php if (!$demandesAttestation): ?><p class="mb-0">Aucune demande d’attestation en attente.</p><?php else: ?><div class="table-scroll" tabindex="0"><table class="data-table"><thead><tr><th>Employé</th><th>Type</th><th>Motif</th><th>Décision</th></tr></thead><tbody><?php foreach ($demandesAttestation as $demande): ?><tr data-demande-attestation="<?= (int) $demande['id'] ?>"><td><div class="cell-person"><?= avatarEmploye($demande, 'sm') ?><div><p class="cell-person__name"><?= h($demande['prenom'] . ' ' . $demande['nom']) ?></p><p class="cell-person__meta"><?= h($demande['matricule'] . ' • ' . $demande['poste']) ?></p></div></div></td><td><?= h($demande['type'] === 'attestation_salaire' ? 'Attestation de salaire' : 'Attestation de travail') ?></td><td><?= h((string) $demande['motif']) ?></td><td><form data-traiter-demande-attestation><input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>"><input type="hidden" name="id" value="<?= (int) $demande['id'] ?>"><button type="submit" name="decision" value="generee" class="btn btn-primary">Générer Word</button><button type="submit" name="decision" value="refusee" class="btn btn-danger-outline">Refuser</button></form></td></tr><?php endforeach; ?></tbody></table></div><?php endif; ?>
</section>
<?php include __DIR__ . '/../layout/fin_page.php'; ?>
