<?php
require_once __DIR__ . '/../../config/init.php';
exigerRole(['employe', 'chef', 'rh']);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Demander une modification — PerPay</title>

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

    <?php $active = 'profil';
    include __DIR__ . '/../layout/navbar.php'; ?>

    <div class="app-main">

      <header class="app-header">
        <div class="app-header__heading">
          <h1 class="app-header__title">Demander une modification</h1>
          <p class="app-header__sub"><a href="profil_employe.php">Profil</a> · Mise à jour du dossier personnel</p>
        </div>
        <button type="button" class="icon-btn" aria-label="Notifications">
          <i class="fa-regular fa-bell" aria-hidden="true"></i>
        </button>
      </header>

      <main class="app-content">
        <p class="app-meta app-meta--chips">
          <span class="avatar avatar--sm" aria-hidden="true">MN</span>
          <span>Marie Ngo Ndjock</span>
          <span class="chip chip--success">EMP-0042</span>
          <span>Mise à jour du dossier personnel</span>
        </p>

        <section class="card-panel card-panel--form">
          <div class="card-panel__head"><span class="icon-tile icon-tile--sm"><i class="fa-solid fa-pen"
                aria-hidden="true"></i></span>
            <h2 class="card-panel__title">Coordonnée à modifier</h2>
          </div>

          <div id="profil-confirmation" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>Demande envoyée. Votre dossier reste
            inchangé jusqu’à la validation de la RH.
          </div>

          <form id="form-profil" class="needs-validation" novalidate data-confirmation="profil-confirmation">

            <div class="mb-3">
              <label for="champ-concerne" class="form-label">Champ concerné</label>
              <select id="champ-concerne" name="champ" class="form-select" required>
                <option selected>Téléphone</option>
                <option>E-mail professionnel</option>
                <option>Adresse</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="ancienne-valeur" class="form-label">Ancienne valeur</label>
              <div class="input-icon">
                <i class="fa-solid fa-phone input-icon__left" aria-hidden="true"></i>
                <input type="text" id="ancienne-valeur" class="form-control" value="+237 699 12 34 56" readonly>
              </div>
            </div>

            <div class="mb-3">
              <label for="nouvelle-valeur" class="form-label">Nouvelle valeur</label>
              <div class="input-icon">
                <i class="fa-solid fa-phone input-icon__left" aria-hidden="true"></i>
                <input type="tel" id="nouvelle-valeur" name="nouvelle_valeur" class="form-control"
                  value="+237 677 45 67 89" required>
                <div class="invalid-feedback">Saisissez la nouvelle valeur.</div>
              </div>
            </div>

            <div class="mb-3">
              <label for="motif-profil" class="form-label">Motif</label>
              <textarea id="motif-profil" name="motif" class="form-control" rows="2"
                required>J’ai changé de numéro personnel. Merci de mettre à jour mes coordonnées de contact.</textarea>
              <div class="invalid-feedback">Indiquez le motif de votre demande.</div>
            </div>

            <div class="note-info note-info--success mb-3" role="note">
              <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
              <span>Votre numéro actuel reste inchangé jusqu’à la validation de la RH. La demande et sa décision seront
                conservées dans le journal.</span>
            </div>

            <fieldset class="upload-group">
              <legend class="upload-group__legend">Justificatifs <span class="field-tag">Facultatifs</span></legend>
              <p class="upload-group__intro">Vous pouvez joindre jusqu’à 2 pièces (PDF, JPG ou PNG, 5 Mo maximum
                chacune). Aucune n’est obligatoire.</p>
              <div class="mb-3">
                <label for="profil-justificatif-1" class="form-label">Justificatif 1 <span
                    class="field-tag">Optionnel</span></label>
                <input type="file" id="profil-justificatif-1" name="justificatif_1" class="form-control"
                  accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
                <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
              </div>
              <div class="mb-0">
                <label for="profil-justificatif-2" class="form-label">Justificatif 2 <span
                    class="field-tag">Optionnel</span></label>
                <input type="file" id="profil-justificatif-2" name="justificatif_2" class="form-control"
                  accept=".pdf,.jpg,.jpeg,.png" data-max-mb="5">
                <div class="invalid-feedback">Fichier trop volumineux (5 Mo maximum).</div>
              </div>
            </fieldset>

            <div class="app-form-actions">
              <button type="submit" class="btn btn-primary"><i class="fa-regular fa-paper-plane me-2"
                  aria-hidden="true"></i>Envoyer la demande</button>
              <a href="profil_employe.php" class="btn btn-neutral text-decoration-none">Annuler</a>
            </div>
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