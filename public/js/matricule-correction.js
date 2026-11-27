(function () {
  'use strict';
  var form = document.getElementById('form-correction-matricule');
  if (!form) { return; }
  form.addEventListener('submit', function (event) {
    event.preventDefault();
    if (!form.checkValidity()) { form.classList.add('was-validated'); return; }
    var bouton = form.querySelector('button[type="submit"]');
    var retour = document.getElementById('correction-matricule-retour');
    bouton.disabled = true;
    retour.classList.add('d-none');
    fetch('/api/matricule_corriger.php', { method: 'POST', headers: { Accept: 'application/json' }, body: new FormData(form) })
      .then(function (reponse) { return reponse.json().then(function (json) { return { ok: reponse.ok && json.ok, json: json }; }); })
      .then(function (resultat) {
        if (!resultat.ok) {
          bouton.disabled = false;
          retour.textContent = resultat.json.message || 'La correction a échoué.';
          retour.className = 'alert alert-danger';
          return;
        }
        retour.textContent = resultat.json.message;
        retour.className = 'alert alert-success';
        window.setTimeout(function () {
          window.location.href = 'employe_fiche_rh.php?id=' + encodeURIComponent(resultat.json.matricule);
        }, 500);
      }).catch(function () {
        bouton.disabled = false;
        retour.textContent = 'Connexion impossible. Réessayez.';
        retour.className = 'alert alert-danger';
      });
  });
})();
