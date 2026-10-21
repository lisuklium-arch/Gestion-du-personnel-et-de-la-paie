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
  <title>Détail de fiche de paie — PerPay</title>

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
          <h1 class="app-header__title">Détail de fiche de paie</h1>
          <p class="app-header__sub">Septembre 2026 • Fiche FP-2026-09-0042</p>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta">Tous les montants en FCFA</p>

        <section class="card-panel">
          <div class="app-person mb-3">
            <span class="avatar" aria-hidden="true">MN</span>
            <div>
              <p class="app-person__name"><?= h($moi['prenom'] . ' ' . $moi['nom']) ?> <span class="badge-status badge-status--success"><i class="fa-solid fa-check" aria-hidden="true"></i>Validée</span></p>
              <p class="app-person__meta">EMP-0042 • Comptable • Finance</p>
            </div>
          </div>
          <dl class="info-grid info-grid--3">
            <div><dt><i class="fa-solid fa-briefcase me-1" aria-hidden="true"></i>Contrat</dt><dd>CDI depuis le 01/03/2023<span class="info-grid__sub">173,33 heures mensuelles</span></dd></div>
            <div><dt><i class="fa-regular fa-calendar me-1" aria-hidden="true"></i>Période de paie</dt><dd>01/09/2026 au 30/09/2026<span class="info-grid__sub">Septembre 2026</span></dd></div>
            <div><dt><i class="fa-solid fa-hashtag me-1" aria-hidden="true"></i>Référence</dt><dd>FP-2026-09-0042<span class="info-grid__sub">Fiche de septembre</span></dd></div>
          </dl>
        </section>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i></span><h2 class="card-panel__title">Gains</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Gains">
            <table class="data-table">
              <caption class="visually-hidden">Gains</caption>
              <thead><tr><th scope="col">Rubrique</th><th scope="col">Base / quantité</th><th scope="col" class="data-table__amount">Montant FCFA</th></tr></thead>
              <tbody>
              <tr><td data-label="Rubrique">Salaire de base</td><td data-label="Base / quantité"><span class="data-table__muted">Mois complet</span></td><td data-label="Montant FCFA" class="data-table__amount">300 000</td></tr>
              <tr><td data-label="Rubrique">Prime de fonction</td><td data-label="Base / quantité"><span class="data-table__muted">Comptable</span></td><td data-label="Montant FCFA" class="data-table__amount">25 000</td></tr>
              <tr><td data-label="Rubrique">Prime de transport</td><td data-label="Base / quantité"><span class="data-table__muted">Forfait mensuel</span></td><td data-label="Montant FCFA" class="data-table__amount">10 000</td></tr>
              <tr><td data-label="Rubrique">Heures supplémentaires</td><td data-label="Base / quantité"><span class="data-table__muted">8 h validées chef</span></td><td data-label="Montant FCFA" class="data-table__amount">15 000</td></tr>
              </tbody>
            </table>
          </div>
          <div class="total-band"><span>Total brut</span><span class="total-band__value">350 000 FCFA</span></div>
          <p class="card-panel__note">300 000 de base + 35 000 de primes + 15 000 d’heures supplémentaires.</p>
        </section>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm icon-tile--danger"><i class="fa-solid fa-circle-minus" aria-hidden="true"></i></span><h2 class="card-panel__title">Retenues</h2></div>
          <div class="table-scroll" tabindex="0" role="region" aria-label="Retenues">
            <table class="data-table">
              <caption class="visually-hidden">Retenues</caption>
              <thead><tr><th scope="col">Rubrique</th><th scope="col">Référence</th><th scope="col" class="data-table__amount">Montant FCFA</th></tr></thead>
              <tbody>
              <tr><td data-label="Rubrique">Cotisations salariales</td><td data-label="Référence"><span class="data-table__muted">Septembre 2026</span></td><td data-label="Montant FCFA" class="data-table__amount"><span class="is-danger">10 000</span></td></tr>
              <tr><td data-label="Rubrique">Avance sur salaire</td><td data-label="Référence"><span class="data-table__muted">AV-0081 • accordée le 10/09</span></td><td data-label="Montant FCFA" class="data-table__amount"><span class="is-danger">20 000</span></td></tr>
              </tbody>
            </table>
          </div>
          <div class="total-band total-band--danger"><span>Total des retenues</span><span class="total-band__value">30 000 FCFA</span></div>
        </section>

        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-circle-check" aria-hidden="true"></i></span><h2 class="card-panel__title">Net à payer</h2></div>
          <div class="net-band">
            <p class="net-band__value">320 000 FCFA</p>
            <p class="net-band__calc">350 000 − 30 000 = 320 000 FCFA</p>
          </div>
          <div class="app-form-actions">
            <a href="#" class="btn btn-primary text-decoration-none"><i class="fa-solid fa-download me-2" aria-hidden="true"></i>Télécharger le PDF</a>
            <a href="fiches_paie_employe.php" class="btn btn-neutral text-decoration-none"><i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Retour aux fiches</a>
          </div>
        </section>

        <div class="note-info" role="note">
          <i class="fa-solid fa-lock" aria-hidden="true"></i>
          <span>Validée par Chantal Mvondo le 2 octobre 2026 à 16:30. Cette fiche est en lecture seule. Le PDF reprend ces rubriques et ces totaux.</span>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
