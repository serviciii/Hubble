// Alur modal notulensi moderator
document.addEventListener('DOMContentLoaded', function () {
  var pilih = document.getElementById('modal-pilih');
  var konfirmasi = document.getElementById('modal-konfirmasi');
  var sukses = document.getElementById('modal-sukses-tunjuk');
  var errorSama = document.getElementById('modal-error-sama');
  var modalRevisi = document.getElementById('modal-revisi');

  function show(el) { if (el) el.classList.add('show'); }
  function hide(el) { if (el) el.classList.remove('show'); }

  var btnTunjuk = document.getElementById('btn-tunjuk');
  if (btnTunjuk) btnTunjuk.addEventListener('click', function () { show(pilih); });

  var btnGanti = document.getElementById('btn-ganti');
  if (btnGanti) btnGanti.addEventListener('click', function () { show(pilih); });

  // Pilih kandidat dari daftar modal
  document.querySelectorAll('.pick-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var pesertaId = btn.getAttribute('data-peserta-id');
      var nama = btn.getAttribute('data-name');
      var isCurrent = btn.getAttribute('data-current') === '1';

      hide(pilih);

      if (isCurrent) {
        show(errorSama);
      } else {
        document.getElementById('confirm-name').textContent = nama;
        document.getElementById('input-tunjuk-peserta-id').value = pesertaId;
        show(konfirmasi);
      }
    });
  });

  var btnErrorBatalkan = document.getElementById('btn-error-batalkan');
  if (btnErrorBatalkan) btnErrorBatalkan.addEventListener('click', function () { hide(errorSama); });

  var btnConfirmBatal = document.getElementById('btn-confirm-batal');
  if (btnConfirmBatal) btnConfirmBatal.addEventListener('click', function () { hide(konfirmasi); });

  var btnSuksesSelesai = document.getElementById('btn-sukses-selesai');
  if (btnSuksesSelesai) btnSuksesSelesai.addEventListener('click', function () { hide(sukses); });

  var btnMintaRevisi = document.getElementById('btn-minta-revisi');
  if (btnMintaRevisi) btnMintaRevisi.addEventListener('click', function () { show(modalRevisi); });

  var btnBatalRevisi = document.getElementById('btn-batal-revisi');
  if (btnBatalRevisi) btnBatalRevisi.addEventListener('click', function () { hide(modalRevisi); });

  // Klik area gelap menutup modal
  [pilih, konfirmasi, sukses, errorSama, modalRevisi].forEach(function (ov) {
    if (ov) {
      ov.addEventListener('click', function (e) {
        if (e.target === ov) hide(ov);
      });
    }
  });
});
