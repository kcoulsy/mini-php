(function () {
  document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
    var field = button.closest('.password-field');
    if (!field) {
      return;
    }

    var input = field.querySelector('input[type="password"], input[type="text"]');
    if (!input) {
      return;
    }

    button.addEventListener('click', function () {
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      button.textContent = showing ? 'Show' : 'Hide';
      button.setAttribute('aria-pressed', showing ? 'false' : 'true');
      button.setAttribute('aria-label', (showing ? 'Show' : 'Hide') + ' password');
    });
  });
})();
