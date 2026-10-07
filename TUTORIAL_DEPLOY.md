# Tutorial Deploy: GitHub + Render + Supabase

Panduan ini mengunggah kode ke **GitHub**, menyimpan database di **Supabase** (PostgreSQL), dan menjalankan aplikasi secara online di **Render**. Semuanya memakai paket gratis.

```
 Laptop (XAMPP) ──git push──▶ GitHub ──deploy otomatis──▶ Render (aplikasi Laravel)
                                                              │
                                                              ▼
                                                     Supabase (database PostgreSQL)
```

Yang perlu disiapkan:

- Akun **GitHub** (github.com), **Supabase** (supabase.com), dan **Render** (render.com). Daftar ke Supabase dan Render bisa memakai akun GitHub.
- **Git** sudah terpasang di laptop (cek dengan `git --version`).
- Proyek sudah berjalan normal di lokal dan `php artisan test` lulus.

File pendukung deploy yang sudah ada di proyek:

| File | Fungsi |
|---|---|
| `Dockerfile` | Resep membangun server (PHP 8.2 + Apache + ekstensi PostgreSQL) dan build CSS |
| `docker/entrypoint.sh` | Dijalankan saat server menyala: cache konfigurasi, migrasi database, menyalakan Apache |
| `.dockerignore` | Daftar file yang tidak ikut dibangun ke server |
| `database/migrations/..._enable_row_level_security.php` | Mengamankan tabel di Supabase (penjelasan di Bagian 2.6) |

---

## Bagian 1 — Mengunggah Kode ke GitHub

### 1.1 Atur identitas Git (sekali saja per laptop)

Buka terminal (PowerShell / Git Bash / terminal VS Code), lalu:

```bash
git config --global user.name "Nama Kamu"
```
```bash
git config --global user.email "email-github-kamu@gmail.com"
```

### 1.2 Buat repository kosong di GitHub

1. Buka github.com, klik tombol **+** di kanan atas, lalu **New repository**.
2. Isi **Repository name**, misalnya `inventaris-gudang-sekolah`.
3. Pilih **Public** (agar bisa dilihat asesor) atau **Private**.
4. **Jangan** centang "Add a README", ".gitignore", atau "license" karena file-file itu sudah ada di proyek.
5. Klik **Create repository**, lalu salin URL repo yang muncul, contohnya `https://github.com/username/inventaris-gudang-sekolah.git`.

### 1.3 Push kode dari laptop

Masuk ke folder proyek:

```bash
cd C:\xampp\htdocs\inventaris
```

Jadikan folder ini repository Git dan masukkan semua file:

```bash
git init
```
```bash
git add .
```

**Periksa dulu sebelum commit.** Pastikan file rahasia tidak ikut:

```bash
git status
```

Di daftar itu **tidak boleh** ada `.env`, `.env.supabase`, folder `vendor/`, atau `node_modules/`. Semuanya sudah diabaikan lewat `.gitignore`. Kalau ternyata muncul, berhenti dan periksa `.gitignore`.

Lanjutkan:

```bash
git commit -m "Aplikasi Inventaris Gudang Sekolah"
```
```bash
git branch -M main
```
```bash
git remote add origin https://github.com/username/inventaris-gudang-sekolah.git
```
```bash
git push -u origin main
```

Saat push pertama, akan muncul jendela login GitHub (Git Credential Manager). Login lewat browser, dan push akan berlanjut otomatis. Muat ulang halaman repo di GitHub; semua file proyek sekarang sudah tampil.

---

## Bagian 2 — Database di Supabase

> Supabase memakai **PostgreSQL**, bukan MySQL. Aplikasi ini sudah diuji di PostgreSQL (semua 50 test lulus), jadi tidak ada kode yang perlu diubah. Yang berganti hanya pengaturan koneksi.

### 2.1 Buat project

1. Login ke supabase.com, lalu klik **New project**.
2. Isi **Project name**, misalnya `gudang-sekolah`.
3. Isi **Database Password**. Klik **Generate a password**, lalu **salin dan simpan** karena password ini diperlukan nanti.
4. **Region**: pilih **Southeast Asia (Singapore)**, yang paling dekat dengan Indonesia dan sama dengan region Render nanti.
5. Klik **Create new project** dan tunggu sekitar 1–2 menit.

### 2.2 Ambil data koneksi

1. Di halaman project, klik tombol **Connect** di bagian atas.
2. Pilih tab **Session pooler**.
   - **Jangan** pakai *Direct connection*. Koneksi langsung hanya mendukung IPv6, sedangkan Render dan kebanyakan jaringan rumah/sekolah memakai IPv4.
   - **Jangan** pakai *Transaction pooler* (port 6543), karena tidak cocok untuk Laravel.
