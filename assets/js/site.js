/* Pencarian aplikasi pada landing page. */
(function () {
  'use strict';

  var input = document.getElementById('appSearch');
  if (!input) return;

  input.addEventListener('input', function () {
    var q = input.value.trim().toLowerCase();

    document.querySelectorAll('[data-group]').forEach(function (group) {
      var cards = group.querySelectorAll('.app-card');
      var shown = 0;

      cards.forEach(function (card) {
        var match = q === '' || (card.dataset.name || '').indexOf(q) !== -1;
        card.hidden = !match;
        if (match) shown++;
      });

      var none = group.querySelector('.apps__none');
      if (none) none.hidden = shown !== 0 || cards.length === 0;
      group.hidden = q !== '' && shown === 0 && cards.length > 0;
    });
  });
})();
