# Tutorial Deploy GitHub, Vercel, dan Supabase

| Keterangan | Isi |
|---|---|
| Aplikasi | Inventaris Gudang Sekolah (Laravel 12) |
| Kode | GitHub |
| Hosting aplikasi | Vercel (paket Hobby, gratis, tanpa kartu kredit) |
| Database | Supabase PostgreSQL (gratis) |
| Penyimpanan gambar | Supabase Storage (gratis) |

Alur kerja:

```
 Laptop (XAMPP) --git push--> GitHub --deploy otomatis--> Vercel (aplikasi Laravel)
                                                             |
                                                             v
                                         Supabase (database PostgreSQL + Storage gambar)
```

Supabase tidak dapat menjalankan PHP / Laravel (hanya database, login, penyimpanan file, dan fungsi TypeScript), sehingga aplikasinya dijalankan di Vercel sedangkan data dan gambarnya disimpan di Supabase.

Yang perlu disiapkan:

- Akun **GitHub**. Akun **Supabase** dan **Vercel** cukup dibuat dengan tombol *Continue with GitHub*.
- **Git** terpasang di laptop (cek dengan `git --version`).
- Program berjalan normal di lokal dan `php artisan test` lulus.

File pendukung deploy di proyek:

| File | Fungsi |
|---|---|
| `vercel.json` | Runtime PHP 8.3 (`vercel-php@0.7.4`), region Singapura, file CSS/JS (`/build`) dan suara (`/audio`) langsung dari `public/`, selebihnya ke Laravel |
| `api/index.php` | Pintu masuk Laravel di Vercel: cache ke `/tmp`, sesi di cookie, log ke menu Logs, gambar ke Supabase Storage |
| `public/build/` | Hasil `npm run build`; ikut di-commit karena Vercel tidak membuild ulang tampilan |
| `app/Services/GambarService.php`, `config/filesystems.php` | Penyimpanan gambar: lokal di laptop, Supabase Storage saat online |
| `database/migrations/..._enable_row_level_security.php` | Mengamankan tabel di Supabase |

<!-- daftar-isi -->

<!-- halaman-baru -->

## 1. Mengunggah Kode ke GitHub

### 1.1 Atur identitas Git (sekali per laptop)

```bash
git config --global user.name "Nama Kamu"
git config --global user.email "email-github-kamu@gmail.com"
```

### 1.2 Buat repository di GitHub

1. Buka github.com → tombol **+** → **New repository**.
2. Isi **Repository name**, misalnya `inventaris-gudang-sekolah`.
3. Pilih **Public** (dapat dilihat asesor) atau **Private**.
4. **Jangan** mencentang README, .gitignore, atau license karena sudah ada di proyek.
5. Klik **Create repository**, lalu salin URL-nya, misalnya `https://github.com/username/inventaris-gudang-sekolah.git`.

### 1.3 Push pertama kali

```bash
cd C:\xampp\htdocs\inventaris
npm run build
git init
git add .
git status
```

Periksa hasil `git status`: **tidak boleh** ada `.env`, `.env.supabase`, `vendor/`, atau `node_modules/` (sudah diabaikan `.gitignore`). Folder `public/build/` justru harus ikut.

```bash
git commit -m "Aplikasi Inventaris Gudang Sekolah"
git branch -M main
git remote add origin https://github.com/username/inventaris-gudang-sekolah.git
git push -u origin main
```

Saat push pertama muncul jendela login GitHub; login lewat browser dan push berlanjut otomatis.

## 2. Database dan Penyimpanan di Supabase

Supabase memakai **PostgreSQL**. Program sudah diuji di PostgreSQL (seluruh test lulus), jadi tidak ada kode yang perlu diubah; yang berganti hanya pengaturan koneksi.

### 2.1 Buat project

1. Login supabase.com → **New project**.
2. **Project name**: misalnya `gudang-sekolah`.
3. **Database Password**: klik *Generate a password*, lalu **salin dan simpan**.
4. **Region**: **Southeast Asia (Singapore)**.
5. Klik **Create new project**, tunggu 1–2 menit.

### 2.2 Ambil data koneksi

1. Klik tombol **Connect** di atas halaman project.
2. Pilih tab **Session pooler** (bukan *Direct connection* yang hanya IPv6, dan bukan *Transaction pooler* port 6543).
3. Catat datanya:

| Data | Contoh |
|---|---|
| host | `aws-0-ap-southeast-1.pooler.supabase.com` |
| port | `5432` |
| database | `postgres` |
| user | `postgres.abcdefghijklmnop` |
| password | password dari langkah 2.1 |

