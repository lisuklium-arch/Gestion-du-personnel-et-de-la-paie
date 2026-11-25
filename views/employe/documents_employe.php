<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ .'/../../models/Employe.php';
require_once __DIR__ . '/../../models/DocumentEmploye.php';
require_once __DIR__ . '/../../models/DemandeDocument.php';
exigerRole(['employe', 'chef', 'rh']);

$moi = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
if ($moi === null) {
    http_response_code(404);
    exit('Aucun dossier employé n\'est associé à ce compte.');
}
$documents = (new DocumentEmploye($pdo))->listerPourEmploye((int) $moi['id']);
$demandes = (new DemandeDocument($pdo))->listerPourEmploye((int) $moi['id']);
$typesDocument = ['piece_identite' => 'Pièce d’identité', 'diplome' => 'Diplôme', 'contrat_signe' => 'Contrat signé', 'attestation' => 'Attestation', 'autre' => 'Autre document'];
$typesAttestation = ['attestation_travail' => 'Attestation de travail', 'attestation_salaire' => 'Attestation de salaire'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf" content="<?= h(csrfToken()) ?>">
  <title>Documents — PerPay</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="/public/css/tokens.css">
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/components.css">
  <link rel="stylesheet" href="/public/css/layout.css">
  <?php include __DIR__ . '/../layout/head_notifications.php'; ?>
</head>
<body>

  <div class="app-shell">

    <?php $active = 'documents'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Documents</h1>
        </div>
        <?php include __DIR__ . '/../layout/cloche.php'; ?>
      </header>

      <main class="app-content">
        <p class="app-meta"><i class="fa-regular fa-user me-1" aria-hidden="true"></i><?= h($moi['prenom'] . ' ' . $moi['nom']) ?> • <?= h($moi['matricule']) ?> • Documents personnels et attestations</p>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-folder" aria-hidden="true"></i></span><h2 class="card-panel__title">Mes documents</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Mes documents">
            <table class="data-table">
              <caption class="visually-hidden">Mes documents</caption>
              <thead><tr><th scope="col">Document</th><th scope="col">Ajouté le</th><th scope="col">Format</th><th scope="col">État</th></tr></thead>
              <tbody>
              <?php if (!$documents): ?><tr><td colspan="4">Aucun document disponible.</td></tr><?php endif; ?>
              <?php foreach ($documents as $document): ?>
              <tr><td data-label="Document"><a class="data-table__ref" href="/api/document_telecharger.php?id=<?= (int) $document['id'] ?>"><i class="fa-regular fa-file me-2" aria-hidden="true"></i><?= h($typesDocument[$document['type']] ?? 'Document') ?> — <?= h($document['nom_fichier']) ?></a></td><td data-label="Ajouté le"><span class="data-table__muted"><?= h((new DateTime($document['date_ajout']))->format('d/m/Y')) ?></span></td><td data-label="Format"><?= h(strtoupper(pathinfo($document['nom_fichier'], PATHINFO_EXTENSION))) ?></td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Disponible</span></td></tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <p class="card-panel__note">Cliquez sur un document pour le télécharger. L’accès est vérifié à chaque téléchargement.</p>
        </section>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-clipboard" aria-hidden="true"></i></span><h2 class="card-panel__title">Demandes d’attestation</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Demandes d’attestation">
            <table class="data-table">
              <caption class="visually-hidden">Demandes d’attestation</caption>
              <thead><tr><th scope="col">Référence</th><th scope="col">Type</th><th scope="col">Date</th><th scope="col">État</th></tr></thead>
              <tbody>
              <?php if (!$demandes): ?><tr><td colspan="4">Aucune demande d’attestation.</td></tr><?php endif; ?>
              <?php foreach ($demandes as $demande): ?><tr><td data-label="Référence"><span class="data-table__ref">AT-<?= str_pad((string) $demande['id'], 4, '0', STR_PAD_LEFT) ?></span></td><td data-label="Type"><span class="data-table__muted"><?= h($typesAttestation[$demande['type']] ?? $demande['type']) ?></span></td><td data-label="Date"><?= h((new DateTime($demande['date_demande']))->format('d/m/Y')) ?></td><td data-label="État"><span class="badge-status badge-status--<?= $demande['statut'] === 'en_attente' ? 'warning' : ($demande['statut'] === 'generee' ? 'success' : 'danger') ?>"><?= h($demande['statut'] === 'en_attente' ? 'En attente RH' : ($demande['statut'] === 'generee' ? 'Générée' : 'Refusée')) ?></span></td></tr><?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </section>

        <section class="card-panel card-panel--form">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-circle-plus" aria-hidden="true"></i></span><h2 class="card-panel__title">Demander une attestation</h2></div>

          <div id="attestation-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Demande d’attestation envoyée. Elle apparaîtra dans la liste ci-dessus.
          </div>

          <form id="form-attestation" class="needs-validation" novalidate data-api="/api/demande_attestation_creer.php" data-confirmation="attestation-confirmation">
            <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
            <div class="mb-3">
              <label for="type-attestation" class="form-label">Type d’attestation</label>
              <select id="type-attestation" name="type_attestation" class="form-select" required>
                <option value="attestation_travail">Attestation de travail</option>
                <option value="attestation_salaire">Attestation de salaire</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="motif-attestation" class="form-label">Motif</label>
              <textarea id="motif-attestation" name="motif" class="form-control" rows="3" required></textarea>
              <div class="invalid-feedback">Indiquez le motif de votre demande.</div>
            </div>

            <div class="note-info note-info--success mb-3" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <span>La RH traite votre demande. Après validation, le document apparaît dans cette liste.</span>
            </div>

            <button type="submit" class="btn btn-primary w-100"><i class="fa-regular fa-paper-plane me-2" aria-hidden="true"></i>Demander l’attestation</button>
          </form>
        </section>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/public/js/forms.js"></script>
</body>
</html>
