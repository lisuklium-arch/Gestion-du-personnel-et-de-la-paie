(function () {
  'use strict';

  document.querySelectorAll('[data-autosubmit]').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  document.querySelectorAll('form[data-recharger]').forEach(function (form) {
    form.addEventListener('form:valid', function () {
      setTimeout(function () { window.location.reload(); }, 1200);
    });
  });

  document.querySelectorAll('[data-supprimer]').forEach(function (bouton) {
    var libelle = bouton.querySelector('[data-libelle]');
    var texteInitial = libelle.textContent;
    var minuterie = null;

    function reinitialiser() {
      libelle.textContent = texteInitial;
      bouton.removeAttribute('data-arme');
    }

    bouton.addEventListener('click', function () {
      var erreur = document.getElementById(bouton.getAttribute('data-erreur'));
      var confirmation = document.getElementById(bouton.getAttribute('data-confirmation'));

      if (!bouton.hasAttribute('data-arme')) {
        bouton.setAttribute('data-arme', '1');
        libelle.textContent = 'Confirmer la suppression';
        clearTimeout(minuterie);
        minuterie = setTimeout(reinitialiser, 6000);
        return;
      }

      clearTimeout(minuterie);
      bouton.disabled = true;
      if (erreur) { erreur.classList.add('d-none'); }
      var donnees = {};
      donnees[bouton.getAttribute('data-champ')] = bouton.getAttribute('data-valeur');

      appelCompte(bouton.getAttribute('data-supprimer'), donnees).then(function (res) {
        if (!res.ok) {
          bouton.disabled = false;
          reinitialiser();
          if (erreur) {
            erreur.textContent = res.message;
            erreur.classList.remove('d-none');
            erreur.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
          }
          return;
        }
        if (confirmation) { afficher(confirmation, res.message); }
        var url = new URL(window.location.href);
        ['param', 'dept', 'poste', 'conge'].forEach(function (p) { url.searchParams.delete(p); });
        setTimeout(function () { window.location.href = url.pathname + url.search; }, 1200);
      });
    });
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

  function appelCompte(url, donnees) {
    var meta = document.querySelector('meta[name="csrf"]');
    var corps = new URLSearchParams(donnees);
    return fetch(url, {
      method: 'POST',
      headers: { 'X-CSRF-Token': meta ? meta.content : '', 'Accept': 'application/json' },
      body: corps
    }).then(function (reponse) {
      return reponse.json().catch(function () { return {}; }).then(function (json) {
        if (!reponse.ok && !json.message) {
          json.message = reponse.status === 403 ? 'Session expirée ou accès refusé. Rechargez la page.' : 'Une erreur est survenue.';
        }
        json.ok = reponse.ok && json.ok !== false;
        return json;
      });
    }).catch(function () {
      return { ok: false, message: 'Connexion impossible. Réessayez.' };
    });
  }

  function afficher(zone, texte) {
    if (!zone) { return; }
    var label = zone.querySelector('[data-confirmation-label]');
    (label || zone).textContent = texte;
    zone.classList.remove('d-none');
  }

  var ACTIONS_COMPTE = {
    activer:    { url: '/api/compte_activer.php',    suivant: 'desactiver', pastille: ['success', 'Compte actif'],      msg: ' activé. Le rôle est accordé et l’action est journalisée.' },
    desactiver: { url: '/api/compte_desactiver.php', suivant: 'reactiver',  pastille: ['danger', 'Compte désactivé'],  msg: ' désactivé. L’action est journalisée.' },
    reactiver:  { url: '/api/compte_reactiver.php',  suivant: 'desactiver', pastille: ['success', 'Compte actif'],      msg: ' réactivé. L’action est journalisée.' }
  };
  var PRESENTATION_COMPTE = {
    activer:    { classe: 'btn-primary',       icone: 'fa-check',        libelle: 'Activer' },
    desactiver: { classe: 'btn-danger-outline', icone: 'fa-user-slash',   libelle: 'Désactiver' },
    reactiver:  { classe: 'btn-neutral',       icone: 'fa-rotate-right', libelle: 'Réactiver' }
  };

  document.querySelectorAll('[data-compte-action]').forEach(function (bouton) {
    bouton.addEventListener('click', function () {
      var ligne = bouton.closest('[data-compte]');
      var nom = ligne.getAttribute('data-compte');
      var action = bouton.getAttribute('data-compte-action');
      var def = ACTIONS_COMPTE[action];
      var pastille = ligne.querySelector('[data-compte-etat]');
      var confirmation = document.getElementById('acces-confirmation');
      var erreur = document.getElementById('acces-erreur');

      bouton.disabled = true;
      erreur.classList.add('d-none');
      confirmation.classList.add('d-none');

      appelCompte(def.url, { id: ligne.getAttribute('data-id') }).then(function (res) {
        bouton.disabled = false;
        if (!res.ok) {
          erreur.textContent = res.message;
          erreur.classList.remove('d-none');
          return;
        }
        var suivant = def.suivant;
        var pres = PRESENTATION_COMPTE[suivant];
        pastille.className = 'badge-status badge-status--dot badge-status--' + def.pastille[0];
        pastille.textContent = def.pastille[1];
        bouton.setAttribute('data-compte-action', suivant);
        bouton.className = 'btn text-decoration-none ' + pres.classe;
        bouton.querySelector('i').className = 'fa-solid ' + pres.icone + ' me-2';
        bouton.querySelector('[data-libelle]').textContent = pres.libelle + (bouton.hasAttribute('data-court') ? '' : ' ' + nom);
        ligne.classList.remove('is-pending');
        afficher(confirmation, 'Compte de ' + nom + def.msg);
        if (action === 'activer') { setTimeout(function () { window.location.reload(); }, 1500); }
      });
    });
  });

  document.querySelectorAll('[data-activation]').forEach(function (form) {
    var confirmation = document.getElementById(form.getAttribute('data-confirmation') || '');
    var erreur = document.getElementById('activation-erreur');

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var nom = form.getAttribute('data-nom');
      var bouton = form.querySelector('button[type="submit"]');
      bouton.disabled = true;
      erreur.classList.add('d-none');

      appelCompte('/api/compte_activer.php', {
        id: form.getAttribute('data-id'),
        commentaire: form.querySelector('[name="commentaire"]').value
      }).then(function (res) {
        if (!res.ok) {
          bouton.disabled = false;
          erreur.textContent = res.message;
          erreur.classList.remove('d-none');
          return;
        }
        afficher(confirmation, 'Compte de ' + nom + ' activé. Le rôle Responsable RH est accordé et l’action est journalisée.');
        form.querySelectorAll('button, textarea').forEach(function (el) { el.disabled = true; });
        setTimeout(function () { window.location.reload(); }, 1500);
      });
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
