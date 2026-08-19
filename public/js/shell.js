(function () {
  'use strict';

  // ---- theme toggle -------------------------------------------------
  var btn = document.getElementById('themeToggle');
  var root = document.documentElement;
  if (btn) {
    function sync() { btn.setAttribute('aria-pressed', String(root.getAttribute('data-theme') === 'dark')); }
    sync();
    btn.addEventListener('click', function () {
      var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
      root.setAttribute('data-theme', next);
      try { localStorage.setItem('ultrademy.theme', next); } catch (e) {}
      sync();
    });
  }

  // ---- calendar day select (rail) ------------------------------------
  document.querySelectorAll('.cal .d').forEach(function (d) {
    d.addEventListener('click', function () {
      var cal = d.closest('.cal');
      cal.querySelectorAll('.d.sel').forEach(function (s) {
        s.classList.remove('sel'); s.removeAttribute('aria-current');
      });
      d.classList.add('sel'); d.setAttribute('aria-current', 'date');
    });
  });
})();
