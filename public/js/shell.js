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

  // ---- password visibility toggle -------------------------------------
  document.querySelectorAll('input[type="password"]').forEach(function (input) {
    var wrap = document.createElement('span');
    wrap.className = 'pw-wrap';
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);

    var toggle = document.createElement('button');
    toggle.type = 'button';
    toggle.className = 'pw-toggle';
    toggle.setAttribute('aria-label', 'Show password');
    toggle.setAttribute('aria-pressed', 'false');
    toggle.innerHTML =
      '<svg class="i-eye" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/><circle cx="12" cy="12" r="3"/></svg>' +
      '<svg class="i-eye-off" viewBox="0 0 24 24" fill="none" stroke="currentColor" aria-hidden="true"><path d="M17.9 17.9A10.94 10.94 0 0112 19c-7 0-11-7-11-7a20.4 20.4 0 015.06-5.94"/><path d="M9.9 4.24A9.14 9.14 0 0112 4c7 0 11 7 11 7a20.3 20.3 0 01-3.22 4.31"/><path d="M14.12 14.12a3 3 0 11-4.24-4.24"/><path d="M1 1l22 22"/></svg>';
    wrap.appendChild(toggle);

    toggle.addEventListener('click', function () {
      var showing = input.type === 'text';
      input.type = showing ? 'password' : 'text';
      toggle.setAttribute('aria-pressed', String(!showing));
      toggle.setAttribute('aria-label', showing ? 'Show password' : 'Hide password');
      wrap.classList.toggle('pw-visible', !showing);
    });
  });

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