`abcdefghijklmnop` adalah **kode project**; kode ini juga dipakai di alamat Storage nanti.

### 2.3 Aktifkan driver PostgreSQL di XAMPP

1. Buka `C:\xampp\php\php.ini`.
2. Hapus tanda `;` di depan dua baris berikut:
   ```
   extension=pdo_pgsql
   extension=pgsql
   ```
3. Simpan, buka ulang terminal, lalu pastikan `php -m` menampilkan `pdo_pgsql`.

### 2.4 Buat file koneksi `.env.supabase`

```bash
copy .env .env.supabase
```

Ubah bagian database di `.env.supabase`:

```
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-southeast-1.pooler.supabase.com
DB_PORT=5432
DB_DATABASE=postgres
DB_USERNAME=postgres.abcdefghijklmnop
DB_PASSWORD=password-database-kamu
DB_SSLMODE=require
```

File ini sudah diabaikan `.gitignore`, jadi tidak ikut ter-upload.

### 2.5 Buat tabel dan isi data sampel

```bash
php artisan migrate:fresh --seed --env=supabase
```

`--env=supabase` membuat Laravel membaca `.env.supabase`, sehingga database lokal tidak tersentuh. Perintah ini **menghapus semua tabel** lalu membuat ulang beserta data sampel dan tiga akun (lihat dokumen 04 Daftar Akun).

Periksa di dashboard Supabase → **Table Editor**: harus ada tabel `user`, `kategori`, `barang`, `barang_masuk`, `barang_keluar`, dan `migrations`.

### 2.6 Row Level Security

Supabase membuka tabel lewat REST API publiknya. Migration `enable_row_level_security` mengaktifkan **RLS** di semua tabel sehingga API publik tidak dapat membaca data (terutama tabel `user`). Peringatan "RLS enabled, no policies" memang disengaja. Laravel tetap dapat mengakses data karena terhubung sebagai pemilik tabel.

### 2.7 Supabase Storage untuk gambar

File yang diunggah di Vercel tidak tersimpan permanen, sehingga gambar disimpan di Supabase Storage (protokol S3).

**a. Buat bucket**

1. Menu **Storage** → **New bucket**.
2. Nama: `gambar`. Aktifkan **Public bucket**.
3. (Opsional) Batasi *Allowed MIME types* ke `image/jpeg, image/png, image/webp` dan *File size limit* 2 MB.
4. Klik **Create**.

**b. Buat kunci akses S3**

1. **Storage → Settings**, bagian **S3 Connection**: catat **Endpoint** (contoh `https://abcdefghijklmnop.storage.supabase.co/storage/v1/s3`) dan **Region** (contoh `ap-southeast-1`).
2. Bagian **S3 Access Keys** → **New access key** → salin **Access key ID** dan **Secret access key** (secret hanya tampil sekali).

**c. Alamat publik gambar**

```
https://abcdefghijklmnop.supabase.co/storage/v1/object/public/gambar
```

Di laptop, gambar tetap disimpan di `storage/app/public` (jalankan `php artisan storage:link` sekali).

## 3. Hosting di Vercel

### 3.1 Buat APP_KEY

```bash
php artisan key:generate --show
```

Salin hasilnya (`base64:...`).

### 3.2 Import project

1. Login vercel.com (*Continue with GitHub*), paket **Hobby**.
2. **Add New… → Project** → **Import** repo `inventaris-gudang-sekolah` (bila tidak muncul: *Adjust GitHub App Permissions*).
3. Pengaturan:

| Pengaturan | Nilai |
|---|---|
| Project Name | `inventaris-gudang-sekolah` (alamat `https://inventaris-gudang-sekolah.vercel.app`) |
| Framework Preset | **Other** |
| Root Directory | `./` |
| Build & Output Settings | Biarkan; sudah diatur `vercel.json` |

### 3.3 Environment Variables

Tempel ke kolom **Environment Variables**, lalu sesuaikan nilainya:

```
APP_NAME="Inventaris Gudang Sekolah"
APP_ENV=production
APP_KEY=base64:hasil-langkah-3.1
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
AWS_ACCESS_KEY_ID=access-key-id
AWS_SECRET_ACCESS_KEY=secret-access-key
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=gambar
AWS_ENDPOINT=https://abcdefghijklmnop.storage.supabase.co/storage/v1/s3
AWS_URL=https://abcdefghijklmnop.supabase.co/storage/v1/object/public/gambar
AWS_USE_PATH_STYLE_ENDPOINT=true
```

