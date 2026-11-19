# Hubble (Versi PHP Native)

Aplikasi pengelolaan rapat berbasis **PHP Native + MySQL (PDO)**

## Struktur Folder
- `config/` : Koneksi database (`database.php`) dan fungsi bantu umum (`helpers.php`).
- `includes/` : Komponen tampilan berulang (`header.php`, `sidebar.php`, `footer.php`).
- `assets/` : File CSS, JavaScript, dan ikon SVG.
- `auth/` : Halaman tampilan Masuk, Daftar, dan Logout.
- `akun/` : Halaman Pengaturan Akun (ubah profil, sandi, dan keluar akun).
- `moderator/` : Halaman khusus Moderator (Buat Rapat, Detail, Edit, Presensi, Rekap Kehadiran, Notulensi).
- `peserta/` : Halaman khusus Peserta Rapat (Detail, Presensi, Notulensi).
- `notulis/` : Halaman khusus Notulis (Editor tulis & ajukan notulensi).
- `proses/` : File pemrosesan form (`INSERT` / `UPDATE` ke database MySQL).
- `index.php` & `dashboard.php` : Pintu masuk dan halaman utama aplikasi.

## Cara Menjalankan di Laragon / XAMPP
1. Pastikan Apache dan MySQL berjalan.
2. Pastikan database `hubble` sudah terpasang (import `hubble.sql` jika belum ada).
3. Sesuaikan username/password database di `config/database.php` jika berbeda dari bawaan (`root` tanpa password).
4. Buka di browser: `http://localhost/hubble/` (atau `http://hubble.test/` jika memakai virtual host Laragon).
