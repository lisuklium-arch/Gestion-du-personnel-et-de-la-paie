<?php
require_once __DIR__ . '/../../config/init.php';
require_once __DIR__ .'/../../models/Employe.php';
require_once __DIR__ . '/../gestion/avatar.php';
exigerRole(['employe', 'chef', 'rh']);

$employes = new Employe($pdo);
$resume = $employes->trouverParUtilisateurId((int) $_SESSION['utilisateur_id']);
$moi = $resume === null ? null : $employes->trouver((int) $resume['id']);
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
  <?php include __DIR__ . '/../layout/head_notifications.php'; ?>
</head>
<body>

  <div class="app-shell">

    <?php $active = 'profil'; include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Profil</h1>
        </div>
        <?php include __DIR__ . '/../layout/cloche.php'; ?>
      </header>

      <main class="app-content">
        <p class="app-meta"><i class="fa-solid fa-lock me-1" aria-hidden="true"></i>Mon dossier personnel • Informations en lecture seule</p>

        <div class="app-split">

          <div class="app-stack">
            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-regular fa-user" aria-hidden="true"></i></span><h2 class="card-panel__title">Identité et coordonnées</h2><div class="card-panel__aside"><span class="badge-status badge-status--success badge-status--dot">Actif</span></div></div>
              <dl class="info-grid">
                <div><dt>Nom complet</dt><dd><?= h($moi['prenom'] . ' ' . $moi['nom']) ?></dd></div>
                <div><dt>Matricule</dt><dd><span class="chip"><?= h($moi['matricule']) ?></span></dd></div>
                <div><dt>Date de naissance</dt><dd><i class="fa-regular fa-calendar" aria-hidden="true"></i><?= $moi['date_naissance'] ? h((new DateTime($moi['date_naissance']))->format('d/m/Y')) : '—' ?></dd></div>
                <div><dt>Téléphone</dt><dd><i class="fa-solid fa-phone" aria-hidden="true"></i><?= h((string) $moi['telephone']) ?></dd></div>
                <div><dt>Lieu de résidence</dt><dd><i class="fa-solid fa-location-dot" aria-hidden="true"></i><?= h((string) ($moi['lieu_residence'] ?: 'Non renseigné')) ?></dd></div>
                <div><dt>E-mail professionnel</dt><dd><i class="fa-regular fa-envelope" aria-hidden="true"></i><?= h($moi['email']) ?></dd></div>
              </dl>
            </section>

            <section class="card-panel">
              <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-briefcase" aria-hidden="true"></i></span><h2 class="card-panel__title">Situation professionnelle</h2></div>
              <dl class="info-grid">
                <div><dt>Département / poste</dt><dd><span class="chip chip--success"><?= h($moi['departement']) ?></span> • <?= h($moi['poste']) ?></dd></div>
                <div><dt>Chef de département</dt><dd><i class="fa-solid fa-user-tie" aria-hidden="true"></i><?= h(trim(($moi['chef_prenom'] ?? '') . ' ' . ($moi['chef_nom'] ?? ''))) ?></dd></div>
                <div><dt>Date d’embauche</dt><dd><i class="fa-regular fa-calendar" aria-hidden="true"></i><?= h((new DateTime($moi['date_embauche']))->format('d/m/Y')) ?></dd></div>
                <div><dt>Contrat en cours</dt><dd><span class="chip">CDI</span> 300 000 FCFA de base</dd></div>
                <div><dt>Compte de connexion</dt><dd><i class="fa-solid fa-shield-halved" aria-hidden="true"></i>Rôle Employé <span class="badge-status badge-status--success">Actif</span></dd></div>
              </dl>
            </section>
          </div>

          <div class="app-stack">
            <section class="card-panel">
              <div class="app-person app-person--center">
                <?= avatarEmploye($moi, 'lg') ?>
                <div>
                  <p class="app-person__name"><?= h($moi['prenom'] . ' ' . $moi['nom']) ?></p>
                  <p class="app-person__meta"><?= h($moi['poste'] . ' • ' . $moi['departement']) ?></p>
                </div>
                <span class="badge-status badge-status--success badge-status--dot">Actif</span>
              </div>
            </section>

            <section class="card-panel card-panel--form">
              <div class="card-panel__head"><h2 class="card-panel__title">Photo de profil</h2></div>
              <div id="photo-confirmation" class="alert alert-success d-none" role="status"></div>
              <div id="photo-erreur" class="alert alert-danger d-none" role="alert"></div>
              <form id="form-photo-profil" class="needs-validation" novalidate enctype="multipart/form-data">
                <input type="hidden" name="csrf" value="<?= h(csrfToken()) ?>">
                <label for="photo-profil" class="form-label">Remplacer ma photo</label>
                <input id="photo-profil" type="file" name="photo" class="form-control" accept=".jpg,.jpeg,.png" data-max-mb="2" required>
                <div class="invalid-feedback">Choisissez une photo JPG ou PNG de 2 Mo maximum.</div>
                <img id="photo-profil-apercu" class="avatar-preview d-none" alt="Aperçu de la nouvelle photo">
                <div class="app-form-actions"><button type="submit" class="btn btn-neutral">Enregistrer la photo</button></div>
              </form>
            </section>

            <div class="note-info note-info--warning" role="note">
              <i class="fa-solid fa-circle-exclamation" aria-hidden="true"></i>
              <div>
                <p><strong>Informations en lecture seule</strong></p>
                <p>Une modification de coordonnées doit être validée par la RH.</p>
              </div>
            </div>

            <a href="profil_modification_employe.php" class="btn btn-primary text-decoration-none">
              <i class="fa-regular fa-pen-to-square me-2" aria-hidden="true"></i>Demander ou suivre une modification
            </a>
          </div>
        </div>
      </main>
    </div>
  </div>

  <?php include __DIR__ . '/../layout/navbar_mobile.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="/public/js/forms.js"></script>
  <script src="/public/js/photo-profil.js"></script>
</body>
</html>
