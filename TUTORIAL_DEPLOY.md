# Tutorial Deploy: GitHub + Vercel + Supabase

Panduan ini mengunggah kode ke **GitHub**, menyimpan database di **Supabase** (PostgreSQL), dan menjalankan aplikasi secara online di **Vercel**. Ketiganya gratis dan **tidak membutuhkan kartu kredit/debit**: cukup daftar memakai akun GitHub.

```
 Laptop (XAMPP) ──git push──▶ GitHub ──deploy otomatis──▶ Vercel (aplikasi Laravel)
                                                              │
                                                              ▼
                                                     Supabase (database PostgreSQL)
```

> **Kenapa tidak Supabase saja?** Supabase hanya menyediakan database (plus login, penyimpanan file, dan Edge Functions berbahasa TypeScript). Supabase tidak bisa menjalankan PHP/Laravel, jadi aplikasinya dijalankan di Vercel dan datanya disimpan di Supabase.

Yang perlu disiapkan:

- Akun **GitHub** (github.com). Akun **Supabase** (supabase.com) dan **Vercel** (vercel.com) dibuat dengan tombol *Continue with GitHub*.
- **Git** sudah terpasang di laptop (cek dengan `git --version`).
- Proyek sudah berjalan normal di lokal dan `php artisan test` lulus.

File pendukung deploy yang sudah ada di proyek:

| File | Fungsi |
|---|---|
| `vercel.json` | Pengaturan Vercel: runtime PHP 8.3 (`vercel-php`), region Singapura, dan aturan alamat (file CSS/JS langsung dari `public/`, sisanya ke Laravel) |
| `api/index.php` | Pintu masuk Laravel di Vercel. Mengarahkan cache ke `/tmp` (satu-satunya folder yang bisa ditulisi di Vercel), menyimpan sesi di cookie, dan mengirim log ke menu Logs |
| `public/build/` | Hasil `npm run build` (CSS/JS). Ikut di-commit karena Vercel tidak membuild ulang tampilan |
| `app/Services/GambarService.php`, `config/filesystems.php` | Penyimpanan gambar: folder lokal saat di laptop, Supabase Storage saat online (Bagian 2.7) |
| `database/migrations/..._enable_row_level_security.php` | Mengamankan tabel di Supabase (penjelasan di Bagian 2.6) |
| `Dockerfile`, `docker/` | Hanya untuk alternatif hosting berbasis Docker (Lampiran), tidak dipakai Vercel |

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

Masuk ke folder proyek, lalu build tampilan terlebih dahulu (hasilnya ikut di-upload):

```bash
cd C:\xampp\htdocs\inventaris
```
```bash
npm run build
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

Di daftar itu **tidak boleh** ada `.env`, `.env.supabase`, folder `vendor/`, atau `node_modules/`. Semuanya sudah diabaikan lewat `.gitignore`. Folder `public/build/` **boleh dan memang harus** ikut.

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

1. Login ke supabase.com (*Continue with GitHub*), lalu klik **New project**.
2. Isi **Project name**, misalnya `gudang-sekolah`.
3. Isi **Database Password**. Klik **Generate a password**, lalu **salin dan simpan** karena password ini diperlukan nanti.
4. **Region**: pilih **Southeast Asia (Singapore)**, yang paling dekat dengan Indonesia dan sama dengan region Vercel di `vercel.json`.
5. Klik **Create new project** dan tunggu sekitar 1–2 menit.

### 2.2 Ambil data koneksi

1. Di halaman project, klik tombol **Connect** di bagian atas.
2. Pilih tab **Session pooler**.
   - **Jangan** pakai *Direct connection*. Koneksi langsung hanya mendukung IPv6, sedangkan Vercel dan kebanyakan jaringan rumah/sekolah memakai IPv4.
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

Langkah ini diperlukan agar laptop bisa membuat tabel dan mengisi data awal ke Supabase.

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

### 2.7 Penyimpanan gambar (Supabase Storage)

Di Vercel, file yang diunggah **tidak bisa disimpan permanen**: satu-satunya folder yang bisa ditulisi (`/tmp`) dikosongkan sewaktu-waktu. Karena itu, foto profil, gambar barang, dan foto bukti disimpan di **Supabase Storage**. Laravel mengaksesnya lewat protokol S3, yang sudah didukung paket `league/flysystem-aws-s3-v3` di proyek.

**a. Buat bucket**

1. Di dashboard Supabase buka menu **Storage**, lalu klik **New bucket**.
2. Nama bucket: `gambar`.
3. Aktifkan **Public bucket** supaya gambar bisa ditampilkan di website.
4. (Opsional) Di **Additional configuration**, batasi *Allowed MIME types* ke `image/jpeg, image/png, image/webp` dan *File size limit* ke 2 MB.
5. Klik **Create**.

**b. Buat kunci akses S3**

1. Buka **Storage → Settings** (atau **Project Settings → Storage**), bagian **S3 Connection**.
2. Catat **Endpoint** dan **Region** yang tertera, contohnya:
   - Endpoint: `https://abcdefghijklmnop.storage.supabase.co/storage/v1/s3`
   - Region: `ap-southeast-1`
