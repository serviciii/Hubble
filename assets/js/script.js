// === Script bersama untuk semua halaman Hubble ===
document.addEventListener('DOMContentLoaded', function () {
  // 1. Drawer Navigasi Mobile (Hamburger Menu)
  var btnOpen = document.getElementById('btn-mobile-menu');
  var btnClose = document.getElementById('btn-close-menu');
  var drawer = document.getElementById('sidebar-drawer');
  var backdrop = document.getElementById('sidebar-backdrop');

  if (btnOpen && drawer && backdrop) {
    function openDrawer() {
      drawer.classList.add('open');
      backdrop.classList.add('show');
      btnOpen.setAttribute('aria-expanded', 'true');
      document.body.style.overflow = 'hidden';
    }

    function closeDrawer() {
      drawer.classList.remove('open');
      backdrop.classList.remove('show');
      btnOpen.setAttribute('aria-expanded', 'false');
      document.body.style.overflow = '';
    }

    btnOpen.addEventListener('click', openDrawer);
    if (btnClose) btnClose.addEventListener('click', closeDrawer);
    backdrop.addEventListener('click', closeDrawer);

    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape' && drawer.classList.contains('open')) {
        closeDrawer();
      }
    });
  }

  // 2. Saklar "Berulang" & tampil/sembunyi "Pola Berulang"
  var sw = document.getElementById('switch-berulang');
  var patternCol = document.getElementById('pattern-col');
  var inputBerulang = document.getElementById('input-berulang');
  var inputPola = document.getElementById('input-pola-berulang');
  var patterns = document.querySelectorAll('.btn-pattern');

  if (sw) {
    sw.addEventListener('click', function () {
      var isOn = sw.classList.toggle('on');
      sw.setAttribute('aria-pressed', isOn ? 'true' : 'false');

      if (inputBerulang) inputBerulang.value = isOn ? '1' : '0';
      if (patternCol) patternCol.hidden = !isOn;

      if (!isOn) {
        patterns.forEach(function (b) { b.classList.remove('selected'); });
        if (inputPola) inputPola.value = '';
      }
    });
  }

  // Pilih pola berulang (Harian / Mingguan / Bulanan)
  patterns.forEach(function (btn) {
    btn.addEventListener('click', function () {
      var wasSelected = btn.classList.contains('selected');
      patterns.forEach(function (b) { b.classList.remove('selected'); });

      if (!wasSelected) {
        btn.classList.add('selected');
        if (inputPola) {
          inputPola.value = btn.getAttribute('data-value') || btn.textContent.trim().toLowerCase();
        }
      } else if (inputPola) {
        inputPola.value = '';
      }
    });
  });

  // 3. Tombol navigasi berbasis data-href
  document.querySelectorAll('[data-href]').forEach(function (el) {
    if (el.id === 'btn-masuk-rapat') return;
    el.addEventListener('click', function () {
      var target = el.getAttribute('data-href');
      if (target) {
        window.location.href = target;
      }
    });
  });

  // 4. Tombol Salin Kode / Link Rapat
  document.querySelectorAll('.btn-copy-detail').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var targetEl = document.getElementById(btn.getAttribute('data-copy-target'));
      if (!targetEl) return;

      var teks = targetEl.textContent.trim();
      var labelAsli = btn.textContent;

      function tandaiTersalin() {
        btn.textContent = 'Tersalin!';
        btn.style.background = '#e7f8ee';
        btn.style.borderColor = '#1f8a34';
        btn.style.color = '#1f8a34';
        setTimeout(function () {
          btn.textContent = labelAsli;
          btn.style.background = '#fff';
          btn.style.borderColor = '#333';
          btn.style.color = '#000';
        }, 2000);
      }

      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(teks).then(tandaiTersalin);
      } else {
        var temp = document.createElement('textarea');
        temp.value = teks;
        document.body.appendChild(temp);
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        tandaiTersalin();
      }
    });
  });
});
