(function () {
  'use strict';
  document.documentElement.classList.remove('no-js');

  /* Einblenden beim Scrollen */
  var rv = document.querySelectorAll('.rv');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (es) {
      es.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.12 });
    rv.forEach(function (el) { io.observe(el); });
  } else {
    rv.forEach(function (el) { el.classList.add('in'); });
  }

  /* Linie unter der Navigation, sobald nicht mehr ganz oben */
  var nav = document.getElementById('nav');
  var sentinel = document.getElementById('top-sentinel');
  if (nav && sentinel && 'IntersectionObserver' in window) {
    new IntersectionObserver(function (e) {
      nav.classList.toggle('scrolled', !e[0].isIntersecting);
    }).observe(sentinel);
  }

  /* Warteliste */
  var form = document.getElementById('form');
  if (!form) return;
  var started = Date.now();
  var stamp = form.querySelector('input[name="t"]');
  if (stamp) stamp.value = String(started);

  var btn = form.querySelector('button[type="submit"]');
  var formErr = form.querySelector('.form-err');
  var consent = document.getElementById('consent');
  var consentErr = form.querySelector('.consent-err');
  var fields = Array.prototype.slice.call(form.querySelectorAll('input[type="text"], input[type="email"], input[type="tel"]'))
    .filter(function (el) { return !el.closest('.hp'); });

  function check(el) {
    var ok = el.checkValidity() && el.value.trim() !== '';
    var f = el.closest('.field');
    if (f) f.classList.toggle('invalid', !ok);
    return ok;
  }
  fields.forEach(function (el) {
    el.addEventListener('blur', function () { if (el.value) check(el); });
    el.addEventListener('input', function () {
      var f = el.closest('.field');
      if (f && f.classList.contains('invalid')) check(el);
    });
  });

  form.addEventListener('submit', function (e) {
    e.preventDefault();
    formErr.classList.remove('show');
    var ok = fields.map(check).every(Boolean);
    consentErr.classList.toggle('show', !consent.checked);
    if (!ok || !consent.checked) {
      var bad = fields.filter(function (f) { return !check(f); })[0];
      (bad || consent).focus();
      return;
    }

    btn.disabled = true;
    var label = btn.firstChild.nodeValue;
    btn.firstChild.nodeValue = 'Wird gesendet … ';

    fetch(form.getAttribute('action'), {
      method: 'POST',
      body: new FormData(form),
      headers: { 'Accept': 'application/json' }
    })
      .then(function (r) {
        return r.json().then(function (j) { return { ok: r.ok && j && j.ok, j: j }; });
      })
      .then(function (res) {
        if (!res.ok) throw new Error((res.j && res.j.error) || 'Fehler');
        form.classList.add('done');
        var ok = form.querySelector('.ok');
        ok.focus();
      })
      .catch(function () {
        formErr.classList.add('show');
        btn.disabled = false;
        btn.firstChild.nodeValue = label;
      });
  });
})();