3. Di bagian **S3 Access Keys** klik **New access key**, beri nama `vercel`, lalu salin **Access key ID** dan **Secret access key**. Secret hanya ditampilkan sekali, jadi simpan baik-baik.

**c. Alamat publik gambar**

Gambar di bucket publik dapat dibuka lewat alamat berpola:

```
https://abcdefghijklmnop.supabase.co/storage/v1/object/public/gambar
```

`abcdefghijklmnop` adalah kode project, yang juga ada di username database (`postgres.abcdefghijklmnop`). Alamat ini dipakai untuk `AWS_URL` di langkah 3.3.

> Di laptop (XAMPP) gambar tetap disimpan di folder `storage/app/public`. Jalankan `php artisan storage:link` sekali agar gambar bisa dibuka dari browser.

---

## Bagian 3 — Hosting Aplikasi di Vercel

### 3.1 Siapkan APP_KEY

Di folder proyek jalankan:

```bash
php artisan key:generate --show
```

Hasilnya berupa teks seperti `base64:xxxxxxxx...`. Salin teks ini untuk langkah 3.3.

### 3.2 Import project dari GitHub

1. Login ke vercel.com dengan **Continue with GitHub**. Pilih paket **Hobby** (gratis, tanpa kartu).
2. Klik **Add New…** → **Project**.
3. Di daftar **Import Git Repository**, klik **Import** pada repo `inventaris-gudang-sekolah`. Kalau repo tidak muncul, klik **Adjust GitHub App Permissions** dan izinkan Vercel mengakses repo tersebut.
4. Pengaturan proyek:

| Pengaturan | Nilai |
|---|---|
| Project Name | `inventaris-gudang-sekolah` (menjadi alamat `https://inventaris-gudang-sekolah.vercel.app`) |
| Framework Preset | **Other** |
| Root Directory | `./` |
| Build & Output Settings | Biarkan saja; sudah diatur oleh `vercel.json` |

### 3.3 Isi Environment Variables

Masih di halaman yang sama, buka **Environment Variables**. Tempel teks berikut ke kolom *Key* (Vercel otomatis memecahnya per baris), lalu sesuaikan nilainya:

```
APP_NAME="Inventaris Gudang Sekolah"
APP_ENV=production
APP_KEY=base64:xxxxxxxx (hasil langkah 3.1)
APP_DEBUG=false
APP_URL=https://inventaris-gudang-sekolah.vercel.app
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.abcdefghijklmnop
DB_PASSWORD=password-database-kamu
DB_SSLMODE=require
UPLOAD_DISK=s3
AWS_ACCESS_KEY_ID=access-key-id-dari-langkah-2.7
AWS_SECRET_ACCESS_KEY=secret-access-key-dari-langkah-2.7
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=gambar
AWS_ENDPOINT=https://abcdefghijklmnop.storage.supabase.co/storage/v1/s3
AWS_URL=https://abcdefghijklmnop.supabase.co/storage/v1/object/public/gambar
AWS_USE_PATH_STYLE_ENDPOINT=true
```

Penjelasan singkat:

