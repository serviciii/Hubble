# Hubble (Versi PHP Native)

Aplikasi pengelolaan rapat sederhana berbasis **PHP Native + MySQL (PDO)**. Hubble memudahkan pengelolaan jadwal rapat, pencatatan kehadiran (presensi), serta penyusunan notulensi berdasarkan pembagian peran pengguna (**Moderator**, **Notulis**, dan **Peserta**).

## Fitur Utama
- **Autentikasi & Manajemen Akun:** Masuk, daftar akun baru, ubah profil, dan ganti kata sandi.
- **Peran Moderator:** Membuat rapat baru, mengedit detail rapat, memantau presensi, melihat rekap kehadiran, dan meninjau notulensi.
- **Peran Notulis:** Menulis, mengedit, dan mengajukan hasil notulensi rapat.
- **Peran Peserta:** Melihat detail undangan rapat, mengisi presensi kehadiran, dan membaca hasil notulensi.

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

---

## Cara Menjalankan Project dari Nol (Menggunakan Docker)

### Tahap 1: Install Tools yang Dibutuhkan
Jika di laptopmu belum terpasang Git dan Docker, install keduanya terlebih dahulu:

1. **Install Git:**
   - Unduh di [git-scm.com/downloads](https://git-scm.com/downloads).
   - Jalankan installer dan klik **Next** terus hingga selesai (gunakan pengaturan bawaan).
2. **Install Docker Desktop:**
   - Unduh di [docker.com/products/docker-desktop](https://www.docker.com/products/docker-desktop/).
   - *(Khusus Windows)* Saat instalasi muncul jendela *Configuration*, pilih **All-users installation** dan pastikan opsi **WSL 2** tercentang agar fitur virtualisasi Windows diaktifkan otomatis.
   - Jika diminta **Restart** laptop setelah instalasi selesai, silakan restart terlebih dahulu.
   - Buka aplikasi **Docker Desktop**, klik **Accept**, lalu pilih **Continue without signing in** (tidak wajib membuat akun). Tunggu hingga status di pojok kiri bawah berwarna hijau (**Engine running**).

### Tahap 2: Download (Clone) Project dari GitHub
1. Buat atau buka folder tempat kamu biasa menyimpan project (misalnya di `Documents` atau `D:\Project`).
2. Klik kanan di dalam folder tersebut lalu pilih **Open in Terminal** (atau buka **Git Bash** / terminal di VS Code).
3. Jalankan perintah berikut untuk mengunduh kode project:
   ```bash
   git clone https://github.com/serviciii/Hubble.git
   ```
4. Masuk ke folder project yang baru terunduh:
   ```bash
   cd <NAMA_FOLDER_PROJECT> | (hubble)
   ```

### Tahap 3: Jalankan Aplikasi & Database dengan Docker
Pastikan aplikasi **Docker Desktop** sudah menyala (ikon paus hijau di pojok kiri bawah), lalu jalankan perintah ini di terminal folder project:

```bash
docker compose up -d --build
```
*(Proses pertama kali akan memakan waktu beberapa menit karena Docker mengunduh sistem PHP, MySQL, dan phpMyAdmin secara otomatis).*

### Tahap 4: Import Database (`hubble.sql`)
Setelah proses di atas selesai, masukkan struktur tabel database ke dalam Docker:
1. Buka browser dan akses **phpMyAdmin** di: `http://localhost:8081`
   *(Jika diminta login, masukkan Username: `root` dan Password: `root`)*.
2. Klik nama database **`hubble_db`** (atau **`hubble`**, sesuai yang tertera di panel sebelah kiri).
3. Klik tab **Import** di bagian atas.
4. Klik **Choose File**, pilih file `hubble.sql` yang ada di dalam folder project ini, lalu klik tombol **Import** (atau **Go**) di bagian bawah.

### Tahap 5: Buka Aplikasi di Browser
Sekarang aplikasi sudah siap digunakan! Buka alamat berikut di browser:
- **Aplikasi Hubble:** `http://localhost:8080`
- **phpMyAdmin (Database):** `http://localhost:8081`

---

## Pemakaian Docker Sehari-hari (Setelah Setup Awal)
Kamu **tidak perlu** mengulang tahap *build* atau *import database* lagi di hari berikutnya:
- **Menyalakan aplikasi:** Buka Docker Desktop, lalu jalankan `docker compose up -d` di terminal (atau klik tombol **Start / Play** pada grup container di Docker Desktop).
- **Mematikan aplikasi (setelah selesai ngoding):** Jalankan `docker compose stop` di terminal (atau klik tombol **Stop** di Docker Desktop). Data database tetap aman dan tidak akan hilang.

---

## Cara Alternatif: Menjalankan di Laragon / XAMPP (Tanpa Docker)
Jika kamu lebih memilih menggunakan Laragon atau XAMPP:
1. Pastikan modul **Apache** dan **MySQL** sudah berjalan.
2. Buat database baru bernama `hubble` di phpMyAdmin (`http://localhost/phpmyadmin`), lalu import file `hubble.sql`.
3. Buka file `config/database.php` dan sesuaikan pengaturan koneksinya menjadi lokal:
   ```php
   $dbHost = 'localhost';$dbName = 'hubble';
   $dbUser = 'root';$dbPass = '';
   ```
4. Buka di browser: `http://localhost/hubble/` (atau `http://hubble.test/` jika memakai virtual host Laragon).

---

## Panduan Update Kode ke GitHub (Alur Kerja Tim)

**Penting:** Jangan melakukan `git push` langsung ke branch `main`. Selalu buat branch baru untuk setiap fitur atau perbaikan yang dikerjakan:

```bash
# 1. Pastikan berada di branch main dan ambil update terbaru
git checkout main
git pull origin main

# 2. Buat dan pindah ke branch baru (sesuaikan nama branch dengan tugasmu)
git checkout -b fitur/nama-fitur

# 3. Setelah selesai ngoding, simpan dan push ke branch tersebut
git add .
git commit -m "feat: deskripsi singkat perubahan yang dibuat"
git push -u origin fitur/nama-fitur
```

4. Buka repository di **GitHub**, lalu klik tombol **Compare & pull request** untuk menggabungkan branch kamu ke `main` setelah dicek bersama tim.