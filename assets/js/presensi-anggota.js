// Presensi anggota: interaksi tombol Izin dan Kembali
document.addEventListener('DOMContentLoaded', function () {
  var viewDibuka = document.querySelector('[data-view="dibuka"]');
  var viewIzin = document.querySelector('[data-view="izin"]');
  var btnIzin = document.getElementById('btn-izin');
  var btnKembali = document.getElementById('btn-kembali');

  if (btnIzin && viewDibuka && viewIzin) {
    btnIzin.addEventListener('click', function () {
      viewDibuka.hidden = true;
      viewIzin.hidden = false;
    });
  }

  if (btnKembali && viewDibuka && viewIzin) {
    btnKembali.addEventListener('click', function () {
      viewIzin.hidden = true;
      viewDibuka.hidden = false;
    });
  }
});