- `APP_DEBUG=false`: detail error tidak ditampilkan ke pengunjung (wajib untuk server online).
- `APP_URL`: alamat website. Kalau nama project berbeda, sesuaikan setelah deploy pertama (lihat langkah 3.5).
- `UPLOAD_DISK=s3` dan semua `AWS_*`: gambar disimpan di Supabase Storage (langkah 2.7). Walaupun namanya "AWS", yang dipakai adalah Supabase, karena keduanya memakai protokol S3 yang sama.
- Sesi login (cookie), cache, dan log **tidak perlu** diisi karena sudah diatur otomatis oleh `api/index.php`.

### 3.4 Deploy

1. Klik **Deploy**.
2. Tunggu proses build (sekitar 1–3 menit). Di log akan terlihat `🐘 Installing Composer dependencies`.
3. Setelah muncul halaman **Congratulations**, klik **Continue to Dashboard**, lalu **Visit**.
4. Login dengan salah satu akun berikut:

| Role | Username | Password |
|---|---|---|
| Admin | `admin_ohim` | `admin123` |
| Manager | `manager_marco` | `@admin123` |
| Operator | `operator_siti` | `operator123` |

5. Uji unggah gambar: buka **Profil Saya** dan unggah foto. Kalau foto tampil, Supabase Storage sudah tersambung dengan benar.

### 3.5 Bila alamat website berbeda

Lihat alamat asli website di **Dashboard → Domains**. Kalau berbeda dengan `APP_URL`:

1. Buka **Settings → Environment Variables**, lalu ubah `APP_URL`.
2. Buka **Deployments**, klik titik tiga pada deployment teratas, lalu **Redeploy**. Perubahan environment variable baru berlaku setelah redeploy.

### 3.6 Catatan paket gratis

- Vercel Hobby **tidak tidur** seperti hosting gratis lain. Akses pertama setelah lama tidak dibuka mungkin butuh 1–3 detik lebih lama.
- Vercel tidak menjalankan migration otomatis. Kalau nanti ada migration baru, jalankan dari laptop: `php artisan migrate --env=supabase`.
- Project Supabase gratis akan di-*pause* bila tidak dipakai selama 1 minggu. Aktifkan lagi lewat dashboard Supabase (**Restore project**). **Buka website sehari sebelum demo** untuk memastikan semuanya aktif.

---

## Bagian 4 — Memperbarui Aplikasi

Setiap kali ada perubahan kode di laptop:

```bash
npm run build
```
```bash
git add .
```
```bash
git commit -m "Jelaskan perubahan yang dibuat"
```
```bash
git push
```

`npm run build` **wajib** dijalankan bila ada perubahan tampilan (file Blade, CSS, atau JS), supaya `public/build/` ikut terbarui. Setelah push, Vercel otomatis membangun dan men-deploy versi terbaru (pantau di tab **Deployments**).

Untuk mengembalikan data online ke kondisi data sampel (misalnya sebelum demo):

```bash
php artisan migrate:fresh --seed --env=supabase
```

### 4.1 Memperbarui website yang sudah online ke versi dengan role Manager & unggah gambar

Kalau website sudah pernah di-deploy sebelum fitur ini ada, lakukan langkah berikut **sekali saja**:

1. Siapkan Supabase Storage (langkah 2.7), lalu tambahkan variabel `UPLOAD_DISK` dan semua `AWS_*` di Vercel (langkah 3.3).
2. Perbarui struktur database Supabase dari laptop. Pilih **salah satu**:
   - Mengembalikan data ke data sampel dengan tiga akun baru (**disarankan** sebelum ujikom):
     ```bash
     php artisan migrate:fresh --seed --env=supabase
     ```
   - Mempertahankan data yang sudah ada (hanya menambah kolom foto dan role Manager):
     ```bash
     php artisan migrate --env=supabase
     ```
     Akun lama tetap memakai role lamanya, jadi ubah salah satunya menjadi **Manager** lewat menu **Pengguna**. Hanya Manager yang dapat memverifikasi barang keluar.
3. Push kode terbaru (`npm run build`, `git add .`, `git commit`, `git push`), lalu tunggu Vercel selesai men-deploy.

---

## Bagian 5 — Mengatasi Masalah

