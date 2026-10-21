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
  <title>Organigramme — PerPay</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="/public/css/tokens.css">
  <link rel="stylesheet" href="/public/css/base.css">
  <link rel="stylesheet" href="/public/css/components.css">
  <link rel="stylesheet" href="/public/css/layout.css">
  <link rel="stylesheet" href="/public/css/organigramme.css">
</head>
<body>

  <div class="app-shell">

    <?php $active = 'organigramme'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Organigramme</h1>
          <p class="app-header__sub">Structure de l’entreprise • 48 employés actifs • 4 départements</p>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <section class="card-panel">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span><div><h2 class="card-panel__title">Direction générale</h2><p class="card-panel__sub">Supervision des départements</p></div></div>
          <div class="org-root__body">
            <span class="avatar" aria-hidden="true">DG</span>
            <div class="org-root__who">
              <p class="app-person__name">Compte Directeur général</p>
              <p class="app-person__meta">dg@entreprise.com</p>
            </div>
            <span class="org-root__tag"><span class="badge-status badge-status--success">Sans dossier employé</span></span>
          </div>
        </section>

        <div class="org-connector" aria-hidden="true"></div>
        <div class="org-divider" role="separator"><span>4 départements</span></div>

        <section class="org-grid" aria-label="Départements">
          <article class="org-dept">
            <div class="org-dept__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-chart-simple" aria-hidden="true"></i></span>
              <div><h3 class="org-dept__name">Finance</h3><p class="org-dept__count">12 employés</p></div>
            </div>
            <div class="org-chief">
              <span class="avatar avatar--sm" aria-hidden="true">JM</span>
              <div><p class="org-chief__name">Jean-Paul Mbarga</p><span class="badge-status badge-status--success">Chef désigné</span></div>
            </div>
            <ul class="org-members">
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">MN</span><div><p class="org-member__name">Marie Ngo Ndjock</p><p class="org-member__role">Comptable</p></div></li>
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">AN</span><div><p class="org-member__name">Aline Njoya</p><p class="org-member__role">Assistante comptable</p></div></li>
            </ul>
            <p class="org-dept__foot">Équipe rattachée à ce département</p>
          </article>
          <article class="org-dept">
            <div class="org-dept__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-user-group" aria-hidden="true"></i></span>
              <div><h3 class="org-dept__name">Ressources humaines</h3><p class="org-dept__count">4 employés</p></div>
            </div>
            <div class="org-chief">
              <span class="avatar avatar--sm" aria-hidden="true">BM</span>
              <div><p class="org-chief__name">Bruno Manga</p><span class="badge-status badge-status--success">Chef désigné</span></div>
            </div>
            <ul class="org-members">
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">CM</span><div><p class="org-member__name">Chantal Mvondo</p><p class="org-member__role">Responsable RH</p></div></li>
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">BE</span><div><p class="org-member__name">Béatrice Essono</p><p class="org-member__role">Assistante RH</p></div></li>
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">LA</span><div><p class="org-member__name">Lucien Abega</p><p class="org-member__role">Gestionnaire paie</p></div></li>
            </ul>
            <p class="org-dept__foot">Équipe rattachée à ce département</p>
          </article>
          <article class="org-dept">
            <div class="org-dept__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-gear" aria-hidden="true"></i></span>
              <div><h3 class="org-dept__name">Opérations</h3><p class="org-dept__count">20 employés</p></div>
            </div>
            <div class="org-chief">
              <span class="avatar avatar--sm" aria-hidden="true">SF</span>
              <div><p class="org-chief__name">Samuel Fotso</p><span class="badge-status badge-status--success">Chef désigné</span></div>
            </div>
            <ul class="org-members">
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">PT</span><div><p class="org-member__name">Patrick Tchoumi</p><p class="org-member__role">Technicien</p></div></li>
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">JO</span><div><p class="org-member__name">Judith Onana</p><p class="org-member__role">Logisticienne</p></div></li>
            </ul>
            <p class="org-dept__foot">Équipe rattachée à ce département</p>
          </article>
          <article class="org-dept">
            <div class="org-dept__head">
              <span class="icon-tile icon-tile--sm"><i class="fa-solid fa-arrow-trend-up" aria-hidden="true"></i></span>
              <div><h3 class="org-dept__name">Commercial</h3><p class="org-dept__count">12 employés</p></div>
            </div>
            <div class="org-chief">
              <span class="avatar avatar--sm" aria-hidden="true">EN</span>
              <div><p class="org-chief__name">Éric Ngue</p><span class="badge-status badge-status--success">Chef désigné</span></div>
            </div>
            <ul class="org-members">
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">RA</span><div><p class="org-member__name">Rose Atangana</p><p class="org-member__role">Chargée de clientèle</p></div></li>
              <li class="org-member"><span class="avatar avatar--sm" aria-hidden="true">PN</span><div><p class="org-member__name">Paul Nlend</p><p class="org-member__role">Commercial</p></div></li>
            </ul>
            <p class="org-dept__foot">Équipe rattachée à ce département</p>
          </article>
        </section>

        <p class="org-footnote">Chaque chef est un employé désigné de son département. La Direction générale dispose d’un compte sans dossier employé.</p>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