| Variabel | Penjelasan |
|---|---|
| `APP_DEBUG=false` | Detail error tidak ditampilkan ke pengunjung |
| `APP_URL` | Alamat website; sesuaikan bila nama project berbeda |
| `DB_*` | Koneksi database Supabase (langkah 2.2) |
| `UPLOAD_DISK`, `AWS_*` | Penyimpanan gambar di Supabase Storage (langkah 2.7); namanya "AWS" karena memakai protokol S3 yang sama |

Sesi, cache, dan log tidak perlu diisi karena sudah diatur `api/index.php`.

### 3.4 Deploy

1. Klik **Deploy** dan tunggu 1–3 menit.
2. Klik **Continue to Dashboard → Visit**.
3. Login memakai akun di dokumen 04 Daftar Akun.
4. Klik **foto profil** di kanan atas (panel Profil Saya) dan unggah foto. Bila foto tampil, Supabase Storage sudah tersambung.

Bila alamat website berbeda dengan `APP_URL`: ubah `APP_URL` di **Settings → Environment Variables**, lalu **Deployments → ⋯ → Redeploy** (variabel baru berlaku setelah redeploy).

### 3.5 Catatan paket gratis

- Vercel Hobby tidak "tidur"; akses pertama mungkin 1–3 detik lebih lama.
- Vercel tidak menjalankan migration. Migration baru dijalankan dari laptop: `php artisan migrate --env=supabase`.
- Project Supabase gratis di-*pause* bila tidak dipakai seminggu; aktifkan lagi lewat **Restore project**. **Buka website sehari sebelum demo.**

## 4. Memperbarui Aplikasi

Setiap ada perubahan kode:

```bash
npm run build
git add .
git commit -m "Jelaskan perubahan"
git push
```

`npm run build` wajib bila ada perubahan tampilan (Blade, CSS, JS). Setelah push, Vercel otomatis men-deploy (pantau di tab **Deployments**).

Bila ada **migration baru** (struktur database berubah):

```bash
php artisan migrate --env=supabase
```

Mengembalikan data online ke data sampel (misalnya sebelum demo):

```bash
php artisan migrate:fresh --seed --env=supabase
```

## 5. Mengatasi Masalah

| Gejala | Penyebab | Solusi |
|---|---|---|
| `could not find driver` | Driver PostgreSQL XAMPP belum aktif | Ulangi langkah 2.3, buka ulang terminal |
| `Network is unreachable` / timeout | Memakai *Direct connection* | Pakai host **Session pooler** |
| `password authentication failed` | Username / password salah | Username `postgres.<kode-project>`; reset password di **Project Settings → Database** |
| `prepared statement ... already exists` | Memakai port 6543 | Ganti `DB_PORT=5432` |
| `Migration table not found` / error 500 saat login | Tabel belum dibuat di Supabase | `php artisan migrate --seed --env=supabase` |
| `column "foto" does not exist` | Migration terbaru belum dijalankan | `php artisan migrate --env=supabase` |
| `No application encryption key` | `APP_KEY` kosong | Isi `APP_KEY`, lalu Redeploy |
| `Vite manifest not found` / tampilan tanpa CSS | `public/build` belum ikut / `APP_URL` salah | `npm run build`, commit, push; cek `APP_URL` |
| Error 500 saat menyimpan gambar | Kunci S3 / endpoint salah | Cek `UPLOAD_DISK` dan `AWS_*`, Redeploy, lihat tab Logs |
| Gambar tidak tampil (ikon rusak) | Bucket belum publik / `AWS_URL` salah | Aktifkan Public bucket; `AWS_URL` berakhiran `/object/public/gambar` |
| Lokal: gambar 404 | Folder `public/storage` belum dibuat | `php artisan storage:link` |
| Pesan "Halaman sudah terlalu lama dibuka..." | Formulir dibuka lebih dari 120 menit | Kirim ulang formulir; isian sudah terisi kembali |
| Website lambat | Region berbeda | Gunakan region Singapore di Supabase |

## 6. Catatan Keamanan

- Jangan pernah meng-commit `.env`, `.env.supabase`, password database, `APP_KEY`, atau secret Storage.
- Bila password database atau secret Storage tersebar, buat ulang di dashboard Supabase lalu perbarui di Vercel (Redeploy) dan `.env.supabase`.
- Bila website dipakai sungguhan setelah ujikom, ganti password akun sampel lewat menu **Pengguna** atau panel **Profil Saya** (klik foto profil).
