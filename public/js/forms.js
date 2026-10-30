(function () {
  'use strict';

  var MAX_MB_DEFAULT = 5;

  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    form.querySelectorAll('input[type="file"]').forEach(function (input) {
      input.addEventListener('change', function () {
        var maxMb = parseFloat(input.getAttribute('data-max-mb')) || MAX_MB_DEFAULT;
        var file = input.files && input.files[0];
        var tooBig = !!file && file.size > maxMb * 1024 * 1024;

        input.setCustomValidity(tooBig ? 'Fichier trop volumineux.' : '');
        input.classList.toggle('is-invalid', tooBig);
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      form.classList.add('was-validated');

      var confirmation = document.getElementById(form.getAttribute('data-confirmation') || '');

      var api = form.getAttribute('data-api');
      var erreur = document.getElementById(form.getAttribute('data-erreur') || 'compte-erreur');
      if (erreur) { erreur.classList.add('d-none'); }

      if (!form.checkValidity()) {
        if (confirmation) { confirmation.classList.add('d-none'); }
        return;
      }

      function succes(texte) {
        if (confirmation) {
          var cible = confirmation.querySelector('[data-confirmation-label]');
          var message = texte || form.getAttribute('data-confirmation-text');
          if (message && cible) { cible.textContent = message; }
          confirmation.classList.remove('d-none');
          confirmation.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
        form.dispatchEvent(new CustomEvent('form:valid', { bubbles: true }));
      }

      if (!api) { succes(); return; }

      var bouton = form.querySelector('button[type="submit"]');
      var meta = document.querySelector('meta[name="csrf"]');
      if (bouton) { bouton.disabled = true; }

      function echec(texte) {
        if (bouton) { bouton.disabled = false; }
        if (confirmation) { confirmation.classList.add('d-none'); }
        if (erreur) {
          erreur.textContent = texte;
          erreur.classList.remove('d-none');
          erreur.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
      }

      fetch(api, {
        method: 'POST',
        headers: { 'X-CSRF-Token': meta ? meta.content : '', 'Accept': 'application/json' },
        body: new FormData(form)
      }).then(function (reponse) {
        return reponse.json().catch(function () { return {}; }).then(function (json) {
          if (reponse.ok && json.ok !== false) {
            succes(json.statut === 'en_attente' ? form.getAttribute('data-message-role-rh') : '');
            form.querySelectorAll('input, select, button').forEach(function (el) { el.disabled = true; });
          } else {
            echec(json.message || (reponse.status === 403 ? 'Session expirée ou accès refusé. Rechargez la page.' : 'Une erreur est survenue.'));
          }
        });
      }).catch(function () {
        echec('Connexion impossible. Réessayez.');
      });
    });
  });
})();
