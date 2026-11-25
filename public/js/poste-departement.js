(function () {
  'use strict';
  var departement = document.getElementById('compte-dept');
  var poste = document.getElementById('compte-poste');
  if (!departement || !poste) return;
  function actualiser() {
    var choisi = departement.value;
    Array.prototype.forEach.call(poste.options, function (option) {
      if (!option.value) return;
      option.hidden = choisi !== '' && option.getAttribute('data-departement') !== choisi;
      option.disabled = option.hidden;
    });
    if (poste.selectedOptions[0] && poste.selectedOptions[0].disabled) poste.value = '';
  }
  departement.addEventListener('change', actualiser);
  actualiser();
})();