| Gejala | Penyebab | Solusi |
|---|---|---|
| `could not find driver` saat langkah 2.5 | Driver PostgreSQL di XAMPP belum aktif | Ulangi langkah 2.3, lalu buka ulang terminal |
| `Network is unreachable` / `timeout` saat koneksi ke Supabase | Memakai *Direct connection* (IPv6) | Pakai host **Session pooler** (langkah 2.2) |
| `password authentication failed` | Username/password salah | Username harus `postgres.<kode-project>`. Password bisa di-reset di Supabase: **Project Settings → Database → Reset database password** |
| `prepared statement ... already exists` | Memakai Transaction pooler (port 6543) | Ganti `DB_PORT` ke `5432` (Session pooler) |
| Vercel: `No application encryption key has been specified` | `APP_KEY` kosong | Isi `APP_KEY` dari langkah 3.1, lalu **Redeploy** |
| Vercel: `Vite manifest not found` | Folder `public/build` belum ikut di-push | Jalankan `npm run build`, lalu `git add .`, `git commit`, `git push` |
| Halaman error 500 | Bermacam-macam | Buka deployment, lalu tab **Logs**. Bila perlu, sementara ubah `APP_DEBUG=true`, redeploy, lihat pesannya, lalu kembalikan ke `false` |
| Tampilan tanpa CSS / berantakan | `public/build` belum terbaru atau `APP_URL` salah | Jalankan `npm run build` dan push ulang; cek `APP_URL` (langkah 3.5) |
| Online: error 500 saat menyimpan data dengan gambar | Kunci S3 / endpoint salah, atau `UPLOAD_DISK` belum `s3` | Periksa ulang variabel `AWS_*` dan `UPLOAD_DISK=s3` (langkah 3.3), lalu **Redeploy**; lihat tab **Logs** untuk pesan errornya |
| Online: gambar tersimpan tapi tidak tampil (ikon gambar rusak) | Bucket belum publik atau `AWS_URL` salah | Aktifkan **Public bucket** pada bucket `gambar`; pastikan `AWS_URL` berakhiran `/storage/v1/object/public/gambar` |
| Lokal: gambar tidak tampil (404) | Folder `public/storage` belum terhubung | Jalankan `php artisan storage:link` |
| `column "foto" does not exist` / role Manager ditolak | Database Supabase belum diperbarui | Jalankan `php artisan migrate --env=supabase` (langkah 4.1) |
| `419 Page Expired` saat login | `APP_KEY` berubah atau cookie lama | Muat ulang halaman login; hapus cookie situs bila masih terjadi |
| Website sangat lambat | Region tidak sama | Pastikan region Supabase **Singapore** (Vercel sudah diatur ke Singapura di `vercel.json`) |

---

## Catatan Keamanan

- **Jangan pernah** meng-commit `.env`, `.env.supabase`, password database, atau `APP_KEY`.
- Jangan pernah membagikan **Secret access key** Supabase Storage. Bila tersebar, hapus kuncinya di **Storage → Settings → S3 Access Keys**, buat kunci baru, lalu perbarui di Vercel.
- Akun sampel (`admin_ohim`, `manager_marco`, `operator_siti`) memakai password yang tertulis di dokumentasi. Kalau website akan dipakai sungguhan setelah ujikom, ganti password-nya lewat menu **Pengguna**.
- Bila password database Supabase pernah tersebar, segera reset di **Project Settings → Database**, lalu perbarui `DB_PASSWORD` di Vercel (lalu Redeploy) dan di `.env.supabase`.

---

## Lampiran — Alternatif Hosting Berbasis Docker

Proyek juga menyertakan `Dockerfile` (PHP 8.2 + Apache + PostgreSQL) untuk hosting yang mendukung Docker, misalnya Render atau Koyeb. Layanan-layanan tersebut umumnya meminta verifikasi kartu, jadi tutorial ini memakai Vercel. Bila suatu saat memakai hosting Docker, isi environment variables yang sama seperti langkah 3.3, ditambah `LOG_CHANNEL=stderr`, `SESSION_DRIVER=cookie`, dan `RUN_MIGRATIONS=true`. Variabel `UPLOAD_DISK=s3` dan `AWS_*` tetap diperlukan karena penyimpanan container juga tidak permanen (migration otomatis dijalankan oleh `docker/entrypoint.sh` setiap deploy).
