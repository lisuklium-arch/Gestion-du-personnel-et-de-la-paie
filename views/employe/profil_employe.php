<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Profil — PerPay</title>

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

    <?php $active = 'profil'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Profil</h1>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Mon dossier personnel • Informations en lecture seule</p>

        <div class="app-split">

          <div class="app-stack">
            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-user" aria-hidden="true"></i></span><h2 class="card-panel__title">Identité et coordonnées</h2><div class="card-panel__aside"><span class="badge-status badge-status--success badge-status--dot">Actif</span></div></div>
              <dl class="info-grid">
                <div><dt>Nom complet</dt><dd>Marie Ngo Ndjock</dd></div>
                <div><dt>Matricule</dt><dd><span class="chip">EMP-0042</span></dd></div>
                <div><dt>Date de naissance</dt><dd><i class="fa-regular fa-calendar" aria-hidden="true"></i>14/05/1992</dd></div>
                <div><dt>Téléphone</dt><dd><i class="fa-solid fa-phone" aria-hidden="true"></i>+237 699 12 34 56</dd></div>
                <div><dt>E-mail professionnel</dt><dd><i class="fa-regular fa-envelope" aria-hidden="true"></i>marie.ngo@entreprise.com</dd></div>
                <div><dt>Adresse</dt><dd><i class="fa-solid fa-location-dot" aria-hidden="true"></i>Bonamoussadi, Douala</dd></div>
              </dl>
            </section>

            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span><h2 class="card-panel__title">Situation professionnelle</h2></div>
              <dl class="info-grid">
                <div><dt>Département / poste</dt><dd><span class="chip chip--success">Finance</span> • Comptable</dd></div>
                <div><dt>Chef de département</dt><dd><i class="fa-solid fa-user-tie" aria-hidden="true"></i>Jean-Paul Mbarga</dd></div>
                <div><dt>Date d’embauche</dt><dd><i class="fa-regular fa-calendar" aria-hidden="true"></i>01/03/2023</dd></div>
                <div><dt>Contrat en cours</dt><dd><span class="chip">CDI</span> 300 000 FCFA de base</dd></div>
                <div><dt>Compte de connexion</dt><dd><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Rôle Employé <span class="badge-status badge-status--success">Actif</span></dd></div>
              </dl>
            </section>
          </div>

          <div class="app-stack">
            <section class="card-panel">
              <div class="app-person app-person--center">
                <span class="avatar avatar--lg" aria-hidden="true">MN</span>
                <div>
                  <p class="app-person__name">Marie Ngo Ndjock</p>
                  <p class="app-person__meta">Comptable • Finance</p>
                </div>
                <span class="badge-status badge-status--success badge-status--dot">Actif</span>
              </div>
            </section>

            <div class="note-info note-info--warning" role="note">
              <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
              <div>
                <p><strong>Informations en lecture seule</strong></p>
                <p>Une modification de coordonnées doit être validée par la RH.</p>
              </div>
            </div>

            <a href="profil_modification_employe.php" class="btn btn-primary text-decoration-none">
              <i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Demander une modification
            </a>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
