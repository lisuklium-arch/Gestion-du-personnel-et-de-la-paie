<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Avances — PerPay</title>

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

    <?php $active = 'avances'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Avances</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta">Marie Ngo Ndjock • EMP-0042 • <strong>Avances sur salaire</strong></p>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-clock" aria-hidden="true"></i></span><h2 class="card-panel__title">Historique</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Historique des avances">
            <table class="data-table">
              <caption class="visually-hidden">Historique des avances</caption>
              <thead><tr><th scope="col">Référence / date</th><th scope="col">Montant</th><th scope="col">État</th><th scope="col">Retenue prévue</th></tr></thead>
              <tbody>
              <tr><td data-label="Référence / date"><span class="data-table__ref data-table__stack">AV-0093<small>07/10/2026</small></span></td><td data-label="Montant"><span class="data-table__ref">30 000 FCFA</span></td><td data-label="État"><span class="badge-status badge-status--warning">En attente RH</span></td><td data-label="Retenue prévue">Octobre 2026</td></tr>
              <tr><td data-label="Référence / date"><span class="data-table__ref data-table__stack">AV-0081<small>08/09/2026</small></span></td><td data-label="Montant"><span class="data-table__ref">20 000 FCFA</span></td><td data-label="État"><span class="badge-status badge-status--success">Accordée</span></td><td data-label="Retenue prévue">Septembre • déjà retenue</td></tr>
              <tr><td data-label="Référence / date"><span class="data-table__ref data-table__stack">AV-0062<small>04/06/2026</small></span></td><td data-label="Montant"><span class="data-table__ref">50 000 FCFA</span></td><td data-label="État"><span class="badge-status badge-status--danger">Refusée</span></td><td data-label="Retenue prévue"><span class="data-table__muted">Aucune retenue</span></td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <div class="app-split">

          <section class="card-panel">
            <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-circle-plus" aria-hidden="true"></i></span><h2 class="card-panel__title">Demander une avance</h2></div>

            <div id="avance-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Demande d’avance envoyée. La RH la traitera prochainement.
          </div>

            <form id="form-avance" class="needs-validation" novalidate data-confirmation="avance-confirmation">
              <div class="mb-3">
                <label for="montant-avance" class="form-label">Montant demandé (FCFA)</label>
                <input type="number" id="montant-avance" name="montant" class="form-control" value="30000" min="1000" step="1000" inputmode="numeric" required>
                <div class="invalid-feedback">Saisissez un montant valide.</div>
              </div>

              <div class="mb-3">
                <label for="mois-retenue" class="form-label">Mois de retenue souhaité</label>
                <select id="mois-retenue" name="mois_retenue" class="form-select" required>
                  <option selected>Novembre 2026</option>
                  <option>Décembre 2026</option>
                  <option>Janvier 2027</option>
                </select>
              </div>

              <div class="mb-3">
                <label for="motif-avance" class="form-label">Motif</label>
                <textarea id="motif-avance" name="motif" class="form-control" rows="3" required>Dépenses scolaires exceptionnelles pour ma famille.</textarea>
                <div class="invalid-feedback">Indiquez le motif de votre demande.</div>
              </div>

              <div class="note-info mb-3" role="note">
                <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                <span>Une demande est déjà en attente pour octobre. Une avance accordée est déduite sur la fiche de paie du mois retenu.</span>
              </div>

              <fieldset class="upload-group">
    <legend class="upload-group__legend">Justificatifs <span class="field-tag">Facultatifs</span></legend>
    <p class="upload-group__intro">Vous pouvez joindre jusqu’à 2 pièces (PDF, JPG ou PNG, 5 Mo maximum chacune). Aucune n’est obligatoire.</p>
    <div class="mb-3">
      <label for="avance-justificatif-1" class="form-label">Justificatif 1 <span class="field-tag">Optionnel</span></label>
      <input type="file" id="avance-justificatif-1" name="justificatif_1" class="form-control"
             accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
      <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
    <div class="mb-0">
      <label for="avance-justificatif-2" class="form-label">Justificatif 2 <span class="field-tag">Optionnel</span></label>
      <input type="file" id="avance-justificatif-2" name="justificatif_2" class="form-control"
             accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
      <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
    </div>
  </fieldset>

              <button type="submit" class="btn btn-primary w-100"><i class="fa-regular fa-paper-plane me-2" aria-hidden="true"></i>Envoyer la demande</button>
            </form>
          </section>

          <div class="app-stack">
            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-circle-info" aria-hidden="true"></i></span><h2 class="card-panel__title">À savoir</h2></div>
              <ul class="bullet-list">
                <li>Une demande est déjà en cours pour octobre 2026.</li>
                <li>Une avance accordée est déduite de la fiche de paie du mois retenu.</li>
              </ul>
            </section>

            <section class="card-panel">
              <h2 class="card-panel__title">Récapitulatif</h2>
              <ul class="kv-list">
                <li><span>Total accordé</span><span class="kv-list__value is-success">20 000 FCFA</span></li>
                <li><span>En attente</span><span class="kv-list__value is-warning">30 000 FCFA</span></li>
                <li><span>Refusé</span><span class="kv-list__value is-danger">50 000 FCFA</span></li>
              </ul>
            </section>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/public/js/forms.js"></script>
</body>
</html>
