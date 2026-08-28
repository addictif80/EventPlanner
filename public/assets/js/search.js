/** Global quick-search: Ctrl/Cmd+K opens a modal, results grouped by category. */
(function () {
  document.addEventListener('DOMContentLoaded', function () {
    var modal = document.getElementById('search-modal');
    var input = document.getElementById('search-input');
    var results = document.getElementById('search-results');
    var trigger = document.getElementById('search-trigger');
    if (!modal || !input || !results) return;

    var searchUrl = modal.dataset.searchUrl;
    var timer = null;

    function open() {
      modal.classList.remove('d-none');
      input.value = '';
      results.innerHTML = '';
      setTimeout(function () { input.focus(); }, 10);
    }

    function close() {
      modal.classList.add('d-none');
    }

    function render(data) {
      results.innerHTML = '';
      if (!data.results.length) {
        results.innerHTML = '<div class="text-center text-muted small py-4">Aucun résultat.</div>';
        return;
      }
      var byCategory = {};
      data.results.forEach(function (r) {
        (byCategory[r.category] = byCategory[r.category] || []).push(r);
      });
      Object.keys(byCategory).forEach(function (cat) {
        var header = document.createElement('div');
        header.className = 'small text-muted text-uppercase px-3 pt-2';
        header.textContent = cat;
        results.appendChild(header);
        byCategory[cat].forEach(function (r) {
          var a = document.createElement('a');
          a.href = r.url;
          a.className = 'list-group-item list-group-item-action';
          a.textContent = r.label;
          results.appendChild(a);
        });
      });
    }

    input.addEventListener('input', function () {
      clearTimeout(timer);
      var q = input.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }
      timer = setTimeout(function () {
        fetch(searchUrl + '?q=' + encodeURIComponent(q)).then(function (r) { return r.json(); }).then(render).catch(function () {});
      }, 200);
    });

    document.addEventListener('keydown', function (e) {
      if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        open();
      } else if (e.key === 'Escape') {
        close();
      }
    });

    if (trigger) trigger.addEventListener('click', open);
    modal.addEventListener('click', function (e) {
      if (e.target === modal) close();
    });
  });
})();
