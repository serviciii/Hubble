// Alur modal join rapat di dashboard
document.addEventListener('DOMContentLoaded', function () {
  var mJoin = document.getElementById('modal-join');
  var mFail = document.getElementById('modal-gagal');

  function show(el) { if (el) el.classList.add('show'); }
  function hide(el) { if (el) el.classList.remove('show'); }

  // Klik input kode rapat membuka modal
  var inputKode = document.getElementById('input-kode-rapat');
  if (inputKode) {
    inputKode.addEventListener('click', function (e) {
      e.target.blur();
      show(mJoin);
    });
  }

  // Klik di luar kartu pada modal Join menutupnya
  if (mJoin) {
    mJoin.addEventListener('click', function (e) {
      if (e.target === mJoin) hide(mJoin);
    });
  }

  // Tombol Masuk ke Rapat pada modal Berhasil Join
  var btnMasukRapat = document.getElementById('btn-masuk-rapat');
  if (btnMasukRapat) {
    btnMasukRapat.addEventListener('click', function () {
      var target = btnMasukRapat.getAttribute('data-href');
      if (target) {
        window.location.href = target;
      }
    });
  }

  // Coba lagi: kembali ke modal Join
  var btnCobaLagi = document.getElementById('btn-coba-lagi');
  if (btnCobaLagi) {
    btnCobaLagi.addEventListener('click', function () {
      hide(mFail);
      show(mJoin);
    });
  }
});
