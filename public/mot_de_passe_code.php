<?php
require __DIR__ . '/../config/init.php';

$emailReset = $_SESSION['reset_email'] ?? '';
if ($emailReset === '') {
    header('Location: mot_de_passe_oublie.php');
    exit;
}
$parts = explode('@', $emailReset, 2);
$emailMasque = substr($parts[0], 0, 1) . '***@' . ($parts[1] ?? '');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf" content="<?= h(csrfToken()) ?>">
  <title>Saisir le code — PerPay</title>

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
          <span class="icon-tile"><i class="fa-solid fa-envelope-open-text" aria-hidden="true"></i></span>
        </div>

        <h1 class="mb-1">Saisir le code</h1>
        <p class="auth-card__subtitle">Réinitialisation du mot de passe • Étape 2 sur 3.</p>

        <div class="auth-box">
          <p class="auth-box__intro">
            Un code à 6 chiffres a été envoyé à <strong id="email-masque"><?= h($emailMasque) ?></strong>.
            Il est valable 15 minutes et ne peut servir qu’une seule fois.
          </p>

          <div id="code-erreur" class="alert alert-danger d-none" role="alert">
            <i class="fa-solid fa-circle-exclamation mt-1" aria-hidden="true"></i>
            <span data-erreur-texte>Code incorrect.</span>
          </div>
          <div id="code-renvoye" class="alert alert-success d-none" role="status" aria-live="polite">
            <i class="fa-solid fa-circle-check me-1" aria-hidden="true"></i>
            Un nouveau code vient d’être envoyé.
          </div>

          <form id="code-form" class="needs-validation" novalidate>
            <div class="mb-3">
              <label for="code" class="form-label">Code de vérification</label>
              <input type="text" id="code" name="code" class="form-control otp-input"
                     inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                     autocomplete="one-time-code" placeholder="000000" required>
              <div class="invalid-feedback">Saisissez le code à 6 chiffres reçu par e-mail.</div>
            </div>

            <button type="submit" class="btn btn-primary btn-submit">
              Vérifier le code <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </button>
          </form>

          <p class="auth-resend">
            Vous n’avez rien reçu ?
            <button type="button" id="renvoyer">Renvoyer le code</button>
            <span id="decompte"></span>
          </p>

          <div class="auth-divider" aria-hidden="true">ou</div>

          <a href="mot_de_passe_oublie.php" class="btn btn-neutral w-100 text-decoration-none">
            <i class="fa-solid fa-arrow-left me-2" aria-hidden="true"></i>Changer d’adresse e-mail
          </a>
        </div>

      </section>
    </main>
  </div>

  <script>
    (function () {
      var DELAI_RENVOI = 180;

      var jeton   = document.querySelector('meta[name="csrf"]').content;
      var form    = document.getElementById('code-form');
      var champ   = document.getElementById('code');
      var erreur  = document.getElementById('code-erreur');
      var renvoye = document.getElementById('code-renvoye');
      var bouton  = document.getElementById('renvoyer');
      var decompte = document.getElementById('decompte');
      var valider = form.querySelector('button[type="submit"]');

      function montrerErreur(texte) {
        erreur.querySelector('[data-erreur-texte]').textContent = texte;
        erreur.classList.remove('d-none');
      }

      function appeler(url, donnees) {
        return fetch(url, {
          method: 'POST',
          headers: { 'X-CSRF-Token': jeton, 'Accept': 'application/json' },
          body: donnees
        }).then(function (r) { return r.json().catch(function () { return {}; }); });
      }

      champ.addEventListener('input', function () {
        champ.value = champ.value.replace(/[^0-9]/g, '');
        erreur.classList.add('d-none');
      });

      form.addEventListener('submit', function (event) {
        event.preventDefault();
        form.classList.add('was-validated');
        erreur.classList.add('d-none');
        if (!form.checkValidity()) { return; }

        valider.disabled = true;
        appeler('../api/verifier_code_mdp.php', new FormData(form))
          .then(function (d) {
            if (d.ok || d.redirection) { window.location.href = d.redirection; return; }
            montrerErreur(d.message || 'Code incorrect ou expiré.');
            valider.disabled = false;
          })
          .catch(function () {
            montrerErreur('Serveur injoignable. Réessayez.');
            valider.disabled = false;
          });
      });

      function lancerDecompte() {
        var reste = DELAI_RENVOI;
        bouton.disabled = true;
        decompte.textContent = '(' + reste + ' s)';
        var minuterie = setInterval(function () {
          reste--;
          decompte.textContent = '(' + reste + ' s)';
          if (reste <= 0) {
            clearInterval(minuterie);
            decompte.textContent = '';
            bouton.disabled = false;
          }
        }, 1000);
      }

      bouton.addEventListener('click', function () {
        bouton.disabled = true;
        erreur.classList.add('d-none');
        appeler('../api/mot_de_passe_oublie.php', new FormData())
          .then(function (d) {
            if (d.ok) {
              champ.value = '';
              renvoye.classList.remove('d-none');
              lancerDecompte();
            } else {
              montrerErreur(d.message || 'Impossible de renvoyer le code.');
              bouton.disabled = false;
            }
          })
          .catch(function () {
            montrerErreur('Serveur injoignable. Réessayez.');
            bouton.disabled = false;
          });
      });

      lancerDecompte();
    })();
  </script>
</body>
</html>
