(function () {
  function ready(fn) {
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', fn);
    } else {
      fn();
    }
  }

  ready(function () {
    var modal = document.querySelector('[data-auth-modal]');
    if (!modal) {
      return;
    }

    var openers = document.querySelectorAll('[data-auth-modal-open]');
    var closers = modal.querySelectorAll('[data-auth-modal-close]');

    function openModal() {
      modal.classList.add('is-open');
      document.body.classList.add('auth-modal-open');
    }

    function closeModal() {
      modal.classList.remove('is-open');
      document.body.classList.remove('auth-modal-open');
    }

    Array.prototype.forEach.call(openers, function (opener) {
      opener.addEventListener('click', function (event) {
        event.preventDefault();
        openModal();
      });
    });

    Array.prototype.forEach.call(closers, function (closer) {
      closer.addEventListener('click', function () {
        closeModal();
      });
    });

    modal.addEventListener('click', function (event) {
      if (event.target === modal) {
        closeModal();
      }
    });

    document.addEventListener('keydown', function (event) {
      if (event.key === 'Escape' && modal.classList.contains('is-open')) {
        closeModal();
      }
    });
  });
})();
