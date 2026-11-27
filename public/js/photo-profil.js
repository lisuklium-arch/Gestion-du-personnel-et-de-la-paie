(function () {
  'use strict';
  var form = document.getElementById('form-photo-profil'); if (!form) return;
  form.addEventListener('submit', function (event) {
    event.preventDefault(); form.classList.add('was-validated'); if (!form.checkValidity()) return;
    var bouton = form.querySelector('button[type="submit"]'); bouton.disabled = true;
    fetch('/api/photo_profil_remplacer.php', {method:'POST', headers:{Accept:'application/json','X-CSRF-Token':document.querySelector('meta[name="csrf"]').content}, body:new FormData(form)})
      .then(function (r) { return r.json().then(function (j) { return {ok:r.ok && j.ok, json:j}; }); })
      .then(function (r) { bouton.disabled=false; var zone=document.getElementById(r.ok ? 'photo-confirmation' : 'photo-erreur'); zone.textContent=r.json.message || 'Opération impossible.'; zone.classList.remove('d-none'); if (r.ok) setTimeout(function(){ window.location.reload(); }, 700); })
      .catch(function () { bouton.disabled=false; var zone=document.getElementById('photo-erreur'); zone.textContent='Connexion impossible. Réessayez.'; zone.classList.remove('d-none'); });
  });
})();
