/**
 * Indicatif téléphonique international (intl-tel-input) — pages auth.
 * Uniquement aide à la saisie et normalisation E.164 à l'envoi ; la validation reste en PHP.
 */
(function () {
  function getE164(iti) {
    try {
      if (
        typeof intlTelInput !== 'undefined' &&
        intlTelInput.utils &&
        typeof intlTelInput.utils.numberFormat !== 'undefined'
      ) {
        return iti.getNumber(intlTelInput.utils.numberFormat.E164);
      }
    } catch (e) {}
    try {
      return iti.getNumber();
    } catch (e2) {
      return '';
    }
  }

  /**
   * @param {string} inputId
   */
  function initAuthIntlTel(inputId) {
    var input = document.getElementById(inputId);
    if (!input || typeof window.intlTelInput === 'undefined') {
      return null;
    }

    var iti = window.intlTelInput(input, {
      initialCountry: 'sn',
      preferredCountries: ['sn', 'ga', 'ci', 'fr', 'ml', 'tg', 'bf', 'ne', 'cm', 'bj'],
      nationalMode: false,
      formatOnDisplay: true,
      strictMode: false
    });

    var form = input.closest('form');
    if (form) {
      form.addEventListener('submit', function () {
        var n = getE164(iti);
        if (n) {
          input.value = n;
        }
      });
    }

    return iti;
  }

  window.initAuthIntlTel = initAuthIntlTel;
})();
