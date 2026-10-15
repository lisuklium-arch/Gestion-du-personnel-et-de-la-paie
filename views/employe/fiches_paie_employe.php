<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Fiches de paie — PerPay</title>

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

    <?php $active = 'paie'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Fiches de paie</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta app-meta--chips">
          <span>Marie Ngo Ndjock · EMP-0042 · Fiches validées uniquement</span>
          <span class="ms-auto"><span class="badge-status badge-status--success">6 fiches</span></span>
        </p>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span><h2 class="card-panel__title">Mes bulletins</h2><div class="card-panel__aside"><label for="annee" class="visually-hidden">Année</label>
            <select id="annee" class="form-select" aria-label="Année">
              <option selected>2026</option>
              <option>2025</option>
            </select></div></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Mes bulletins de paie">
            <table class="data-table">
              <caption class="visually-hidden">Mes bulletins de paie</caption>
              <thead><tr><th scope="col">Période</th><th scope="col">Net à payer</th><th scope="col">Publication</th><th scope="col">État</th></tr></thead>
              <tbody>
              <tr><td data-label="Période"><span class="icon-tile icon-tile--sm me-2"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>Septembre 2026</td><td data-label="Net à payer"><span class="data-table__ref">320 000 FCFA</span></td><td data-label="Publication"><span class="data-table__muted">02/10/2026</span></td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Validée</span></td></tr>
              <tr><td data-label="Période"><span class="icon-tile icon-tile--sm me-2"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>Août 2026</td><td data-label="Net à payer"><span class="data-table__ref">325 000 FCFA</span></td><td data-label="Publication"><span class="data-table__muted">02/09/2026</span></td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Validée</span></td></tr>
              <tr><td data-label="Période"><span class="icon-tile icon-tile--sm me-2"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>Juillet 2026</td><td data-label="Net à payer"><span class="data-table__ref">325 000 FCFA</span></td><td data-label="Publication"><span class="data-table__muted">03/08/2026</span></td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Validée</span></td></tr>
              <tr><td data-label="Période"><span class="icon-tile icon-tile--sm me-2"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>Juin 2026</td><td data-label="Net à payer"><span class="data-table__ref">325 000 FCFA</span></td><td data-label="Publication"><span class="data-table__muted">02/07/2026</span></td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Validée</span></td></tr>
              <tr><td data-label="Période"><span class="icon-tile icon-tile--sm me-2"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>Mai 2026</td><td data-label="Net à payer"><span class="data-table__ref">325 000 FCFA</span></td><td data-label="Publication"><span class="data-table__muted">02/06/2026</span></td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Validée</span></td></tr>
              <tr><td data-label="Période"><span class="icon-tile icon-tile--sm me-2"><i class="fa-solid fa-file-lines" aria-hidden="true"></i></span>Avril 2026</td><td data-label="Net à payer"><span class="data-table__ref">325 000 FCFA</span></td><td data-label="Publication"><span class="data-table__muted">04/05/2026</span></td><td data-label="État"><span class="badge-status badge-status--success badge-status--dot">Validée</span></td></tr>
              </tbody>
            </table>
          </div>
          <p class="card-panel__note">6 fiches disponibles. Les brouillons de paie ne sont pas visibles dans votre espace personnel.</p>
        </section>

        <div class="app-actions">
          <a href="fiche_paie_detail_employe.php" class="btn btn-primary text-decoration-none">
            <i class="fa-solid fa-download me-2" aria-hidden="true"></i>Ouvrir la fiche du dernier mois<i class="fa-solid fa-arrow-right ms-2" aria-hidden="true"></i>
          </a>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
