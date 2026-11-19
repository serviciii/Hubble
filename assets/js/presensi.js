// Logika modal halaman presensi moderator
document.addEventListener('DOMContentLoaded', function () {
  var modalKoreksi = document.getElementById('modal-koreksi');
  var modalTutup = document.getElementById('modal-tutup');

  function show(el) { if (el) el.classList.add('show'); }
  function hide(el) { if (el) el.classList.remove('show'); }

  // Buka modal konfirmasi tutup presensi
  var btnTutup = document.getElementById('btn-tutup-presensi');
  if (btnTutup) {
    btnTutup.addEventListener('click', function () { show(modalTutup); });
  }

  // Tombol Koreksi di tiap baris peserta
  document.querySelectorAll('.btn-koreksi').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var row = btn.closest('tr');
      var status = row.getAttribute('data-status');
      var targetRadio = status === 'hadir' ? 'izin' : 'hadir';

      document.getElementById('koreksi-id-peserta').value = row.getAttribute('data-peserta-id');
      document.getElementById('koreksi-name').textContent = row.getAttribute('data-name');
      document.getElementById('koreksi-email').textContent = row.getAttribute('data-email');
      document.getElementById('koreksi-current').textContent =
        status === 'hadir' ? 'Hadir' : (status === 'izin' ? 'Izin' : 'Belum diisi');

      document.querySelectorAll('input[name="status"]').forEach(function (r) {
        r.checked = (r.value === targetRadio);
      });

      show(modalKoreksi);
    });
  });

  // Tombol X / Batal menutup modal terdekat
  document.querySelectorAll('[data-close]').forEach(function (el) {
    el.addEventListener('click', function () {
      hide(el.closest('.modal-overlay'));
    });
  });
});
