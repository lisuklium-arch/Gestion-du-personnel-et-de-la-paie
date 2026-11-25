(function () {
  'use strict';
  var form = document.getElementById('form-profil');
  if (!form) { return; }
  var confirmation = document.getElementById('profil-confirmation');
  var erreur = document.getElementById('profil-erreur');
  var champ = form.querySelector('[name="champ"]');
  var ancienneValeur = document.getElementById('ancienne-valeur');
  var nouvelleValeur = document.getElementById('nouvelle-valeur');

  function mettreAJourChamp() {
    var option = champ.options[champ.selectedIndex];
    ancienneValeur.value = option.getAttribute('data-ancienne') || '';
    nouvelleValeur.type = option.getAttribute('data-type') || 'text';
    nouvelleValeur.value = '';
  }

  champ.addEventListener('change', mettreAJourChamp);

  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!form.checkValidity()) {
      form.classList.add('was-validated');
      return;
    }
    var bouton = form.querySelector('button[type="submit"]');
    bouton.disabled = true;
    erreur.classList.add('d-none');
    fetch('/api/demande_modification_creer.php', {
      method: 'POST',
      headers: { Accept: 'application/json' },
      body: new FormData(form)
    }).then(function (reponse) {
      return reponse.json().then(function (json) { return { ok: reponse.ok && json.ok, json: json }; });
    }).then(function (resultat) {
      bouton.disabled = false;
      if (!resultat.ok) {
        erreur.textContent = resultat.json.message || 'La demande n’a pas pu être envoyée.';
        erreur.classList.remove('d-none');
        return;
      }
      confirmation.classList.remove('d-none');
      form.reset();
      mettreAJourChamp();
    }).catch(function () {
      bouton.disabled = false;
      erreur.textContent = 'Connexion impossible. Réessayez.';
      erreur.classList.remove('d-none');
    });
  });
})();
