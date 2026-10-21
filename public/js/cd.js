(function () {
  'use strict';

  document.querySelectorAll('[data-table-filter]').forEach(function (input) {
    var table = document.querySelector(input.getAttribute('data-table-filter'));
    var vide = document.querySelector(input.getAttribute('data-empty-message') || '');
    if (!table) { return; }

    input.addEventListener('input', function () {
      var terme = input.value.trim().toLowerCase();
      var visibles = 0;
      table.querySelectorAll('tbody tr').forEach(function (tr) {
        var ok = !terme || (tr.getAttribute('data-search') || '').indexOf(terme) !== -1;
        tr.hidden = !ok;
        if (ok) { visibles++; }
      });
      if (vide) { vide.hidden = visibles !== 0; }
    });
  });

  var modal = document.getElementById('modal-absence');
  if (modal) {
    var form = modal.querySelector('form');
    var conf = modal.querySelector('#absence-confirmation');
    var champNom = modal.querySelector('#absence-employe-nom');
    var champId = modal.querySelector('#absence-employe-id');
    var today = modal.getAttribute('data-today');
    var feedback = document.getElementById('equipe-feedback');
    var kpiPresents = document.getElementById('kpi-presents');
    var courant = { id: '', nom: '' };

    modal.addEventListener('show.bs.modal', function (event) {
      var btn = event.relatedTarget;
      if (btn) {
        courant.id = btn.getAttribute('data-id') || '';
        courant.nom = btn.getAttribute('data-nom') || '';
      }
      champNom.textContent = courant.nom;
      champId.value = courant.id;
      form.reset();
      form.classList.remove('was-validated');
      form.setAttribute('data-confirmation-text', 'Absence de ' + courant.nom + ' enregistrée.');
      if (conf) { conf.classList.add('d-none'); }
    });

    form.addEventListener('form:valid', function () {
      var date = form.elements.date ? form.elements.date.value : '';
      var ligne = document.querySelector('#table-equipe tr[data-emp="' + courant.id + '"]');
      var texte = 'Absence de ' + courant.nom + ' enregistrée.';

      if (ligne && date === today) {
        var badge = ligne.querySelector('.js-statut');
        if (badge && badge.classList.contains('badge-status--success')) {
          badge.classList.remove('badge-status--success');
          badge.classList.add('badge-status--danger');
          badge.textContent = badge.getAttribute('data-feminin') === '1' ? 'Absente' : 'Absent';
          if (kpiPresents) {
            kpiPresents.textContent = String(Math.max(0, (parseInt(kpiPresents.textContent, 10) || 0) - 1));
          }
        }
      }
      if (feedback) {
        feedback.textContent = texte + ' (démonstration : rien n’est encore envoyé au serveur)';
        feedback.classList.remove('d-none');
      }

      window.setTimeout(function () {
        var instance = window.bootstrap && window.bootstrap.Modal.getInstance(modal);
        if (instance) { instance.hide(); }
      }, 900);
    });
  }

  document.querySelectorAll('.js-valider-hs').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var tr = btn.closest('tr');
      var badge = tr.querySelector('.js-hs-etat');
      var h = parseInt(tr.getAttribute('data-h'), 10) || 0;

      badge.classList.remove('badge-status--warning');
      badge.classList.add('badge-status--success');
      badge.textContent = 'Intégrées';

      var cell = btn.parentNode;
      cell.innerHTML = '<span class="data-table__muted" aria-hidden="true">—</span><span class="visually-hidden">Aucune action</span>';

      var attente = document.getElementById('kpi-hs-attente');
      var integrees = document.getElementById('kpi-hs-integrees');
      var meta = document.getElementById('kpi-hs-attente-meta');
      if (attente && integrees) {
        var a = Math.max(0, (parseInt(attente.textContent, 10) || 0) - h);
        attente.textContent = a + ' h';
        integrees.textContent = ((parseInt(integrees.textContent, 10) || 0) + h) + ' h';
      }
      if (meta) {
        var restantes = document.querySelectorAll('.js-valider-hs').length;
        meta.textContent = restantes + ' déclaration' + (restantes > 1 ? 's' : '') + ' en attente';
      }

      var fb = document.getElementById('hs-feedback');
      if (fb) {
        fb.textContent = 'Heures de ' + (btn.getAttribute('data-nom') || '') + ' validées (démonstration : rien n’est encore envoyé au serveur).';
        fb.classList.remove('d-none');
      }
    });
  });

  var debut = document.getElementById('hs-debut');
  var fin = document.getElementById('hs-fin');
  var duree = document.getElementById('hs-duree');

  function minutes(v) {
    var p = v.split(':');
    return Number(p[0]) * 60 + Number(p[1]);
  }

  function majDuree() {
    if (!debut || !fin || !duree) { return; }
    var boite = duree.closest('.duration-box');
    if (!debut.value || !fin.value) {
      duree.textContent = '—';
      return;
    }
    var d = minutes(fin.value) - minutes(debut.value);
    if (d <= 0) {
      fin.setCustomValidity('L’heure de fin doit être après l’heure de début.');
      fin.classList.add('is-invalid');
      if (boite) { boite.classList.add('is-invalid'); }
      duree.textContent = '—';
      return;
    }
    fin.setCustomValidity('');
    fin.classList.remove('is-invalid');
    if (boite) { boite.classList.remove('is-invalid'); }
    var h = Math.floor(d / 60);
    var m = d % 60;
    duree.textContent = h + ' h' + (m ? ' ' + (m < 10 ? '0' : '') + m : '');
  }

  if (debut && fin) {
    debut.addEventListener('input', majDuree);
    fin.addEventListener('input', majDuree);
    majDuree();
  }

  document.querySelectorAll('[data-autosubmit]').forEach(function (sel) {
    sel.addEventListener('change', function () { sel.form.submit(); });
  });
})();
