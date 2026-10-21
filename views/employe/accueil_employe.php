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
  <title>Accueil — PerPay</title>

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

    <?php $active = 'accueil'; include __DIR__ . '/../layout/navbar.php'; ?>
    <div class="app-main">

      <header class="app-header">
        <h1 class="app-header__title">Accueil</h1>

      </header>

      <main class="app-content">

        <p class="app-meta">Vendredi 9 octobre 2026 • <?= h($moi['prenom'] . ' ' . $moi['nom']) ?> • <?= h($moi['matricule']) ?> • <?= h($moi['departement']) ?></p>

        <section class="app-greeting">
          <h2 class="app-greeting__title">Bonjour <?= h($moi['prenom']) ?></h2>
          <p class="app-greeting__text">Retrouvez vos congés, votre dernière fiche de paie et le suivi de vos demandes.
          </p>
        </section>

        <section class="app-kpis" aria-label="Indicateurs">
          <article class="card-panel kpi-card">
            <p class="kpi-card__label">Congés disponibles</p>
            <p class="kpi-card__value">12 jours</p>
            <p class="kpi-card__meta">24 acquis − 8 pris − 4 en attente</p>
          </article>
          <article class="card-panel kpi-card">
            <p class="kpi-card__label">Dernier net à payer</p>
            <p class="kpi-card__value">320 000 FCFA</p>
            <p class="kpi-card__meta">Fiche de septembre 2026</p>
          </article>
        </section>

        <section class="card-panel app-payslip">
          <h2 class="card-panel__title">Dernière fiche de paie</h2>
          <span class="badge-status badge-status--success">Validée</span>
          <p class="app-payslip__label">Septembre 2026 • 320 000 FCFA nets</p>
          <p class="app-payslip__details">
            Brut : 350 000 FCFA • Retenues : 30 000 FCFA. Validée le 2 octobre par Chantal Mvondo.
          </p>
          <a href="fiche_paie_detail_employe.php" class="btn btn-neutral text-decoration-none">Consulter la fiche</a>
        </section>

        <section class="card-panel">
          <h2 class="card-panel__title">Demandes récentes</h2>

          <div class="table-scroll" tabindex="0" role="region" aria-label="Demandes récentes">
            <table class="data-table">
              <caption class="visually-hidden">Demandes récentes</caption>
              <thead>
                <tr>
                  <th scope="col">Référence</th>
                  <th scope="col">Type</th>
                  <th scope="col">Période</th>
                  <th scope="col">État</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <td data-label="Référence" class="data-table__ref">CG-0261</td>
                  <td data-label="Type">Congé annuel</td>
                  <td data-label="Période">02–05 nov. 2026 • 4 jours</td>
                  <td data-label="État"><span class="badge-status badge-status--warning">En attente RH</span></td>
                </tr>
                <tr>
                  <td data-label="Référence" class="data-table__ref">CG-0184</td>
                  <td data-label="Type">Congé annuel</td>
                  <td data-label="Période">03–12 août 2026 • 8 jours</td>
                  <td data-label="État"><span class="badge-status badge-status--success">Validé</span></td>
                </tr>
                <tr>
                  <td data-label="Référence" class="data-table__ref">CG-0120</td>
                  <td data-label="Type">Congé annuel</td>
                  <td data-label="Période">18–19 juin 2026 • 2 jours</td>
                  <td data-label="État"><span class="badge-status badge-status--danger">Refusé</span></td>
                </tr>
              </tbody>
            </table>
          </div>

          <p class="card-panel__note">CG-0261 : avis favorable du chef reçu le 8 octobre. Décision RH attendue.</p>
        </section>

        <div class="app-actions">
          <a href="conges_nouvelle_demande_employe.php" class="btn btn-primary text-decoration-none">Nouvelle demande de congé</a>
          <a href="documents_employe.php" class="btn btn-neutral text-decoration-none">Voir mes documents</a>
        </div>

      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>