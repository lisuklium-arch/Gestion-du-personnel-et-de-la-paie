<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ .'/../../models/Employe.php';
exigerRole(['employe', 'chef', 'rh']);

$moi = (new Employe($pdo))->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
if ($moi === null) {
    http_response_code(404);
    exit('Aucun dossier employé n\'est associé à ce compte.');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Congés — PerPay</title>

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

    <?php $active = 'conges'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Congés</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta"><?= h($moi['prenom'] . ' ' . $moi['nom']) ?>• Année 2026 • Soldes actualisés au 9 octobre</p>

        <section class="app-kpis app-kpis--4" aria-label="Soldes de congés">
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-regular fa-calendar" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Droits acquis</p>
            </div>
            <p class="kpi-card__value">24 jours</p>
            <p class="kpi-card__meta">Congé annuel 2026</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-circle-check" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Jours pris</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral">8 jours</p>
            <p class="kpi-card__meta">03 au 12 août</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm icon-tile--warning"><i class="fa-regular fa-clock" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Réservés</p>
            </div>
            <p class="kpi-card__value kpi-card__value--neutral">4 jours</p>
            <p class="kpi-card__meta">Demande CG-0261 en attente</p>
          </article>
          <article class="card-panel kpi-card">
            <div class="kpi-card__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i></span>
              <p class="kpi-card__label">Disponibles</p>
            </div>
            <p class="kpi-card__value">12 jours</p>
            <p class="kpi-card__meta">16 avant réservation − 4</p>
          </article>
        </section>

        <div class="app-actions">
          <a href="conges_nouvelle_demande_employe.php" class="btn btn-primary text-decoration-none">
            <i class="fa-solid fa-plus me-2" aria-hidden="true"></i>Nouvelle demande de congé
          </a>
        </div>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-list" aria-hidden="true"></i></span><h2 class="card-panel__title">Mes demandes</h2><div class="card-panel__aside">3 demandes</div></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Mes demandes de congé">
            <table class="data-table">
              <caption class="visually-hidden">Mes demandes de congé</caption>
              <thead><tr><th scope="col">Référence</th><th scope="col">Type</th><th scope="col">Dates et durée</th><th scope="col">État</th></tr></thead>
              <tbody>
              <tr><td data-label="Référence"><span class="data-table__ref">CG-0261</span></td><td data-label="Type">Congé annuel</td><td data-label="Dates et durée">02–05 nov. 2026 • 4 jours</td><td data-label="État"><span class="badge-status badge-status--warning">En attente RH</span></td></tr>
              <tr><td data-label="Référence"><span class="data-table__ref">CG-0184</span></td><td data-label="Type">Congé annuel</td><td data-label="Dates et durée">03–12 août 2026 • 8 jours</td><td data-label="État"><span class="badge-status badge-status--success">Validé</span></td></tr>
              <tr><td data-label="Référence"><span class="data-table__ref">CG-0120</span></td><td data-label="Type">Congé annuel</td><td data-label="Dates et durée">18–19 juin 2026 • 2 jours</td><td data-label="État"><span class="badge-status badge-status--danger">Refusé</span></td></tr>
              </tbody>
            </table>
          </div>
          <p class="card-panel__note"><i class="fa-solid fa-circle-exclamation is-danger me-1" aria-hidden="true"></i>CG-0120 refusée par la RH le 12 juin : présence requise pour la clôture. Aucun jour déduit.</p>
        </section>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-wave-square" aria-hidden="true"></i></span><h2 class="card-panel__title">Autres types de congé</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Autres types de congé">
            <table class="data-table">
              <caption class="visually-hidden">Autres types de congé</caption>
              <thead><tr><th scope="col">Type</th><th scope="col">Droit</th><th scope="col">Utilisation</th></tr></thead>
              <tbody>
              <tr><td data-label="Type"><span class="data-table__ref">Maladie</span></td><td data-label="Droit"><span class="data-table__muted">Sur justificatif</span></td><td data-label="Utilisation">0 jour déclaré</td></tr>
              <tr><td data-label="Type"><span class="data-table__ref">Événement familial</span></td><td data-label="Droit"><span class="data-table__muted">Selon événement</span></td><td data-label="Utilisation">0 jour pris</td></tr>
              </tbody>
            </table>
          </div>
        </section>

        <div class="note-info note-info--success" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>Pour un employé, le chef émet un avis puis la RH décide. Un avis défavorable n’est pas un refus définitif. Les demandes en attente réservent le solde disponible.</span>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
