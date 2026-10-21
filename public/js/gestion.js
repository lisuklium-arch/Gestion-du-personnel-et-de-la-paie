(function () {
  'use strict';

  document.querySelectorAll('[data-autosubmit]').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  document.querySelectorAll('[data-demo-form]').forEach(function (form) {
    var confirmation = document.getElementById(form.getAttribute('data-confirmation') || '');

    form.addEventListener('submit', function (event) { event.preventDefault(); });

    form.querySelectorAll('input, textarea, select').forEach(function (champ) {
      champ.addEventListener('input', function () { champ.classList.remove('is-invalid'); });
    });

    form.querySelectorAll('[data-message]').forEach(function (bouton) {
      bouton.addEventListener('click', function () {
        var requis = bouton.getAttribute('data-requis');
        var champ = requis ? form.querySelector(requis) : null;

        if (champ && !champ.value.trim()) {
          champ.classList.add('is-invalid');
          champ.focus();
          return;
        }

        if (confirmation) {
          confirmation.querySelector('[data-confirmation-label]').textContent = bouton.getAttribute('data-message');
          confirmation.classList.remove('d-none');
        }

        if (form.hasAttribute('data-verrou') || bouton.hasAttribute('data-verrou')) {
          form.querySelectorAll('button, input, textarea, select').forEach(function (element) {
            element.disabled = true;
          });
        }
      });
    });
  });

  document.querySelectorAll('[data-paie]').forEach(function (form) {
    var base = Number(form.getAttribute('data-base')) || 0;
    var extras = Number(form.getAttribute('data-extras')) || 0;
    var avance = Number(form.getAttribute('data-avance')) || 0;
    var prime = form.querySelector('[name="prime"]');
    var retenues = form.querySelector('[name="retenues"]');

    function format(valeur) {
      return valeur.toLocaleString('fr-FR');
    }

    function calculer() {
      var brut = base + extras + (Number(prime.value) || 0);
      var totalRetenues = (Number(retenues.value) || 0) + avance;
      var net = brut - totalRetenues;
      form.querySelector('[data-sortie="brut"]').textContent = format(brut);
      form.querySelector('[data-sortie="retenues"]').textContent = format(totalRetenues);
      form.querySelector('[data-sortie="net"]').textContent = format(net);
    }

    prime.addEventListener('input', calculer);
    retenues.addEventListener('input', calculer);
    calculer();
  });

  document.querySelectorAll('[data-contrat-type]').forEach(function (select) {
    var fin = document.getElementById(select.getAttribute('data-contrat-type'));
    if (!fin) { return; }

    function mettreAJour() {
      fin.required = select.value !== '' && select.value !== 'CDI';
    }

    select.addEventListener('change', mettreAJour);
    mettreAJour();
  });

  document.querySelectorAll('[data-compte-action]').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
      var ligne = bouton.closest('[data-compte]');
      var nom = ligne.getAttribute('data-compte');
      var desactiver = bouton.getAttribute('data-compte-action') === 'desactiver';
      var pastille = ligne.querySelector('[data-compte-etat]');
      var confirmation = document.getElementById('acces-confirmation');

      pastille.className = 'badge-status badge-status--dot badge-status--' + (desactiver ? 'danger' : 'success');
      pastille.textContent = desactiver ? 'Compte désactivé' : 'Compte actif';

      bouton.setAttribute('data-compte-action', desactiver ? 'reactiver' : 'desactiver');
      bouton.className = 'btn text-decoration-none ' + (desactiver ? 'btn-neutral' : 'btn-danger-outline');
      bouton.querySelector('[data-libelle]').textContent = (desactiver ? 'Réactiver ' : 'Désactiver ') + nom;

      confirmation.querySelector('[data-confirmation-label]').textContent =
        'Compte de ' + nom + (desactiver ? ' désactivé' : ' réactivé') + '. L’action est journalisée.';
      confirmation.classList.remove('d-none');
    });
  });

  document.querySelectorAll('[data-role-rh]').forEach(function (form) {
    var roleRh = form.getAttribute('data-role-rh');
    var messageRh = form.getAttribute('data-message-role-rh');
    var confirmation = document.getElementById(form.getAttribute('data-confirmation') || '');
    var selectRole = form.querySelector('[name="role"]');

    form.addEventListener('form:valid', function () {
      if (confirmation && selectRole && messageRh && selectRole.value === roleRh) {
        confirmation.querySelector('[data-confirmation-label]').textContent = messageRh;
      }
    });
  });
})();
