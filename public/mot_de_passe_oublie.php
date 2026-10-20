<?php
require __DIR__ . '/../config/init.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf" content="<?= h(csrfToken()) ?>">
  <title>Mot de passe oublié — PerPay</title>

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
          <span class="icon-tile"><i class="fa-solid fa-key" aria-hidden="true"></i></span>
        </div>

        <h1 class="mb-1">Mot de passe oublié</h1>
        <p class="auth-card__subtitle">Réinitialisation du mot de passe • Étape 1 sur 3.</p>

        <div class="auth-box">
          <p class="auth-box__intro">
            Indiquez l’e-mail associé à votre compte. Vous recevrez un code
            à 6 chiffres pour choisir un nouveau mot de passe.
          </p>

          <div id="oubli-erreur" class="alert alert-danger d-none" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-1" aria-hidden="true"></i>
            <span data-erreur-texte></span>
          </div>

          <form id="reset-form" class="needs-validation" novalidate>
            <div class="mb-3">
              <label for="email" class="form-label">E-mail professionnel</label>
              <div class="input-icon">
                <i class="fa-regular fa-envelope input-icon__left" aria-hidden="true"></i>
                <input type="email" id="email" name="email" class="form-control"
                       placeholder="exemple@domaine.fr" autocomplete="username" required>
                <div class="invalid-feedback">Saisissez un e-mail professionnel valide.</div>
              </div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit">
              Envoyer le code <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
          </form>

          <div class="auth-divider" aria-hidden="true">ou</div>

          <a href="login.php" class="btn btn-neutral w-100 text-decoration-none">
            <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Retour à la connexion
          </a>
        </div>

      </section>
    </main>
  </div>

  <script>
    (function () {
      var jeton   = document.querySelector('meta[name="csrf"]').content;
      var form    = document.getElementById('reset-form');
      var erreur  = document.getElementById('oubli-erreur');
      var bouton  = form.querySelector('button[type="submit"]');

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        form.classList.add('was-validated');
        erreur.classList.add('d-none');
        if (!form.checkValidity()) { return; }

        bouton.disabled = true;
        fetch('../api/mot_de_passe_oublie.php', {
          method: 'POST',
          headers: { 'X-CSRF-Token': jeton, 'Accept': 'application/json' },
          body: new FormData(form)
        })
          .then(function (r) { return r.json().catch(function () { return {}; }); })
          .then(function (d) {
            if (d.ok) { window.location.href = 'mot_de_passe_code.php'; return; }
            erreur.querySelector('[data-erreur-texte]').textContent = d.message || 'Une erreur est survenue.';
            erreur.classList.remove('d-none');
            bouton.disabled = false;
          })
          .catch(function () {
            erreur.querySelector('[data-erreur-texte]').textContent = 'Serveur injoignable. Réessayez.';
            erreur.classList.remove('d-none');
            bouton.disabled = false;
          });
      });
    })();
  </script>
</body>
</html>