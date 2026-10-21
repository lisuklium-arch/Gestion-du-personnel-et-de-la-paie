<?php
require __DIR__ . '/../config/init.php';

// Déjà connecté : inutile d'afficher le formulaire
if (utilisateurConnecte() && accueilPourRoleCourant() !== null) {
  header('Location: ' . accueilPourRoleCourant());
  exit;
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Connexion — PerPay</title>
  <meta name="csrf" content="<?= h(csrfToken()) ?>">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

  <link rel="stylesheet" href="css/tokens.css">
  <link rel="stylesheet" href="css/base.css">
  <link rel="stylesheet" href="css/components.css">
  <link rel="stylesheet" href="css/auth.css">
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
      <section class="auth-card">

        <div class="auth-card__brand-mobile">
          <span class="icon-tile"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
          <span>PerPay</span>
        </div>

        <div class="auth-card__icon d-none d-lg-block">
          <span class="icon-tile"><i class="fa-solid fa-users" aria-hidden="true"></i></span>
        </div>

        <h1 class="mb-1">Connexion</h1>
        <p class="auth-card__subtitle">Accédez à votre espace personnel ou administratif.</p>

        <div id="login-error" class="alert alert-danger d-none" role="alert">
          <i class="fa-solid fa-circle-exclamation mt-1" aria-hidden="true"></i>
          <span>E-mail ou mot de passe incorrect.</span>
        </div>

        <form id="login-form" class="needs-validation" novalidate>

          <div class="mb-3">
            <label for="email" class="form-label">Adresse e-mail</label>
            <div class="input-icon">
              <i class="fa-regular fa-envelope input-icon__left" aria-hidden="true"></i>
              <input type="email" id="email" name="email" class="form-control" placeholder="exemple@domaine.fr"
                autocomplete="username" required>
              <div class="invalid-feedback">Saisissez un e-mail professionnel valide.</div>
            </div>
          </div>

          <div class="mb-3">
            <label for="password" class="form-label">Mot de passe</label>
            <div class="input-icon">
              <i class="fa-solid fa-lock input-icon__left" aria-hidden="true"></i>
              <input type="password" id="password" name="password" class="form-control has-action"
                placeholder="Votre mot de passe" autocomplete="current-password" required>
              <button type="button" class="input-icon__action" id="toggle-password"
                aria-label="Afficher le mot de passe" aria-pressed="false">
                <i class="fa-regular fa-eye" aria-hidden="true"></i>
              </button>
              <div class="invalid-feedback">Saisissez votre mot de passe.</div>
            </div>
          </div>

          <div class="auth-options">
            <div class="form-check mb-0">
              <input class="form-check-input" type="checkbox" id="remember" name="remember">
              <label class="form-check-label text-muted" for="remember">Se souvenir de moi</label>
            </div>
            <a href="mot_de_passe_oublie.php">Mot de passe oublié ?</a>
          </div>

          <button type="submit" class="btn btn-primary btn-submit">
            Se connecter <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
          </button>
        </form>

        <div class="note-info mt-4" role="note">
          <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
          <span>
            Votre compte doit être actif. Les comptes non actifs ne
            peuvent pas se connecter.
          </span>
        </div>

      </section>
    </main>
  </div>

  <script>
    (function () {
      var input = document.getElementById('password');
      var button = document.getElementById('toggle-password');
      var icon = button.querySelector('i');

      button.addEventListener('click', function () {
        var visible = input.type === 'text';
        input.type = visible ? 'password' : 'text';
        icon.className = visible ? 'fa-regular fa-eye' : 'fa-regular fa-eye-slash';
        button.setAttribute('aria-pressed', String(!visible));
        button.setAttribute('aria-label', visible ? 'Afficher le mot de passe' : 'Masquer le mot de passe');
      });
    })();

    (function () {
      var form = document.getElementById('login-form');
      var erreur = document.getElementById('login-error');
      var texte = erreur.querySelector('span');
      var bouton = form.querySelector('button[type="submit"]');
      var jeton = document.querySelector('meta[name="csrf"]').content;

      function afficherErreur(message) {
        texte.textContent = message;
        erreur.classList.remove('d-none');
      }

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        form.classList.add('was-validated');
        erreur.classList.add('d-none');

        if (!form.checkValidity()) { return; }

        bouton.disabled = true;

        fetch('../api/login.php', {
          method: 'POST',
          headers: { 'X-CSRF-Token': jeton, 'Accept': 'application/json' },
          body: new FormData(form),
          credentials: 'same-origin'
        })
          .then(function (reponse) {
            return reponse.json().then(
              function (donnees) { return { ok: reponse.ok, donnees: donnees }; },
              function () { return { ok: false, donnees: { message: 'Session expirée. Rechargez la page.' } }; }
            );
          })
          .then(function (resultat) {
            if (!resultat.ok) {
              afficherErreur(resultat.donnees.message || 'Connexion impossible.');
              bouton.disabled = false;
              return;
            }
            window.location.href = resultat.donnees.redirection;
          })
          .catch(function () {
            afficherErreur('Serveur injoignable. Réessayez.');
            bouton.disabled = false;
          });
      });
    })();
  </script>
</body>

</html>