3. Catat nilai-nilai berikut (contoh):

| Data | Contoh nilai |
|---|---|
| host | `aws-0-ap-southeast-1.pooler.supabase.com` |
| port | `5432` |
| database | `postgres` |
| user | `postgres.abcdefghijklmnop` (ada kode project di belakangnya) |
| password | password dari langkah 2.1 |

### 2.3 Aktifkan driver PostgreSQL di XAMPP

Langkah ini diperlukan agar laptop bisa mengisi data awal ke Supabase.

1. Buka `C:\xampp\php\php.ini` dengan editor teks.
2. Cari dua baris berikut dan **hapus tanda titik koma** di depannya:
   ```
   ;extension=pdo_pgsql
   ;extension=pgsql
   ```
   menjadi:
   ```
   extension=pdo_pgsql
   extension=pgsql
   ```
3. Simpan, lalu tutup dan buka ulang terminal. Pastikan driver sudah aktif:
   ```bash
   php -m
   ```
   Di daftar yang muncul harus ada `pdo_pgsql`.

### 2.4 Buat file koneksi khusus Supabase

Di folder proyek, salin `.env` menjadi `.env.supabase`:

```bash
copy .env .env.supabase
```

Buka `.env.supabase`, lalu ganti bagian database menjadi data dari langkah 2.2:

```
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.abcdefghijklmnop
DB_PASSWORD=password-database-kamu
DB_SSLMODE=require
```

File `.env.supabase` sudah terdaftar di `.gitignore`, jadi tidak akan ikut ter-upload ke GitHub.

### 2.5 Buat tabel dan isi data sampel

```bash
php artisan migrate:fresh --seed --env=supabase
```

Opsi `--env=supabase` membuat Laravel membaca file `.env.supabase`, bukan `.env`. Database lokal di XAMPP tidak tersentuh.

> **Perhatian:** `migrate:fresh` **menghapus semua tabel** lalu membuatnya ulang. Jalankan perintah ini hanya untuk pengisian pertama, atau saat memang ingin mengembalikan data ke kondisi data sampel.

Periksa hasilnya: di dashboard Supabase buka **Table Editor**. Di sana harus ada tabel `user`, `kategori`, `barang`, `barang_masuk`, `barang_keluar`, dan `migrations` beserta datanya.

### 2.6 Tentang keamanan (Row Level Security)

Supabase otomatis membuka tabel-tabel di database lewat REST API publiknya. Supaya data, terutama tabel `user`, tidak bisa dibaca dari luar lewat API itu, migration `enable_row_level_security` mengaktifkan **RLS** di semua tabel.

- Di Table Editor, tiap tabel akan berlabel **RLS enabled**. Kalau muncul peringatan "no policies", itu memang disengaja: tanpa policy berarti API publik tidak bisa mengakses apa pun.
- Laravel tetap bisa membaca dan menulis data karena terhubung sebagai pemilik tabel.

---

## Bagian 3 — Hosting Aplikasi di Render

### 3.1 Siapkan APP_KEY

Di folder proyek jalankan:

```bash
php artisan key:generate --show
```

Hasilnya berupa teks seperti `base64:xxxxxxxx...`. Salin teks ini untuk langkah 3.3.

### 3.2 Buat Web Service

1. Login ke render.com, lalu klik **New +** → **Web Service**.
2. Pilih **Git Provider: GitHub**, izinkan Render mengakses repo, lalu pilih repo `inventaris-gudang-sekolah`.
3. Isi pengaturan:

| Pengaturan | Nilai |
|---|---|
| Name | `inventaris-gudang-sekolah` (menjadi alamat `https://inventaris-gudang-sekolah.onrender.com`) |
| Language | **Docker** (otomatis terdeteksi dari `Dockerfile`) |
| Branch | `main` |
| Region | **Singapore** |
| Instance Type | **Free** |

### 3.3 Isi Environment Variables

Di bagian **Environment Variables**, tambahkan variabel berikut. Bisa juga lewat **Add from .env**, lalu tempel semuanya sekaligus.

```
APP_NAME="Inventaris Gudang Sekolah"
APP_ENV=production
APP_KEY=base64:xxxxxxxx (hasil langkah 3.1)
APP_DEBUG=false
APP_URL=https://inventaris-gudang-sekolah.onrender.com
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en

LOG_CHANNEL=stderr
SESSION_DRIVER=cookie
CACHE_STORE=file
QUEUE_CONNECTION=sync

DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.abcdefghijklmnop
DB_PASSWORD=password-database-kamu
DB_SSLMODE=require

RUN_MIGRATIONS=true
```

