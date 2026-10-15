<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
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
</head>
<body>

  <div class="app-shell">

    <?php $active = 'documents'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Documents</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta"><i class="fa-regular fa-user me-1" aria-hidden="true"></i>Marie Ngo Ndjock • EMP-0042 • Documents personnels et attestations</p>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-folder" aria-hidden="true"></i></span><h2 class="card-panel__title">Mes documents</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Mes documents">
            <table class="data-table">
              <caption class="visually-hidden">Mes documents</caption>
              <thead><tr><th scope="col">Document</th><th scope="col">Ajouté le</th><th scope="col">Format</th><th scope="col">État</th></tr></thead>
              <tbody>
              <tr><td data-label="Document"><i class="fa-regular fa-file me-2" aria-hidden="true"></i>Contrat CDI signé</td><td data-label="Ajouté le"><span class="data-table__muted">01/03/2023</span></td><td data-label="Format">PDF • 420 Ko</td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Disponible</span></td></tr>
              <tr><td data-label="Document"><i class="fa-regular fa-file me-2" aria-hidden="true"></i>Pièce d’identité</td><td data-label="Ajouté le"><span class="data-table__muted">01/03/2023</span></td><td data-label="Format">PDF • 180 Ko</td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Disponible</span></td></tr>
              <tr><td data-label="Document"><i class="fa-regular fa-file me-2" aria-hidden="true"></i>Attestation de travail</td><td data-label="Ajouté le"><span class="data-table__muted">15/06/2026</span></td><td data-label="Format">PDF • 95 Ko</td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Disponible</span></td></tr>
              </tbody>
            </table>
          </div>
          <div class="app-form-actions">
            <button type="button" class="btn btn-neutral"><i class="fa-solid fa-arrow-up-from-bracket me-2" aria-hidden="true"></i>Télécharger un document</button>
          </div>
        </section>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-clipboard" aria-hidden="true"></i></span><h2 class="card-panel__title">Demandes d’attestation</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Demandes d’attestation">
            <table class="data-table">
              <caption class="visually-hidden">Demandes d’attestation</caption>
              <thead><tr><th scope="col">Référence</th><th scope="col">Type</th><th scope="col">Date</th><th scope="col">État</th></tr></thead>
              <tbody>
              <tr><td data-label="Référence"><span class="data-table__ref">AT-0083</span></td><td data-label="Type"><span class="data-table__muted">Attestation de salaire</span></td><td data-label="Date">08/10/2026</td><td data-label="État"><span class="badge-status badge-status--warning">En attente RH</span></td></tr>
              <tr><td data-label="Référence"><span class="data-table__ref">AT-0041</span></td><td data-label="Type"><span class="data-table__muted">Attestation de travail</span></td><td data-label="Date">14/06/2026</td><td data-label="État"><span class="badge-status badge-status--success">Validée</span></td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <section class="card-panel card-panel--form">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-circle-plus" aria-hidden="true"></i></span><h2 class="card-panel__title">Demander une attestation</h2></div>

          <div id="attestation-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Demande d’attestation envoyée. Elle apparaîtra dans la liste ci-dessus.
          </div>

          <form id="form-attestation" class="needs-validation" novalidate data-confirmation="attestation-confirmation">
            <div class="mb-3">
              <label for="type-attestation" class="form-label">Type d’attestation</label>
              <select id="type-attestation" name="type_attestation" class="form-select" required>
                <option selected>Attestation de travail</option>
                <option>Attestation de salaire</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="destinataire" class="form-label">Destinataire</label>
              <input type="text" id="destinataire" name="destinataire" class="form-control" value="Banque de l’entreprise" required>
              <div class="invalid-feedback">Indiquez le destinataire.</div>
            </div>

            <div class="mb-3">
              <label for="motif-attestation" class="form-label">Motif</label>
              <textarea id="motif-attestation" name="motif" class="form-control" rows="3" required>Constitution de mon dossier administratif bancaire.</textarea>
              <div class="invalid-feedback">Indiquez le motif de votre demande.</div>
            </div>

            <div class="note-info note-info--success mb-3" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <span>La RH traite votre demande. Après validation, le document apparaît dans cette liste.</span>
            </div>

            <fieldset class="upload-group">
    <legend class="upload-group__legend">Justificatifs <span class="field-tag">Facultatifs</span></legend>
    <p class="upload-group__intro">Vous pouvez joindre jusqu’à 2 pièces (PDF, JPG ou PNG, 5 Mo maximum chacune). Aucune n’est obligatoire.</p>
    <div class="mb-3">
      <label for="attestation-justificatif-1" class="form-label">Justificatif 1 <span class="field-tag">Optionnel</span></label>
      <input type="file" id="attestation-justificatif-1" name="justificatif_1" class="form-control"
             accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
      <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
    <div class="mb-0">
      <label for="attestation-justificatif-2" class="form-label">Justificatif 2 <span class="field-tag">Optionnel</span></label>
      <input type="file" id="attestation-justificatif-2" name="justificatif_2" class="form-control"
             accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
      <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
  </fieldset>

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
