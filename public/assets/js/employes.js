// Recherche instantanée des employés (jQuery + AJAX) : la liste se met à jour sans recharger la page.
(function ($) {
  var $f = $('#filtres'), url = $f.data('url'), voir = $f.data('voir'), page = 1, minuteur = null, requete = null;
  var STATUTS = { ACTIF: ['Actif', 'text-bg-success'], EN_CONGE: ['En congé', 'text-bg-warning'], SUSPENDU: ['Suspendu', 'text-bg-danger'], SORTI: ['Sorti', 'text-bg-secondary'] };

  function cellule(texte) { return $('<td>').text(texte); }   // .text() : jamais de HTML injecté (protection XSS)

  function charger() {
    if (requete) { requete.abort(); }
    requete = $.getJSON(url, { q: $('#q').val(), departement: $('#departement').val(), poste: $('#poste').val(), statut: $('#statut').val(), page: page })
      .done(afficher)
      .fail(function (x, etat) { if (etat !== 'abort') { $('#message').text('Impossible de charger la liste. Réessayez.').removeClass('d-none'); } });
  }

  function afficher(d) {
    var $tb = $('#lignes').empty();
    $('#message').addClass('d-none');
    page = d.page;
    if (!d.lignes.length) {
      $tb.append($('<tr>').append($('<td colspan="6" class="text-center text-secondary py-4">').text('Aucun employé ne correspond à votre recherche.')));
    }
    $.each(d.lignes, function (i, e) {
      var s = STATUTS[e.statut] || [e.statut, 'text-bg-secondary'];
      $tb.append($('<tr>').append(
        cellule(e.matricule),
        $('<td class="fw-semibold">').text(e.prenom + ' ' + e.nom).append($('<div class="small fw-normal text-secondary">').text(e.email)),
        cellule(e.departement), cellule(e.poste),
        $('<td>').append($('<span class="badge">').addClass(s[1]).text(s[0])),
        $('<td>').append($('<a class="btn btn-outline-secondary btn-sm">').attr('href', voir + '?id=' + encodeURIComponent(e.id)).text('Dossier'))
      ));
    });
    $('#compte').text(d.total + (d.total > 1 ? ' employés' : ' employé'));
    $('#plage').text(d.total ? 'Affichage ' + d.debut + ' à ' + d.fin + ' sur ' + d.total : '');
    $('#prec').prop('disabled', d.page <= 1);
    $('#suiv').prop('disabled', d.page >= d.pages);
  }

  function relancer() { page = 1; charger(); }
  $('#q').on('input', function () { clearTimeout(minuteur); minuteur = setTimeout(relancer, 300); });   // attend 0,3 s après la dernière frappe
  $('#departement, #poste, #statut').on('change', relancer);
  $f.on('submit', function (ev) { ev.preventDefault(); relancer(); });
  $('#prec').on('click', function () { page -= 1; charger(); });
  $('#suiv').on('click', function () { page += 1; charger(); });
  charger();
})(jQuery);
