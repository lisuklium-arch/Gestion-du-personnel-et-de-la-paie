(function () {
  'use strict';

  document.addEventListener('click', function (event) {
    var lien = event.target.closest('a[href$="/public/logout.php"]');
    var meta = document.querySelector('meta[name="csrf"]');
    if (!lien || !meta) { return; }

    event.preventDefault();
    var form = document.createElement('form');
    form.method = 'post';
    form.action = lien.getAttribute('href');

    var champ = document.createElement('input');
    champ.type = 'hidden';
    champ.name = 'csrf';
    champ.value = meta.content;
    form.appendChild(champ);

    document.body.appendChild(form);
    form.submit();
  });
})();
