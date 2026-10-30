(function () {
  'use strict';

  var racine = document.querySelector('[data-notif]');
  if (!racine) { return; }

  var INTERVALLE_MS = 60000;
  var bouton = racine.querySelector('[data-notif-bouton]');
  var badge = racine.querySelector('[data-notif-badge]');
  var panneau = racine.querySelector('[data-notif-panneau]');
  var liste = racine.querySelector('[data-notif-liste]');
  var vide = racine.querySelector('[data-notif-vide]');
  var toutLu = racine.querySelector('[data-notif-tout]');
  var minuterie = null;

  function jeton() {
    var meta = document.querySelector('meta[name="csrf"]');
    return meta ? meta.content : '';
  }

  // Appel d'API : renvoie le JSON, ou null si la session a pris fin ou si le réseau échoue.
  function appeler(url, methode, donnees) {
    var options = {
      method: methode,
      headers: { 'Accept': 'application/json', 'X-CSRF-Token': jeton() },
      credentials: 'same-origin'
    };
    if (donnees) { options.body = new URLSearchParams(donnees); }

    return fetch(url, options).then(function (reponse) {
      if (reponse.status === 401) { arreter(); return null; }
      return reponse.json().catch(function () { return null; });
    }).catch(function () { return null; });
  }

  function depuis(secondes) {
    if (secondes < 60) { return 'À l’instant'; }
    if (secondes < 3600) { return 'Il y a ' + Math.floor(secondes / 60) + ' min'; }
    if (secondes < 86400) { return 'Il y a ' + Math.floor(secondes / 3600) + ' h'; }
    return 'Il y a ' + Math.floor(secondes / 86400) + ' j';
  }

  function lienSur(lien) {
    return typeof lien === 'string' && lien.charAt(0) === '/' && lien.charAt(1) !== '/' && lien.indexOf('\\') === -1;
  }

  function afficherBadge(nombre) {
    badge.textContent = nombre > 9 ? '9+' : String(nombre);
    badge.classList.toggle('d-none', nombre === 0);
    bouton.setAttribute('aria-label', nombre === 0 ? 'Notifications' : 'Notifications : ' + nombre + ' non lue' + (nombre > 1 ? 's' : ''));
  }

  function creerElement(n) {
    var li = document.createElement('li');
    var b = document.createElement('button');
    b.type = 'button';
    b.className = 'notif__item' + (n.lue ? '' : ' is-unread');

    var titre = document.createElement('span');
    titre.className = 'notif__titre';
    titre.textContent = n.titre;
    b.appendChild(titre);

    if (n.message) {
      var message = document.createElement('span');
      message.className = 'notif__message';
      message.textContent = n.message;
      b.appendChild(message);
    }

    var date = document.createElement('span');
    date.className = 'notif__date';
    date.textContent = depuis(n.depuis_secondes);
    b.appendChild(date);

    b.addEventListener('click', function () { ouvrirNotification(n); });
    li.appendChild(b);
    return li;
  }

  function rendre(donnees) {
    liste.replaceChildren();
    donnees.notifications.forEach(function (n) { liste.appendChild(creerElement(n)); });
    vide.classList.toggle('d-none', donnees.notifications.length > 0);
    toutLu.classList.toggle('d-none', donnees.non_lues === 0);
    afficherBadge(donnees.non_lues);
  }

  function charger() {
    return appeler('/api/notifications.php', 'GET').then(function (donnees) {
      if (donnees && donnees.ok) { rendre(donnees); }
    });
  }

  function ouvrirNotification(n) {
    var suite = function () {
      if (lienSur(n.lien)) { window.location.href = n.lien; } else { charger(); }
    };
    if (n.lue) { suite(); return; }
    appeler('/api/notification_lue.php', 'POST', { id: n.id }).then(suite);
  }

  function marquerToutes() {
    appeler('/api/notifications_toutes_lues.php', 'POST', {}).then(charger);
  }

  function ouvrirPanneau() {
    panneau.classList.remove('d-none');
    bouton.setAttribute('aria-expanded', 'true');
    charger();
  }

  function fermerPanneau() {
    panneau.classList.add('d-none');
    bouton.setAttribute('aria-expanded', 'false');
  }

  function demarrer() {
    arreter();
    minuterie = setInterval(function () {
      if (!document.hidden) { charger(); }
    }, INTERVALLE_MS);
  }

  function arreter() {
    if (minuterie !== null) { clearInterval(minuterie); minuterie = null; }
  }

  bouton.addEventListener('click', function () {
    if (panneau.classList.contains('d-none')) { ouvrirPanneau(); } else { fermerPanneau(); }
  });

  toutLu.addEventListener('click', marquerToutes);

  document.addEventListener('click', function (event) {
    if (!racine.contains(event.target)) { fermerPanneau(); }
  });

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !panneau.classList.contains('d-none')) {
      fermerPanneau();
      bouton.focus();
    }
  });

  document.addEventListener('visibilitychange', function () {
    if (!document.hidden) { charger(); demarrer(); }
  });

  charger();
  demarrer();
})();
