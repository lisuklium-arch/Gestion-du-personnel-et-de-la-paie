(function () {
  'use strict';
  document.querySelectorAll('[data-traiter-demande-modification]').forEach(function (form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      var bouton = event.submitter;
      if (!bouton) { return; }
      var motifRefus = form.querySelector('[data-motif-refus]');
      if (bouton.value === 'refusee' && (!motifRefus || !motifRefus.value.trim())) {
        if (motifRefus) {
          motifRefus.setCustomValidity('Le motif de refus est obligatoire.');
          motifRefus.reportValidity();
        }
        return;
      }
      if (motifRefus) { motifRefus.setCustomValidity(''); }
      var donnees = new FormData(form);
      donnees.set('decision', bouton.value);
      form.querySelectorAll('button').forEach(function (element) { element.disabled = true; });
      fetch('/api/demande_modification_traiter.php', { method: 'POST', headers: { Accept: 'application/json' }, body: donnees })
        .then(function (reponse) { return reponse.json().then(function (json) { return { ok: reponse.ok && json.ok, json: json }; }); })
        .then(function (resultat) {
          var retour = document.getElementById('demande-modification-retour');
          retour.textContent = resultat.json.message || 'Traitement impossible.';
          retour.className = 'alert ' + (resultat.ok ? 'alert-success' : 'alert-danger');
          if (resultat.ok) {
            form.closest('[data-demande-modification]').remove();
          } else {
            form.querySelectorAll('button').forEach(function (element) { element.disabled = false; });
          }
        }).catch(function () {
          form.querySelectorAll('button').forEach(function (element) { element.disabled = false; });
        });
    });
  });
})();
