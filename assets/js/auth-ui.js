function initAuthUi(rootSelector) {
  var roots = document.querySelectorAll(rootSelector || '[data-auth-ui]');

  Array.prototype.forEach.call(roots, function (root) {
    var modeButtons = root.querySelectorAll('[data-auth-mode]');
    var viewButtons = root.querySelectorAll('[data-auth-view]');
    var viewTriggers = root.querySelectorAll('[data-auth-view]');
    var panels = root.querySelectorAll('[data-auth-panel]');

    function setActiveMode(mode) {
      Array.prototype.forEach.call(modeButtons, function (button) {
        button.classList.toggle('is-active', button.getAttribute('data-auth-mode') === mode);
      });
    }

    function setActiveView(view) {
      Array.prototype.forEach.call(viewButtons, function (button) {
        button.classList.toggle('is-active', button.getAttribute('data-auth-view') === view);
      });

      Array.prototype.forEach.call(panels, function (panel) {
        panel.classList.toggle('is-active', panel.getAttribute('data-auth-panel') === view);
      });
    }

    Array.prototype.forEach.call(modeButtons, function (button) {
      button.addEventListener('click', function () {
        var mode = button.getAttribute('data-auth-mode');

        if (mode === 'farmer') {
          window.location.href = button.getAttribute('data-auth-redirect');
          return;
        }

        setActiveMode('user');
      });
    });

    Array.prototype.forEach.call(viewTriggers, function (button) {
      button.addEventListener('click', function (event) {
        event.preventDefault();
        setActiveView(button.getAttribute('data-auth-view'));
      });
    });

    Array.prototype.forEach.call(viewButtons, function (button) {
      button.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ' ') {
          event.preventDefault();
          setActiveView(button.getAttribute('data-auth-view'));
        }
      });
    });

    setActiveMode('user');
    setActiveView(root.getAttribute('data-auth-default-view') || 'login');
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', function () {
    initAuthUi();
  });
} else {
  initAuthUi();
}
