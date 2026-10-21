// JavaScript commun. Un formulaire avec l'attribut data-confirm demande une confirmation avant d'être envoyé.
document.addEventListener('submit', function (ev) {
  var message = ev.target.getAttribute('data-confirm');
  if (message && !window.confirm(message)) { ev.preventDefault(); }
});
