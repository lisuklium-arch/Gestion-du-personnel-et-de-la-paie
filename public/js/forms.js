(function () {
  'use strict';

  var MAX_MB_DEFAULT = 5;

  document.querySelectorAll('form.needs-validation').forEach(function (form) {
    form.querySelectorAll('input[type="file"]').forEach(function (input) {
      input.addEventListener('change', function () {
        var maxMb = parseFloat(input.getAttribute('data-max-mb')) || MAX_MB_DEFAULT;
        var file = input.files && input.files[0];
        var tooBig = !!file && file.size > maxMb * 1024 * 1024;

        input.setCustomValidity(tooBig ? 'Fichier trop volumineux.' : '');
        input.classList.toggle('is-invalid', tooBig);
      });
    });

    form.addEventListener('submit', function (event) {
      event.preventDefault();
      form.classList.add('was-validated');

      var confirmation = document.getElementById(form.getAttribute('data-confirmation') || '');

      if (form.checkValidity()) {
        if (confirmation) {
          var texte = form.getAttribute('data-confirmation-text');
          var cible = confirmation.querySelector('[data-confirmation-label]');
          if (texte && cible) { cible.textContent = texte; }
          confirmation.classList.remove('d-none');
          confirmation.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        form.dispatchEvent(new CustomEvent('form:valid', { bubbles: true }));
      } else if (confirmation) {
        confirmation.classList.add('d-none');
      }
    });
  });
})();