Penjelasan singkat:

- `APP_DEBUG=false`: detail error tidak ditampilkan ke pengunjung (wajib untuk server online).
- `LOG_CHANNEL=stderr`: log error tampil di menu **Logs** Render.
- `SESSION_DRIVER=cookie`: sesi login disimpan di cookie terenkripsi. Server Render gratis bisa restart kapan saja, dan file sesi akan hilang saat itu terjadi.
- `RUN_MIGRATIONS=true`: setiap deploy, server menjalankan `php artisan migrate --force`. Perintah ini hanya menambah tabel/kolom baru dan **tidak menghapus data**.

### 3.4 Deploy

1. (Opsional) Di **Advanced** → **Health Check Path**, isi `/up`.
2. Klik **Deploy Web Service**.
3. Tunggu proses build di tab **Logs** (pertama kali sekitar 5–10 menit). Deploy selesai saat muncul status **Live** dan log `Apache ... resuming normal operations`.
4. Buka `https://inventaris-gudang-sekolah.onrender.com`, lalu login dengan `admin_marco` / `@admin123` atau `operator_ohim` / `operator123`.

### 3.5 Catatan paket gratis Render

- Server **tidur** setelah 15 menit tidak ada pengunjung. Saat dibuka lagi, halaman pertama butuh sekitar **30–60 detik** untuk bangun. **Buka website beberapa menit sebelum demo ke asesor.**
- Paket gratis tidak punya menu *Shell*. Karena itu pengisian data awal dilakukan dari laptop (Bagian 2.5).
- Project Supabase gratis akan di-*pause* bila tidak dipakai selama 1 minggu. Aktifkan lagi lewat dashboard Supabase (**Restore project**).

---

## Bagian 4 — Memperbarui Aplikasi

Setiap kali ada perubahan kode di laptop:

```bash
git add .
```
```bash
git commit -m "Jelaskan perubahan yang dibuat"
```
```bash
git push
```

Render otomatis membangun ulang dan men-deploy versi terbaru (pantau di tab **Events / Logs**). Kalau ada migration baru, migration itu ikut dijalankan karena `RUN_MIGRATIONS=true`.

Untuk mengembalikan data online ke kondisi data sampel (misalnya sebelum demo):

```bash
php artisan migrate:fresh --seed --env=supabase
```

---

## Bagian 5 — Mengatasi Masalah

| Gejala | Penyebab | Solusi |
|---|---|---|
| `could not find driver` saat langkah 2.5 | Driver PostgreSQL di XAMPP belum aktif | Ulangi langkah 2.3, lalu buka ulang terminal |
| `Network is unreachable` / `timeout` saat koneksi ke Supabase | Memakai *Direct connection* (IPv6) | Pakai host **Session pooler** (langkah 2.2) |
| `password authentication failed` | Username/password salah | Username harus `postgres.<kode-project>`. Password bisa di-reset di Supabase: **Project Settings → Database → Reset database password** |
| `prepared statement ... already exists` | Memakai Transaction pooler (port 6543) | Ganti `DB_PORT` ke `5432` (Session pooler) |
| Render: `No application encryption key has been specified` | `APP_KEY` kosong | Isi `APP_KEY` dari langkah 3.1 |
| Halaman error 500 di Render | Bermacam-macam | Buka tab **Logs** di Render. Bila perlu, sementara ubah `APP_DEBUG=true`, lihat pesannya, lalu kembalikan ke `false` |
| Tampilan tanpa CSS / berantakan | `APP_URL` salah | Isi `APP_URL` dengan alamat `https://...onrender.com` yang tepat |
| `419 Page Expired` saat login | `APP_KEY` berubah atau cookie lama | Muat ulang halaman login; hapus cookie situs bila masih terjadi |
| Website lama sekali saat dibuka | Server gratis sedang bangun dari tidur | Tunggu sekitar 1 menit, normal untuk paket gratis |

---

## Catatan Keamanan

- **Jangan pernah** meng-commit `.env`, `.env.supabase`, password database, atau `APP_KEY`.
- Akun sampel (`admin_marco`, `operator_ohim`) memakai password yang tertulis di dokumentasi. Kalau website akan dipakai sungguhan setelah ujikom, ganti password-nya lewat menu **Pengguna**.
- Bila password database Supabase pernah tersebar, segera reset di **Project Settings → Database**, lalu perbarui `DB_PASSWORD` di Render dan `.env.supabase`.
