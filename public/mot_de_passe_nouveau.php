<?php
require __DIR__ . '/../config/init.php';

if (empty($_SESSION['reset_user_id']) || empty($_SESSION['reset_jeton'])) {
    header('Location: mot_de_passe_oublie.php');
    exit;
}
header('Cache-Control: no-store');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf" content="<?= h(csrfToken()) ?>">
  <title>Nouveau mot de passe — PerPay</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="./css/tokens.css">
  <link rel="stylesheet" href="./css/base.css">
  <link rel="stylesheet" href="./css/components.css">
  <link rel="stylesheet" href="./css/auth.css">
</head>
<body>

  <div class="auth-layout">

    <aside class="auth-aside" aria-label="Présentation">
      <h2 class="auth-aside__title">PerPay</h2>
      <span class="auth-aside__bar" aria-hidden="true"></span>
      <p class="auth-aside__quote">
        Une équipe bien gérée,<br>
        <span class="accent">c’est une entreprise plus forte.</span>
      </p>
    </aside>

    <main class="auth-main">
      <section class="auth-card auth-card--box">

        <div class="auth-card__brand-mobile">
          <span class="icon-tile"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
          <span>PerPay</span>
        </div>

        <div class="auth-card__icon d-none d-lg-block">
          <span class="icon-tile"><i class="fa-solid fa-lock" aria-hidden="true"></i></span>
        </div>

        <h1 class="mb-1">Nouveau mot de passe</h1>
        <p class="auth-card__subtitle">Réinitialisation du mot de passe • Étape 3 sur 3.</p>

        <div class="auth-box">
          <div id="reset-succes" class="d-none">
            <div class="alert alert-success" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>
              Votre mot de passe a été modifié. Vous pouvez vous connecter.
            </div>
            <a href="login.php" class="btn btn-primary btn-submit text-decoration-none">
              Se connecter <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
          </div>

          <div id="reset-saisie">
            <div id="reset-erreur" class="alert alert-danger d-none" role="alert">
              <i class="fa-solid fa-circle-exclamation mt-1" aria-hidden="true"></i>
              <span data-erreur-texte></span>
            </div>

            <p class="auth-box__intro">Choisissez un nouveau mot de passe pour votre compte.</p>

            <form id="reset-form" class="needs-validation" novalidate>
              <div class="mb-3">
                <label for="nouveau" class="form-label">Nouveau mot de passe</label>
                <div class="input-icon">
                  <i class="fa-solid fa-lock input-icon__left" aria-hidden="true"></i>
                  <input type="password" id="nouveau" name="nouveau_mot_de_passe" class="form-control has-action"
                         pattern="(?=.*[A-Za-z])(?=.*[0-9]).{8,}" autocomplete="new-password" required>
                  <button type="button" class="input-icon__action" data-toggle-password="nouveau"
                          aria-label="Afficher le mot de passe" aria-pressed="false">
                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                  </button>
                  <div class="invalid-feedback">8 caractères minimum, avec au moins une lettre et un chiffre.</div>
                </div>
                <p class="password-rules">8 caractères minimum, avec au moins une lettre et un chiffre.</p>
              </div>

              <div class="mb-4">
                <label for="confirmation" class="form-label">Confirmer le mot de passe</label>
                <div class="input-icon">
                  <i class="fa-solid fa-lock input-icon__left" aria-hidden="true"></i>
                  <input type="password" id="confirmation" name="confirmation" class="form-control has-action"
                         autocomplete="new-password" required>
                  <button type="button" class="input-icon__action" data-toggle-password="confirmation"
                          aria-label="Afficher le mot de passe" aria-pressed="false">
                    <i class="fa-regular fa-eye" aria-hidden="true"></i>
                  </button>
                  <div class="invalid-feedback">Les deux mots de passe doivent être identiques.</div>
                </div>
              </div>

              <button type="submit" class="btn btn-primary btn-submit">
                Enregistrer le mot de passe <i class="fa-solid fa-check" aria-hidden="true"></i>
              </button>
            </form>
          </div>
        </div>

      </section>
    </main>
  </div>

  <script>
    (function () {
      document.querySelectorAll('[data-toggle-password]').forEach(function (bouton) {
        var champ = document.getElementById(bouton.getAttribute('data-toggle-password'));
        var icone = bouton.querySelector('i');

        bouton.addEventListener('click', function () {
          var visible = champ.type === 'text';
          champ.type = visible ? 'password' : 'text';
          icone.className = visible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
          bouton.setAttribute('aria-pressed', String(!visible));
          bouton.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
        });
      });

      var jeton = document.querySelector('meta[name="csrf"]').content;
      var form = document.getElementById('reset-form');
      var erreur = document.getElementById('reset-erreur');
      var envoyer = form.querySelector('button[type="submit"]');
      var nouveau = document.getElementById('nouveau');
      var confirmation = document.getElementById('confirmation');

      function verifierConfirmation() {
        confirmation.setCustomValidity(confirmation.value === nouveau.value ? '' : 'Différent');
      }

      nouveau.addEventListener('input', verifierConfirmation);
      confirmation.addEventListener('input', verifierConfirmation);

      function montrerErreur(texte) {
        erreur.querySelector('[data-erreur-texte]').textContent = texte;
        erreur.classList.remove('d-none');
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        verifierConfirmation();
        form.classList.add('was-validated');
        erreur.classList.add('d-none');

        if (!form.checkValidity()) { return; }

        envoyer.disabled = true;
        fetch('../api/reinitialiser_mdp.php', {
          method: 'POST',
          headers: { 'X-CSRF-Token': jeton, 'Accept': 'application/json' },
          body: new FormData(form)
        })
          .then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (d) {
            if (d.ok) {
              document.getElementById('reset-saisie').classList.add('d-none');
              document.getElementById('reset-succes').classList.remove('d-none');
              return;
            }
            montrerErreur(d.message || 'Une erreur est survenue.');
            if (d.redirection) {
              setTimeout(function () { window.location.href = d.redirection; }, 2500);
              return;
            }
            envoyer.disabled = false;
          })
          .catch(function () {
            montrerErreur('Serveur injoignable. Réessayez.');
            envoyer.disabled = false;
          });
      });
    })();
  </script>
</body>
</html>
