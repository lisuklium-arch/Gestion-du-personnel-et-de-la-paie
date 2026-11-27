(function () {
  'use strict';

  var form = document.querySelector('[data-employes-recherche]');
  if (!form) { return; }

  var corps = document.getElementById('employes-resultats');
  var compteur = document.getElementById('employes-compteur');
  var endpoint = form.getAttribute('data-endpoint');
  var ficheUrl = form.getAttribute('data-fiche-url');
  var attente;
  var requete;

  var dossiers = {
    actif: ['Actif', 'success'],
    suspendu: ['Suspendu', 'danger'],
    sorti: ['Sorti', 'neutral']
  };
  var comptes = {
    en_attente: ['En attente', 'warning'],
    actif: ['Compte actif', 'success'],
    desactive: ['Compte désactivé', 'danger']
  };
  var actions = {
    activer: ['btn-primary', 'fa-check', 'Activer'],
    desactiver: ['btn-danger-outline', 'fa-user-slash', 'Désactiver'],
    reactiver: ['btn-neutral', 'fa-rotate-right', 'Réactiver']
  };

  function element(nom, texte, classe) {
    var noeud = document.createElement(nom);
    if (texte !== undefined) { noeud.textContent = texte; }
    if (classe) { noeud.className = classe; }
    return noeud;
  }

  function badge(texte, ton) {
    return element('span', texte, 'badge-status badge-status--dot badge-status--' + ton);
  }

  function afficher(liste) {
    corps.replaceChildren();
    if (!liste.length) {
      var vide = element('tr');
      var cellule = element('td', 'Aucun dossier ne correspond à ces filtres.');
      cellule.colSpan = 6;
      vide.appendChild(cellule);
      corps.appendChild(vide);
    }

    liste.forEach(function (employe) {
      var ligne = element('tr');
      var nom = employe.prenom + ' ' + employe.nom;
      ligne.setAttribute('data-compte', nom);
      ligne.setAttribute('data-id', employe.id);
      if (employe.statut_compte === 'en_attente') { ligne.className = 'is-pending'; }

      var tdNom = element('td');
      tdNom.setAttribute('data-label', 'Nom complet');
      var personne = element('div', undefined, 'cell-person');
      var avatar = element('span', undefined, 'avatar avatar--sm');
      if (employe.photo_chemin && /^documents\/[a-f0-9]{32}\.(jpg|png)$/.test(employe.photo_chemin)) {
        var image = element('img', undefined, 'avatar__image');
        image.src = '/api/photo_profil.php?id=' + encodeURIComponent(employe.id) + '&v=' + encodeURIComponent(employe.photo_chemin.slice(10, 42));
        image.alt = 'Photo de ' + nom;
        avatar.appendChild(image);
      } else { avatar.textContent = (employe.prenom.charAt(0) + employe.nom.charAt(0)).toUpperCase(); }
      var lien = element('a', nom, 'data-table__ref');
      lien.href = ficheUrl + '?id=' + encodeURIComponent(employe.matricule);
      var blocNom = element('div'); blocNom.appendChild(lien);
      if (!employe.photo_chemin) blocNom.appendChild(element('p', 'Photo à compléter', 'cell-person__meta'));
      personne.append(avatar, blocNom); tdNom.appendChild(personne);

      var tdMatricule = element('td', employe.matricule);
      tdMatricule.setAttribute('data-label', 'Matricule');
      var tdOrganisation = element('td', employe.departement + ' • ' + employe.poste);
      tdOrganisation.setAttribute('data-label', 'Département et poste');

      var tdDossier = element('td');
      tdDossier.setAttribute('data-label', 'Statut');
      tdDossier.appendChild(badge(dossiers[employe.statut_dossier][0], dossiers[employe.statut_dossier][1]));

      var tdCompte = element('td');
      tdCompte.setAttribute('data-label', 'Compte');
      var statutCompte = badge(comptes[employe.statut_compte][0], comptes[employe.statut_compte][1]);
      statutCompte.setAttribute('data-compte-etat', '');
      tdCompte.appendChild(statutCompte);

      var tdAction = element('td');
      tdAction.setAttribute('data-label', 'Action');
      if (employe.action && actions[employe.action]) {
        var action = actions[employe.action];
        var bouton = element('button', undefined, 'btn text-decoration-none ' + action[0]);
        bouton.type = 'button';
        bouton.setAttribute('data-compte-action', employe.action);
        bouton.setAttribute('data-court', '');
        var icone = element('i', undefined, 'fa-solid ' + action[1] + ' me-2');
        icone.setAttribute('aria-hidden', 'true');
        bouton.appendChild(icone);
        var libelle = element('span', action[2]);
        libelle.setAttribute('data-libelle', '');
        bouton.appendChild(libelle);
        tdAction.appendChild(bouton);
      } else {
        tdAction.textContent = '—';
        tdAction.className = 'text-muted';
      }

      ligne.append(tdNom, tdMatricule, tdOrganisation, tdDossier, tdCompte, tdAction);
      corps.appendChild(ligne);
    });
    compteur.textContent = liste.length + ' dossier' + (liste.length > 1 ? 's' : '') + ' affiché' + (liste.length > 1 ? 's' : '') + '.';
  }

  function rechercher() {
    if (requete) { requete.abort(); }
    requete = new AbortController();
    var params = new URLSearchParams(new FormData(form));
    fetch(endpoint + '?' + params.toString(), { headers: { Accept: 'application/json' }, signal: requete.signal })
      .then(function (reponse) {
        if (!reponse.ok) { throw new Error('Recherche indisponible.'); }
        return reponse.json();
      })
      .then(function (resultat) {
        if (!resultat.ok) { throw new Error(resultat.message || 'Recherche indisponible.'); }
        afficher(resultat.employes);
      })
      .catch(function (erreur) {
        if (erreur.name !== 'AbortError') { compteur.textContent = erreur.message; }
      });
  }

  function programmerRecherche() {
    window.clearTimeout(attente);
    attente = window.setTimeout(rechercher, 250);
  }

  form.addEventListener('submit', function (event) { event.preventDefault(); rechercher(); });
  form.querySelectorAll('input, select').forEach(function (champ) {
    champ.addEventListener(champ.tagName === 'SELECT' ? 'change' : 'input', programmerRecherche);
  });
})();
