# Dokumentasi Program Inventaris Gudang Sekolah

| Keterangan | Isi |
|---|---|
| Nama Peserta | Renaissance Ricarda Sugiarto Putra |
| Skema | Pemrogram Junior (Junior Coder) |
| Aplikasi | Inventaris Gudang Sekolah (berbasis web) |
| Framework | Laravel 12.69.2 (PHP 8.2.12) |
| Database | MariaDB 10.4.32 (XAMPP) untuk lokal; PostgreSQL 17.11 (Supabase) untuk versi online |
| Penyimpanan gambar | Folder `storage/app/public` (lokal); Supabase Storage (online) |
| Tampilan | Blade + Tailwind CSS 4.3.3 (dibuild dengan Vite 7.3.6) dan Hotwire Turbo 8, responsif untuk komputer dan HP |
| Editor | Antigravity IDE 2.5.5 |
| Pengujian | PHPUnit 11.5.56 — 96 test, 371 assertion, semua lulus |
| Dokumen terkait | 01 Dokumen Rancangan, 03 Persiapan Asesor, 04 Daftar Akun, 05 Tutorial Deploy |

<!-- daftar-isi -->

<!-- halaman-baru -->

## 1. Deskripsi Program

Inventaris Gudang Sekolah adalah aplikasi web untuk mencatat barang di gudang sekolah beserta lokasi dan kondisinya, mencatat barang yang masuk, dan mengelola barang keluar (peminjaman, pemindahan ke ruangan, pemusnahan) melalui persetujuan, termasuk pengembalian barang pinjaman. Setiap barang, transaksi, dan pengguna dapat dilengkapi gambar (multimedia).

Masalah yang diselesaikan:

- Pencatatan barang yang manual membuat stok tidak jelas dan sulit ditelusuri.
- Barang dapat keluar tanpa persetujuan dan tanpa bukti.
- Tidak ada riwayat siapa yang mencatat dan siapa yang menyetujui.
- Barang pinjaman tidak tercatat kapan kembali dan dalam kondisi apa.
- Tidak diketahui barang berada di gudang mana atau sudah dipindah ke ruangan mana.

Aplikasi menerapkan **pemisahan tugas (separation of duties)** dengan tiga role:

| Role | Peran | Tugas utama |
|---|---|---|
| Admin | Pengelola sistem | Mengelola pengguna, kategori, lokasi, dan data barang; mengoreksi data barang masuk |
| Operator | Petugas gudang | Mendaftarkan barang, mencatat barang masuk, mengajukan barang keluar (dengan foto bukti), mencatat pengembalian pinjaman |
| Manager | Pengawas | Memeriksa rincian kondisi, menyetujui / menolak barang keluar, dan melihat seluruh data; tidak dapat menambah atau mengeluarkan barang |

Aturan utama: **stok bertambah saat barang masuk dicatat**, dan **stok baru berubah setelah Manager menyetujui** permintaan barang keluar. Stok dicatat **per lokasi dan per kondisi** (Baik, Rusak Ringan, Rusak Berat). Setiap verifikasi mencatat Manager yang memverifikasi, tanggal, dan catatannya.

### 1.1 Daftar Fitur

| No | Fitur | Keterangan |
|---|---|---|
| 1 | Login & logout | Login memakai username dan password (password tersimpan ter-hash); halaman login bergaya siluet monokrom dengan animasi, jam berjalan, salam dan suara suasana (audio) sesuai waktu pagi/siang/sore/malam |
| 2 | Dashboard | Sapaan sesuai jam, kartu statistik, grafik stok per kategori, status permintaan, stok menipis, aktivitas terbaru, aksi cepat per role |
| 3 | Profil saya | Klik foto profil → panel kanan untuk ubah nama, foto profil, dan password (wajib password lama) |
| 4 | Kelola pengguna | Tambah, ubah, hapus akun beserta role-nya (Admin) |
| 5 | Kategori | Tambah, ubah, hapus kategori; prefix kode barang otomatis |
| 6 | Data barang | Daftar, cari, filter kategori dan lokasi, detail riwayat, stok per lokasi dan kondisi, tambah dengan kode otomatis, gambar barang |
| 7 | Lokasi | Data master tempat barang (Gudang / Ruangan): tambah, ubah, hapus (Admin) |
| 8 | Barang masuk | Catat penerimaan ke lokasi tertentu dengan rincian kondisi (Baik / Rusak Ringan / Rusak Berat), foto bukti opsional, filter tanggal, koreksi oleh Admin |
| 9 | Barang keluar | Tiga jenis: **Peminjaman**, **Pemindahan** ke ruangan, **Pemusnahan** barang Rusak Berat; rincian kondisi, foto bukti wajib (Pending), Manager memeriksa dan boleh mengoreksi rincian sebelum Setujui / Tolak, tab filter status dan "Sedang Dipinjam" |
| 10 | Pengembalian | Barang pinjaman dikembalikan (boleh bertahap) dengan kondisi saat kembali; status Dipinjam / Dikembalikan Sebagian / Dikembalikan / Terlambat |
| 11 | Cetak | Daftar barang, barang masuk, dan barang keluar dapat dicetak |
| 12 | Tema monokrom & mode terang / gelap | Tampilan hitam-putih ala video Bad Apple!!; tombol bulan/matahari di topbar dan login; tema baru muncul dengan efek lingkaran membesar; tema awal mengikuti jam (malam = gelap) |
| 13 | Musik Bad Apple!! | Tombol not musik di topbar dan login untuk memutar / menjeda lagu Bad Apple!! (MP3), posisi lagu berlanjut saat pindah halaman |
| 14 | Tanpa pindah halaman | Menu dibuka tanpa memuat ulang halaman (Turbo); form tambah, ubah, dan detail tampil sebagai popup |
| 15 | Sidebar bisa ditutup | Tombol burger menutup / membuka sidebar; posisinya diingat |

## 2. Cara Menjalankan Program

Prasyarat: XAMPP (MySQL aktif), PHP 8.2 dengan ekstensi `gd` dan `fileinfo`, Composer, Node.js.

1. Aktifkan **MySQL** di XAMPP Control Panel.
2. Buat database lewat phpMyAdmin: `CREATE DATABASE gudang_sekolah;`
3. Salin konfigurasi: `copy .env.example .env` lalu `php artisan key:generate` (lewati bila `.env` sudah ada).
4. Pasang dependensi (bila folder `vendor` / `node_modules` belum ada): `composer install` dan `npm install`.
5. Buat tabel dan isi data sampel: `php artisan migrate:fresh --seed` (database yang sudah berisi data cukup `php artisan migrate`; data lama otomatis dipindahkan ke struktur lokasi dan kondisi)
6. Hubungkan folder gambar ke folder publik (cukup sekali): `php artisan storage:link`
7. Build tampilan: `npm run build`
8. Jalankan server: `php artisan serve`, lalu buka `http://localhost:8000`
9. Jalankan pengujian: `php artisan test`

Akun login ada di dokumen **04 Daftar Akun**. Cara mengunggah ke GitHub dan meng-online-kan aplikasi ada di dokumen **05 Tutorial Deploy**.

### 2.1 Versi Alat dan Teknologi yang Digunakan

Versi di bawah ini dibaca langsung dari laptop pengembang dan dari proyek (`composer.json`, `package.json`, `vercel.json`) pada 12 Oktober 2026.

**a. Perangkat dan editor**

| Alat | Versi | Keterangan |
|---|---|---|
| Sistem operasi | Windows 11 Pro 25H2 (build 10.0.26200, x64) | Laptop pengembang |
| Antigravity IDE | 2.5.5 | Editor kode (berbasis VS Code OSS 1.107.0, Electron 39.2.3, Chromium 142.0.7444.175, Node.js 22.21.1, V8 14.2.231.21) |
| Google Chrome | 154.0.8037.98 | Browser untuk uji tampilan |
| Git | 2.55.0 (windows.4) | Pengelolaan versi kode, terhubung ke GitHub |

**b. Server lokal (XAMPP)**

| Alat | Versi | Keterangan |
|---|---|---|
| XAMPP | 8.2.12 (Control Panel 3.3.0) | Paket server lokal |
| PHP | 8.2.12 | Bahasa pemrograman (dibawa XAMPP) |
| MariaDB (MySQL) | 10.4.32 | Database lokal `gudang_sekolah` |
| Apache | 2.4.58 | Web server bawaan XAMPP |
| phpMyAdmin | 5.2.1 | Mengelola database lewat browser |

**c. Bahasa, framework, dan pustaka PHP (Composer)**

| Alat | Versi | Keterangan |
|---|---|---|
| Composer | 2.10.3 | Pengelola pustaka PHP |
| Laravel Framework | 12.69.2 | Framework utama (MVC) |
| PHPUnit | 11.5.56 | Pengujian unit dan fitur |
| league/flysystem-aws-s3-v3 | 3.35.3 | Penyimpanan gambar ke Supabase Storage (protokol S3) |
| laravel/tinker | 2.11.1 | Konsol interaktif Laravel |
| laravel/pint | 1.30.4 | Perapi format kode PHP |
| fakerphp/faker | 1.24.1 | Data palsu untuk pengujian |
| mockery/mockery | 1.6.15 | Objek tiruan untuk pengujian |
| nunomaduro/collision | 8.9.5 | Tampilan error di terminal |

**d. Tampilan dan JavaScript (npm)**

| Alat | Versi | Keterangan |
|---|---|---|
| Node.js | 26.7.0 | Menjalankan alat build tampilan |
| npm | 11.19.0 | Pengelola pustaka JavaScript |
| Vite | 7.3.6 | Build CSS dan JavaScript ke `public/build` |
| laravel-vite-plugin | 2.1.0 | Penghubung Vite dengan Laravel |
| Tailwind CSS (+ @tailwindcss/vite) | 4.3.3 | Kerangka gaya tampilan |
| Hotwire Turbo (@hotwired/turbo) | 8.0.23 | Pindah halaman tanpa muat ulang dan popup |
| @fontsource-variable/plus-jakarta-sans | 5.3.0 | Font aplikasi (dibundel, tanpa internet) |
| axios | 1.20.0 | Pustaka permintaan HTTP bawaan Laravel |

**e. Layanan online (hosting)**

| Layanan | Versi / Paket | Keterangan |
|---|---|---|
| GitHub | — | Menyimpan kode (repository `Ujikom-Inventaris`) |
| Vercel | Paket Hobby (gratis), runtime `vercel-php@0.7.4` (PHP 8.3), region Singapura (`sin1`) | Hosting aplikasi |
| Supabase PostgreSQL | 17.11 | Database versi online |
| Supabase Storage | Protokol S3 | Penyimpanan gambar versi online |

**f. Kebutuhan minimum**

PHP **8.2** atau lebih baru (sesuai `composer.json`) dengan ekstensi `gd`, `fileinfo`, `pdo_mysql` (lokal) / `pdo_pgsql` (online); MySQL 5.7+/MariaDB 10.3+ atau PostgreSQL; Composer 2; Node.js 20 atau lebih baru untuk build tampilan.


## 3. Spesifikasi Program

### 3.1 Hak Akses per Role

| No | Fitur | Admin | Operator | Manager |
|---|---|---|---|---|
| 1 | Login, logout, ubah profil sendiri | Ya | Ya | Ya |
| 2 | Dashboard | Ya | Ya | Ya |
| 3 | Kelola pengguna | Ya | Tidak | Tidak |
| 4 | Kelola kategori dan lokasi | Ya | Tidak | Tidak |
| 5 | Lihat data barang dan detail riwayatnya | Ya | Ya | Ya |
| 6 | Tambah barang baru + gambar | Ya | Ya | Tidak |
| 7 | Ubah / hapus barang | Ya | Tidak | Tidak |
| 8 | Catat barang masuk + foto opsional | Tidak | Ya | Tidak |
| 9 | Koreksi (ubah / hapus) barang masuk | Ya | Tidak | Tidak |
| 10 | Ajukan barang keluar (pinjam / pindah / musnahkan) + foto wajib | Tidak | Ya | Tidak |
| 11 | Verifikasi barang keluar (periksa / koreksi rincian kondisi, Setujui / Tolak) | Tidak | Tidak | Ya |
| 12 | Catat pengembalian barang pinjaman | Tidak | Ya | Tidak |
| 13 | Lihat barang masuk / keluar / pengembalian beserta foto, cetak | Ya | Ya | Ya |

Alasan pembagian:

- Tiga peran tanpa tumpang tindih: tidak ada orang yang dapat menyetujui permintaannya sendiri.
- Operator hanya **menambah** data; perubahan dan penghapusan dilakukan Admin.
- Manager hanya **melihat** dan memberi persetujuan.
- Operator boleh mendaftarkan barang baru karena barang harus terdaftar sebelum dicatat sebagai barang masuk.

### 3.2 Aturan Bisnis dan Validasi

- Kode barang dibuat otomatis berpola `PREFIX-NOMOR` (AT, FR, KN, AO, EK); nomor melanjutkan nomor terbesar pada prefix yang sama.
- Stok awal hanya diisi saat barang dibuat (lokasi + rincian kondisi); selanjutnya stok hanya berubah lewat barang masuk, barang keluar, dan pengembalian.
- Stok disimpan di tabel `stok_barang` per barang + lokasi + kondisi; kolom `barang.stok` adalah totalnya.
- Kondisi barang hanya: Baik, Rusak Ringan, Rusak Berat. Setiap transaksi diisi rinciannya, contoh 20 barang = 5 Baik, 10 Rusak Ringan, 5 Rusak Berat; total minimal 1.
- Verifikasi hanya: Pending, Disetujui, Ditolak. Jenis barang keluar hanya: Peminjaman, Pemindahan, Pemusnahan.
- Pengajuan barang keluar wajib mengisi lokasi asal, pemohon, dan foto bukti; jumlah tiap kondisi tidak boleh melebihi stok kondisi itu di lokasi asal, dan dicek ulang saat disetujui.
- **Peminjaman** wajib mengisi rencana tanggal kembali; stok berkurang setelah disetujui, data tidak dihapus, dan stok bertambah lagi saat barang dikembalikan.
- **Pemindahan** wajib mengisi lokasi tujuan yang berbeda dari lokasi asal; stok pindah dari lokasi asal ke lokasi tujuan, total stok tetap.
- **Pemusnahan** hanya untuk barang Rusak Berat; stok berkurang permanen.
- Manager boleh mengoreksi rincian kondisi sebelum menyetujui (contoh Operator menulis 10 Baik, ternyata 7 Baik dan 3 Rusak Ringan). Koreksi dicatat otomatis di catatan verifikasi.
- Pengembalian boleh bertahap; jumlah total pengembalian tidak boleh melebihi sisa pinjaman. Kondisi saat kembali boleh berbeda (misalnya rusak saat dipinjam) dan lokasi penyimpanan boleh dipilih.
- Lokasi yang masih menyimpan stok atau sudah dipakai di transaksi tidak dapat dihapus.
- Penolakan wajib disertai alasan. Permintaan yang sudah diverifikasi tidak dapat diverifikasi ulang.
- Pengajuan barang keluar tidak dapat diubah / dihapus (jejak audit); pengajuan keliru cukup ditolak Manager.
- Gambar hanya JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB. File lama dihapus saat gambar diganti atau datanya dihapus.
- Kategori yang masih dipakai, barang yang punya transaksi, dan pengguna yang punya transaksi tidak dapat dihapus.
- Admin tidak dapat menghapus akunnya sendiri atau mengubah role-nya sendiri (dikunci di form dan ditolak di server).
- Ganti password di Profil wajib memasukkan password lama yang benar. Username dan role hanya diubah Admin.
- Koreksi / hapus barang masuk menyesuaikan stok; ditolak bila stok tersebut sudah terpakai.
- Bila formulir dibuka terlalu lama hingga sesi berakhir (error 419), pengguna dikembalikan ke halaman sebelumnya dengan pesan dan isian tetap terisi.

### 3.3 Alur Barang Keluar

1. Guru / staf (pemohon) meminta, meminjam, atau meminta barang dipindahkan; atau tim sarpras meminta barang rusak berat dimusnahkan.
2. **Operator** mengisi form "Ajukan Barang Keluar": memilih jenis (Peminjaman / Pemindahan / Pemusnahan), barang, lokasi asal (form menampilkan stok per kondisi di lokasi itu), rincian kondisi, lalu mengunggah foto bukti. Status otomatis **Pending**; stok belum berubah.
3. **Manager** membuka menu Barang Keluar (tab Pending), lalu menekan **Verifikasi**. Halaman detail menampilkan foto, rincian kondisi yang diajukan, dan stok yang tersedia di lokasi asal.
4. Manager memeriksa barang. Bila rincian kondisi tidak sesuai, Manager mengubah angkanya di panel verifikasi.
5. **Setujui**: rincian dicek terhadap stok, lalu stok diperbarui sesuai jenis (Peminjaman / Pemusnahan: stok lokasi asal berkurang; Pemindahan: stok pindah ke lokasi tujuan), status menjadi Disetujui, Manager dan tanggal verifikasi dicatat — semuanya dalam satu transaksi database.
6. **Tolak**: alasan wajib diisi; status menjadi Ditolak; stok tidak berubah.
7. Untuk Peminjaman, saat barang kembali **Operator** membuka detail peminjaman lalu mengisi form Pengembalian (tanggal, lokasi penyimpanan, rincian kondisi saat kembali). Stok bertambah sesuai kondisi; status berubah menjadi Dikembalikan Sebagian atau Dikembalikan.

### 3.4 Batasan Sistem

- Kondisi barang dicatat sebagai jumlah per kondisi, bukan per unit (tidak ada nomor seri per unit).
- Perbaikan barang Rusak Ringan menjadi Baik belum memiliki menu khusus.
- Data sampel barang keluar belum memiliki foto karena dibuat sebelum foto diwajibkan.

## 4. Database

Database `gudang_sekolah` berisi **8 tabel**: `user`, `kategori`, `barang`, `lokasi`, `stok_barang`, `barang_masuk`, `barang_keluar`, `pengembalian` (ditambah tabel `migrations` otomatis dari Laravel). Sesi dan cache disimpan di file / cookie, bukan tabel, agar database hanya berisi tabel sesuai rancangan. ERD, desain tabel, relasi, kamus data, dan data sampel lengkap ada di dokumen **01 Dokumen Rancangan**.

Relasi:

- `user` 1 : N `barang_masuk` (Operator pencatat, kolom `id_user`)
- `user` 1 : N `barang_keluar` (Operator pengaju, kolom `id_user`)
- `user` 1 : N `barang_keluar` (Manager verifikator, kolom `id_verifikator`)
- `barang` 1 : N `barang_masuk` dan `barang` 1 : N `barang_keluar` (kolom `id_barang`)
- `kategori` 1 : N `barang` (kolom `id_kategori`)
- `barang` 1 : N `stok_barang` dan `lokasi` 1 : N `stok_barang` (stok per lokasi dan kondisi)
- `lokasi` 1 : N `barang_masuk` (`id_lokasi`), `barang_keluar` (`id_lokasi_asal`, `id_lokasi_tujuan`), `pengembalian` (`id_lokasi`)
- `barang_keluar` 1 : N `pengembalian` (kolom `id_keluar`); `user` 1 : N `pengembalian` (Operator pencatat)

Migration (urutan pembuatan struktur database):

| File | Isi |
|---|---|
| `2026_09_25_000001_create_user_table` | Tabel `user` |
| `2026_09_25_000002_create_kategori_table` | Tabel `kategori` |
| `2026_09_25_000003_create_barang_table` | Tabel `barang` + FK ke kategori |
| `2026_09_25_000004_create_barang_masuk_table` | Tabel `barang_masuk` + FK ke barang dan user |
| `2026_09_25_000005_create_barang_keluar_table` | Tabel `barang_keluar` + FK ke barang, user (pengaju), dan user (verifikator) |
| `2026_09_25_000006_enable_row_level_security` | Khusus PostgreSQL / Supabase: mengaktifkan Row Level Security agar tabel tidak terbaca lewat API publik Supabase |
| `2026_10_08_000001_tambah_role_manager_dan_foto` | Menambah role Manager dan kolom foto / gambar tanpa menghapus data lama |
| `2026_10_21_000001_tambah_lokasi_kondisi_peminjaman` | Membuat tabel `lokasi`, `stok_barang`, `pengembalian`; menambah jenis keluar, lokasi, dan rincian kondisi; memindahkan data lama (teks lokasi → data master lokasi, stok → stok per lokasi dan kondisi, barang keluar lama → Pemindahan) lalu menghapus kolom `lokasi` dan `status_barang` |

Gambar tidak disimpan di dalam database; kolom `foto` / `gambar` hanya berisi lokasi file. Database tetap kecil dan cepat.

## 5. Struktur Program (MVC)

Laravel memakai pola **Model – View – Controller**. Alur satu permintaan:

Browser → `routes/web.php` → Middleware (`auth`, `role`) → Form Request (validasi) → Controller → Model / Service → View (Blade) → Browser.

| Lokasi | Isi |
|---|---|
| `routes/web.php` | Daftar URL (48 route) dan pembagian hak akses per role |
| `app/Http/Middleware/CekRole.php` | Membatasi halaman berdasarkan role |
| `app/Http/Controllers/` | AuthController, DashboardController, ProfilController, UserController, KategoriController, LokasiController, BarangController, BarangMasukController, BarangKeluarController, PengembalianController |
| `app/Http/Requests/` | Aturan validasi: ProfilRequest, UserRequest, KategoriRequest, LokasiRequest, BarangRequest, BarangMasukRequest, BarangKeluarRequest, PengembalianRequest |
| `app/Models/` | Model Eloquent: User, Kategori, Lokasi, Barang, StokBarang, BarangMasuk, BarangKeluar, Pengembalian |
| `app/Models/Concerns/PunyaRincianKondisi.php` | Trait bersama untuk rincian kondisi (`rincian()`, `ringkasKondisi()`, `isiRincian()`) yang dipakai BarangMasuk, BarangKeluar, Pengembalian |
| `app/Services/StokService.php` | Satu-satunya tempat yang mengubah stok (per barang, lokasi, kondisi) |
| `app/Services/GambarService.php` | Satu-satunya tempat yang menyimpan, mengganti, menghapus, dan membuat alamat gambar |
| `app/Support/Suasana.php` | Penentu suasana pagi / siang / sore / malam: salam dashboard, animasi dan audio login |
| `app/Exceptions/StokTidakCukupException.php` | Error khusus saat stok tidak mencukupi |
| `app/Providers/AppServiceProvider.php` | Pagination berbahasa Indonesia, jumlah Pending untuk menu, paksa https di produksi |
| `bootstrap/app.php` | Alias middleware `role`, proxy tepercaya, penanganan error 419 |
| `config/filesystems.php` | Lokasi penyimpanan gambar (`UPLOAD_DISK`: `public` atau `s3`) |
| `database/migrations/`, `database/seeders/`, `database/factories/` | Struktur tabel, data sampel, data palsu untuk pengujian |
| `resources/views/` | Halaman Blade: layout, login, dashboard, profil, user, kategori, lokasi, barang, barang-masuk, barang-keluar |
| `resources/views/components/` | Komponen: `icon`, `badge`, `empty`, `error`, `avatar`, `unggah-gambar`, `tombol-tema`, `input-kondisi` (3 kotak jumlah per kondisi + total), `rincian-kondisi` (label berwarna per kondisi) |
| `resources/views/partials/` | `flash` (pesan notifikasi), `pagination`, dan `tema-awal` (skrip mode gelap) |
| `resources/css/app.css` | Gaya tampilan, animasi halaman login, dan warna mode gelap |
| `resources/js/` | `app.js` (menu HP, notifikasi, lihat password, pratinjau gambar), `tema.js` (mode gelap), `suasana.js` (salam, jam, dan audio login), `musik.js` (musik Bad Apple!!), `popup.js` (popup dan panel), `form-kondisi.js` (total kondisi otomatis dan form barang keluar sesuai jenis) |
| `public/images/siluet.png` | Gambar siluet halaman login (PNG transparan) |
| `public/audio/` | Suara suasana (`pagi.wav`, `siang.wav`, `sore.wav`, `malam.wav`) dan lagu `bad-apple.mp3` |
| `tests/Unit`, `tests/Feature` | 96 test PHPUnit |
| `vercel.json`, `api/index.php` | Konfigurasi hosting Vercel |

## 6. Desain Antarmuka

- **Konsisten**: tombol, kartu, input, dan tabel memakai kelas yang sama dari `resources/css/app.css`.
- **Tanpa internet**: font *Plus Jakarta Sans* dibundel lewat Vite; ikon berupa SVG di dalam kode (komponen `<x-icon>`).
- **Responsif**: di HP, menu samping menjadi menu geser; tabel dapat digeser ke samping.
- **Sesuai role**: menu, tombol, dan aksi cepat hanya tampil bila role berhak; jumlah permintaan Pending tampil di menu.
- **Multimedia**: foto profil di menu samping, topbar, dan daftar pengguna; gambar barang di daftar dan detail; foto bukti di daftar dan detail transaksi; pratinjau sebelum unggah.
- **Siap cetak**: tombol Cetak menyembunyikan menu, filter, dan tombol aksi; saat dicetak selalu memakai mode terang.
- **Mode terang / gelap**: seluruh warna memakai variabel CSS yang ditukar nilainya saat `<html class="dark">` (`resources/css/app.css` bagian MODE GELAP).
- **Tema monokrom**: warna utama hitam-putih ala video Bad Apple!!; sidebar dan banner mengikuti tema; warna status tetap berwarna.
- **Tanpa pindah halaman**: Turbo mengganti isi halaman tanpa muat ulang; tambah, ubah, dan detail tampil sebagai popup; profil di panel kanan.
- **Latar karakter**: karakter tersenyum bersayap tampil besar di tengah belakang halaman, hitam di mode terang dan putih di mode gelap, ditemani percikan cahaya yang naik lalu menghilang.
- **Kotak ikut tema**: kotak (kartu) mengikuti tema; di mode gelap diberi cahaya putih tipis di tepinya.
- **Hiasan karakter**: penyihir samar di banner dashboard dan karakter samar di latar sidebar; warnanya ikut tema.
- **Login siluet**: mode terang = siluet hitam di kiri dengan kartu di kanan; mode gelap = siluet putih di kanan dengan kartu di kiri. Ruang kosong diisi tulisan raksasa "GUDANG SEKOLAH" bergaris tipis dan apel kecil yang jatuh; cahaya apel berdenyut mengikuti waktu.

## 7. Penjelasan Kode Penting

### 7.1 Login — `AuthController::login()`

`Auth::attempt()` mencari user berdasarkan username lalu mencocokkan password dengan hash bcrypt. Bila cocok, sesi dibuat ulang (`session()->regenerate()`) untuk mencegah *session fixation*. Model `User` memakai cast `'password' => 'hashed'` sehingga password selalu tersimpan ter-hash.

### 7.2 Hak Akses — `CekRole::handle()`

Middleware menerima daftar role yang diizinkan, misalnya `role:Admin`, `role:Manager`, atau `role:Admin,Operator`. Bila role pengguna tidak termasuk, server mengembalikan **403 Forbidden**. Tampilan juga menyembunyikan tombol yang tidak berhak (`isAdmin()`, `isOperator()`, `isManager()`).

### 7.3 Kode Barang Otomatis — `Barang::generateKode()`

1. Ambil prefix dari nama kategori (`prefixKategori()` memakai array `PREFIX_KATEGORI`).
2. Ambil semua kode berawalan prefix tersebut (`pluck`).
3. Ubah bagian nomor menjadi angka (`map`) dan ambil nilai terbesar (`max`).
4. Tambah 1 dan format tiga digit: `sprintf('%s-%03d', ...)`, misalnya AO-008.

### 7.4 Stok per Lokasi dan Kondisi — `StokService`

1. Stok disimpan di tabel `stok_barang`: satu baris untuk setiap barang + lokasi + kondisi. Kolom `barang.stok` hanya total, dihitung ulang oleh `perbaruiTotal()` setiap kali stok berubah.
2. `tambah($barang, $idLokasi, $kondisi, $jumlah)` dan `kurangi(...)` mengubah satu baris stok. `kurangi()` melempar `StokTidakCukupException` bila stok kondisi itu di lokasi itu kurang, sehingga stok tidak pernah negatif.
3. `tambahRincian()` dan `kurangiRincian()` menerima array rincian, contoh `['Baik' => 5, 'Rusak Ringan' => 10, 'Rusak Berat' => 5]`. `kurangiRincian()` memeriksa semua kondisi dulu, baru mengurangi, sehingga tidak ada pengurangan setengah jalan.
4. Controller memanggilnya di dalam `DB::transaction()` agar data transaksi dan stok berhasil bersama atau batal bersama. `lockForUpdate()` mengunci baris stok agar dua proses bersamaan tidak mengacaukan stok.

### 7.5 Verifikasi — `BarangKeluarController::setujui()` dan `tolak()`

Hanya Manager yang dapat mengaksesnya. Di dalam transaksi, `kunciPermintaanPending()` mengunci baris permintaan dan memastikan statusnya masih Pending; bila dua Manager menyetujui bersamaan, yang kedua menunggu lalu mendapati status sudah berubah.

Pada `setujui()`, bila Manager mengubah rincian kondisi, rincian baru diperiksa dengan `BarangKeluarRequest::periksaRincian()` (aturan yang sama dengan saat pengajuan), lalu catatan "Rincian dikoreksi Manager dari (...) menjadi (...)" ditambahkan otomatis. `terapkanStok()` memperbarui stok sesuai jenis: Peminjaman dan Pemusnahan mengurangi stok lokasi asal; Pemindahan mengurangi stok lokasi asal dan menambah stok lokasi tujuan. `simpanVerifikasi()` mengisi status, `id_verifikator`, `tanggal_verifikasi`, dan catatan. Pada `tolak()`, catatan wajib diisi.

### 7.6 Pengembalian — `PengembalianController::store()`

1. `PengembalianRequest` memeriksa tanggal, lokasi, rincian kondisi saat kembali, dan memastikan total tidak melebihi `sisaPinjaman()`.
2. Di dalam transaksi, baris peminjaman dikunci lalu sisa pinjaman dicek ulang (mencegah dua pengembalian bersamaan melebihi jumlah pinjaman).
3. Data `pengembalian` disimpan, lalu `StokService::tambahRincian()` menambah stok ke lokasi yang dipilih sesuai kondisi saat kembali.
4. Status dihitung oleh `BarangKeluar::statusPinjam()` dari jumlah pinjaman dikurangi jumlah yang sudah kembali: Dipinjam, Dikembalikan Sebagian, atau Dikembalikan. `terlambat()` bernilai benar bila lewat `tanggal_kembali` dan belum kembali seluruhnya.

### 7.7 Validasi — Form Request

Contoh `BarangKeluarRequest`: `required`, `integer`, `min:1`, `exists:barang,id_barang`, `Rule::in(BarangKeluar::JENIS)`, `required_if:jenis_keluar,Peminjaman` (tanggal kembali), `different:id_lokasi_asal` (lokasi tujuan), aturan foto (`required`, `image`, `mimes:jpg,jpeg,png,webp`, `max:2048`), dan pengecekan rincian kondisi terhadap stok lokasi asal di method `after()` (memanggil `periksaRincian()`; Pemusnahan hanya boleh Rusak Berat). `ProfilRequest` memakai `current_password` untuk memastikan password lama benar. Pesan error tampil di bawah input lewat komponen `<x-error>`.

### 7.8 Unggah Gambar — `GambarService`

1. Form memakai `enctype="multipart/form-data"`; komponen `<x-unggah-gambar>` menampilkan pratinjau dengan JavaScript.
2. Form Request memeriksa jenis isi dan ukuran file (`GambarService::ATURAN`).
3. `simpan()` menyimpan file dengan nama acak ke folder sesuai jenisnya (`profil`, `barang`, `barang-masuk`, `barang-keluar`) dan mengembalikan lokasinya untuk disimpan di database.
4. Model menyediakan alamat gambar: `$user->foto_url`, `$barang->gambar_url`, `$transaksi->foto_url`.
5. `ganti()` menghapus file lama setelah file baru tersimpan. Pada barang masuk, bila transaksi database gagal, file baru dihapus kembali.
6. Lokasi penyimpanan diatur `UPLOAD_DISK`: `public` (lokal) atau `s3` (Supabase Storage), tanpa mengubah kode.
7. Bila penyimpanan gagal (kunci salah, bucket tidak ada, koneksi putus), `simpan()` melempar `GambarGagalDisimpanException`. `bootstrap/app.php` mengembalikan pengguna ke form dengan pesan jelas, dan penyebab aslinya dicatat ke log dalam satu baris pendek berawalan "Gambar gagal disimpan:" (di Vercel: menu Logs, cari kata itu).

### 7.9 Suasana Login dan Audio — `App\Support\Suasana`

1. `Suasana::DAFTAR` menyimpan data tiap waktu: jam mulai, salam, kalimat, dan file audio (`public/audio/*.wav`).
2. `Suasana::dariJam()` menentukan waktu: pagi 04.00-10.59, siang 11.00-14.59, sore 15.00-17.59, malam 18.00-03.59. Fungsi yang sama dipakai untuk salam di dashboard.
3. `AuthController::index()` mengirim data ke halaman login; Blade menuliskannya sebagai JSON (`@json`) agar dibaca `resources/js/suasana.js`.
4. JavaScript memperbarui jam setiap detik dan mengganti atribut `data-waktu`; CSS mengubah warna cahaya apel berdasarkan atribut tersebut.
5. Tombol "Putar suara" memutar elemen `<audio loop>` dengan volume naik perlahan. Suara dibuat sendiri secara sintetis sehingga bebas hak cipta.

### 7.10 Mode Terang / Gelap — `resources/js/tema.js`

1. `partials/tema-awal.blade.php` dijalankan paling awal di `<head>` agar halaman tidak berkedip putih.
2. `gantiTema()` menukar class `dark` di `<html>` dan menyimpan pilihan di `sessionStorage`. Bila belum memilih, tema mengikuti jam: malam (18.00-03.59) gelap, selain itu terang. Bila browser mendukung View Transitions API, tema baru muncul sebagai lingkaran yang membesar dari tombol.
3. Di CSS, `html.dark` menukar nilai variabel warna, sehingga sidebar, banner, popup, dan latar karakter ikut berubah warna.

4. Warna utama (`brand`) dibuat hitam dan abu-abu (`slate`) dibuat netral sehingga seluruh aplikasi bergaya monokrom.

### 7.11 Musik Bad Apple!! — `resources/js/musik.js`

1. File lagu `public/audio/bad-apple.mp3` diputar oleh elemen `<audio loop preload="none">` di `partials/pemutar-musik.blade.php`.
2. Tombol not musik memanggil `play()` / `pause()`; ikon berubah sesuai status.
3. Posisi lagu disimpan di `sessionStorage` saat meninggalkan halaman, sehingga di halaman berikutnya lagu dilanjutkan dari detik terakhir.
4. Memutar musik menjeda suara suasana di halaman login, dan sebaliknya.

### 7.12 Tanpa Pindah Halaman dan Popup — Turbo

1. **Turbo Drive** (`resources/js/app.js`) mengambil halaman tujuan di belakang layar lalu mengganti isi `<body>`; halaman tidak dimuat ulang, sehingga lebih cepat dan musik tidak terputus.
2. Tombol Tambah / Ubah / Detail diberi `data-turbo-frame="modal"`. Turbo mengirim header `Turbo-Frame: modal`; layout (`layouts/app.blade.php`) lalu hanya mengirim isi halaman di dalam `<turbo-frame id="modal">` yang ditampilkan dalam `<dialog>`.
3. Halaman yang boleh menjadi popup ditandai `@section('popup', true)`. Tanpa Turbo, halaman yang sama tetap tampil utuh, sehingga route, controller, dan test tidak berubah.
4. Form barang keluar memakai `resources/js/form-kondisi.js`: kotak lokasi tujuan atau tanggal kembali hanya muncul sesuai jenis, kotak Baik / Rusak Ringan terkunci untuk Pemusnahan, stok per kondisi di lokasi asal ditampilkan dan dipakai sebagai batas maksimum, dan total dihitung otomatis.
5. Validasi gagal → form tampil lagi di popup dengan pesan error. Simpan berhasil → popup ditutup dan halaman daftar tampil dengan pesan sukses (`turbo:frame-missing` di `resources/js/popup.js`).
6. Profil dibuka di panel kanan (`data-turbo-frame="laci"`); setelah disimpan kembali ke halaman asal.

### 7.13 Penanganan Error 419 — `bootstrap/app.php`

Error 419 terjadi bila token CSRF di formulir sudah tidak cocok dengan sesi, biasanya karena halaman dibiarkan terbuka lebih dari 120 menit. Pengguna dikembalikan ke halaman sebelumnya dengan pesan "Halaman sudah terlalu lama dibuka..." dan isian tetap terisi (kecuali password dan file).

## 8. Keamanan

| Ancaman / kebutuhan | Penerapan |
|---|---|
| Password bocor | Password di-hash bcrypt; tidak pernah disimpan sebagai teks asli |
| Akses tanpa hak | Middleware `auth` dan `CekRole` di server; error 403 |
| CSRF (form palsu) | Token `@csrf` di setiap form; error 419 ditangani dengan pesan jelas |
| XSS (script berbahaya) | Output Blade `{{ }}` di-escape; teks di JavaScript memakai `@js()` |
| SQL Injection | Query lewat Eloquent / Query Builder (parameter binding) |
| Unggah file berbahaya | Validasi isi file (`image`, `mimes`), maks. 2 MB, nama file acak |
| Data tidak konsisten | `DB::transaction()` + `lockForUpdate()` |
| Session fixation | `session()->regenerate()` setelah login |
| Data rahasia | Disimpan di `.env` (tidak di-commit ke Git) |
| Database online terbuka | Row Level Security di Supabase |

## 9. Debugging

### 9.1 Bug yang Ditemukan dan Perbaikannya

| No | Lokasi | Masalah | Akibat | Perbaikan |
|---|---|---|---|---|
| 1 | Model `Kategori` | Salah ketik `protected $tabl` | Laravel mencari tabel `kategoris` (table not found) | Diganti `protected $table = 'kategori'` |
| 2 | Model `BarangMasuk` | `$fillable` berisi `jumblah`, tanpa `tanggal` | Kolom jumlah dan tanggal tidak tersimpan | `$fillable` disamakan dengan nama kolom |
| 3 | Semua migration | `down()` menghapus nama tabel yang salah | `migrate:rollback` gagal | Nama tabel di `down()` diperbaiki |
| 4 | Migration barang_masuk | Tidak ada foreign key | Data bisa merujuk barang / user yang tidak ada | Ditambah foreign key |
| 5 | `KategoriController::store` | Hanya validasi, tidak menyimpan | Kategori tidak pernah tersimpan | Ditambah `Kategori::create()` dan redirect |
| 6 | View `kategori.index` | File belum ada | Error "View not found" | Semua view dibuat |
| 7 | Migration barang | FK kategori `cascade` | Hapus kategori ikut menghapus barangnya | Diganti `restrictOnDelete()` + pengecekan |
| 8 | Layout | Link menu berisi `#` | Menu tidak berfungsi | Diganti `route(...)` |
| 9 | Log awal | "Unmatched '}'", "Target class [DashboardController] does not exist", "Route [dashboard] not defined" | Aplikasi error saat dibuka | Sintaks route diperbaiki, route diberi nama |
| 10 | `UserFactory` | Username acak kadang berisi titik | Test kadang gagal kadang lulus (*flaky*) | Format username diganti `user_####??` |
| 11 | `config/app.php` | Zona waktu `UTC` | Verifikasi dini hari tercatat tanggal kemarin | Diganti `Asia/Jakarta`, bahasa tanggal Indonesia |
| 12 | `BarangKeluarController::setujui()` | Status Pending dicek di luar transaksi (*race condition*) | Dua persetujuan bersamaan mengurangi stok dua kali | Baris dikunci dan dicek ulang di dalam transaksi |
| 13 | Migration role Manager | `->change()` pada enum menghasilkan SQL tidak valid di PostgreSQL | Database online gagal diperbarui | Constraint role dibuat ulang manual khusus PostgreSQL |
| 14 | Formulir lama terbuka | Token CSRF kedaluwarsa | Halaman "419 Page Expired" dan isian hilang | Error 419 ditangani: kembali dengan pesan, isian tetap |
| 15 | `GambarService::simpan()` di website online | Bila Supabase Storage menolak, `putFile()` mengembalikan `false` tanpa pesan, lalu `false` ikut disimpan ke kolom foto | Unggah foto profil / gambar barang menampilkan "500 Server Error" | Kegagalan diubah menjadi `GambarGagalDisimpanException`: penyebab asli dicatat di log, pengguna kembali ke form dengan pesan jelas, data tidak tersimpan setengah jadi |
| 16 | Rancangan barang keluar (masukan pembina) | Semua barang keluar dianggap hilang dari stok; satu transaksi hanya satu kondisi; lokasi berupa teks bebas | Barang pinjaman / pindah ruangan tidak terlacak; kondisi campuran tidak dapat dicatat | Ditambah jenis keluar, tabel `pengembalian`, rincian kondisi per transaksi, data master `lokasi`, dan `stok_barang`; migration memindahkan data lama tanpa kehilangan data |

### 9.2 Teknik Debugging

- Membaca pesan error dan *stack trace* (`APP_DEBUG=true` hanya di lokal).
- Membaca log `storage/logs/laravel.log` (lokal) dan menu Logs di Vercel (online).
- `dd()` / `dump()` untuk memeriksa isi variabel; `php artisan tinker` untuk mencoba query.
- `php artisan route:list` untuk memeriksa route.
- Menjalankan `php artisan test` setelah setiap perubahan, dan berulang kali untuk mendeteksi *flaky test*.
- Menguji migration dan test di PostgreSQL (sama dengan server online) sebelum deploy.
- Memeriksa respons server langsung dengan `curl` (misalnya header cookie dan status 302 / 419).

## 10. Pengujian Unit Program

Pengujian memakai **PHPUnit** dengan database SQLite di memori (`phpunit.xml`), sehingga data asli tidak terganggu. Setiap test memakai `RefreshDatabase`. File unggahan disimpan di penyimpanan palsu (`Storage::fake()`), dan gambar uji dibuat dengan `UploadedFile::fake()->image()`.

Perintah: `php artisan test` — hasil terakhir **96 passed (371 assertions)**.

| File | Jenis | Jumlah | Yang diuji |
|---|---|---|---|
| `tests/Unit/StokServiceTest.php` | Unit | 8 | Tambah / kurangi stok per lokasi dan kondisi, stok habis, stok tidak cukup, stok lokasi lain tidak terpakai, rincian tidak dikurangi setengah jalan, jumlah nol / kondisi tidak dikenal, cek stok cukup |
| `tests/Unit/KodeBarangTest.php` | Unit | 5 | Prefix dan nomor kode barang otomatis |
| `tests/Unit/SuasanaTest.php` | Unit | 3 | Suasana dan salam sesuai jam, file audio tersedia |
| `tests/Feature/AuthTest.php` | Fitur | 7 | Login berhasil / gagal, password ter-hash, tamu diarahkan ke login, logout, suasana login sesuai jam |
| `tests/Feature/HakAksesTest.php` | Fitur | 6 | Pembatasan halaman untuk Admin, Operator, Manager |
| `tests/Feature/UserTest.php` | Fitur | 8 | Kelola pengguna, username unik, role valid, Admin dapat mengubah nama / username sendiri tetapi tidak dapat mengubah role / menghapus akun sendiri |
| `tests/Feature/ProfilTest.php` | Fitur | 5 | Ubah profil, unggah / ganti / hapus foto, ganti password |
| `tests/Feature/KategoriBarangTest.php` | Fitur | 9 | CRUD kategori & barang, stok awal per lokasi dan kondisi, validasi, pencarian, gambar barang |
| `tests/Feature/LokasiTest.php` | Fitur | 5 | Tambah / ubah lokasi oleh Admin, nama unik dan jenis valid, lokasi berisi barang tidak dapat dihapus, filter barang per lokasi, hanya Admin yang mengelola |
| `tests/Feature/TransaksiBarangTest.php` | Fitur | 27 | Barang masuk dengan rincian kondisi dan lokasi, peminjaman, pemindahan, pemusnahan (hanya Rusak Berat), koreksi rincian oleh Manager, pengembalian (bertahap, kondisi berubah, tidak melebihi sisa), verifikasi, foto bukti |
| `tests/Feature/SesiKedaluwarsaTest.php` | Fitur | 3 | Penanganan error 419 |
| `tests/Feature/GambarGagalTest.php` | Fitur | 5 | Penyimpanan gambar gagal (profil, barang, barang keluar): kembali ke form dengan pesan, bukan error 500, data tidak tersimpan; penyebab dicatat dalam satu baris log pendek, pesan di layar tanpa detail teknis |
| `tests/Feature/PopupTest.php` | Fitur | 5 | Halaman dikirim sebagai isi popup / panel kanan, halaman daftar tetap utuh, simpan profil kembali ke halaman asal dan menolak alamat situs lain |

Contoh test case:

| Skenario | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| Manager menyetujui | Stok 10, Operator ajukan 4, Manager setujui | Disetujui, verifikator = Manager, stok = 6 | Lulus |
| Kondisi campuran | Catat barang masuk dengan rincian Baik dan Rusak Ringan ke satu lokasi | Stok tiap kondisi di lokasi itu bertambah sesuai rincian | Lulus |
| Manager mengoreksi | Manager mengubah rincian kondisi lalu menyetujui | Stok dikurangi sesuai koreksi, koreksi tercatat di catatan | Lulus |
| Pemindahan | Pindahkan barang dari gudang ke ruangan, Manager setujui | Stok gudang berkurang, stok ruangan bertambah, total tetap | Lulus |
| Pemusnahan barang Baik | Ajukan pemusnahan 2 Baik | Error validasi, hanya Rusak Berat | Lulus |
| Pengembalian | Kembalikan sebagian pinjaman dengan kondisi berbeda | Dikembalikan Sebagian, stok bertambah sesuai kondisi saat kembali | Lulus |
| Pengembalian berlebih | Kembalikan melebihi sisa pinjaman | Error validasi, stok tetap | Lulus |
| Foto wajib | Ajukan barang keluar tanpa foto | Error validasi foto, data tidak tersimpan | Lulus |
| File tidak sah | Unggah PDF / gambar 3 MB | Error validasi, data tidak tersimpan | Lulus |
| Tolak tanpa alasan | Manager menolak tanpa catatan | Error validasi, status tetap Pending | Lulus |
| Stok tidak cukup | Stok 3, permintaan 100, disetujui | Pesan gagal, status Pending, stok tetap 3 | Lulus |
| Hak akses | Admin / Operator menekan Setujui | 403 Forbidden | Lulus |
| Manager hanya melihat | Manager membuka form tambah barang | 403 Forbidden | Lulus |
| Token kedaluwarsa | Kirim form setelah sesi berakhir | Kembali ke halaman sebelumnya dengan pesan, isian tetap | Lulus |

## 11. Pemetaan Unit Kompetensi

| No | Kode Unit | Judul Unit | Bukti pada Program |
|---|---|---|---|
| 1 | J.620100.004.02 | Menggunakan Struktur Data | Array `KONDISI`, `JENIS`, `VERIFIKASI`, `ROLES`; array asosiatif `PREFIX_KATEGORI`, `KOLOM_KONDISI`, rincian kondisi; array bersarang `INFO_ROLE`, `INFO_JENIS`, peta stok `[barang][lokasi][kondisi]`; Collection (`pluck`, `map`, `max`, `concat`, `sortByDesc`); objek model dan relasi |
| 2 | J.620100.009.01 | Menggunakan Spesifikasi Program | Hak akses, aturan bisnis, dan alur (Bagian 3) diterapkan di route, middleware, Form Request; rancangan database (dokumen 01) diterapkan di migration |
| 3 | J.620100.010.01 | Menerapkan Perintah Eksekusi Bahasa Pemrograman Berbasis Teks, Grafik, dan Multimedia | Perintah teks: `php artisan ...`, `npm run build`, `composer`, `git`; antarmuka grafis Blade + Tailwind dan grafik dashboard; multimedia: unggah, pratinjau, tampil, ganti, hapus gambar |
| 4 | J.620100.016.01 | Menulis Kode dengan Prinsip sesuai Guidelines dan Best Practices | PSR-12 (Laravel Pint), MVC, Form Request, service class, pemisahan tugas, keamanan (Bagian 8), transaksi database |
| 5 | J.620100.017.02 | Mengimplementasikan Pemrograman Terstruktur | Percabangan `if/elseif/else` dan `match`, perulangan `foreach` / `@forelse`, fungsi dengan parameter dan nilai kembali, prosedur `void`, `try...catch` |
| 6 | J.620100.023.02 | Membuat Dokumen Kode Program | PHPDoc di class dan method, komentar Blade, dokumen 01–05 |
| 7 | J.620100.025.02 | Melakukan Debugging | 16 bug beserta perbaikannya dan teknik debugging (Bagian 9) |
| 8 | J.620100.033.02 | Melaksanakan Pengujian Unit Program | 96 test PHPUnit unit dan fitur (Bagian 10) |

## 12. Skenario Demo

1. Login **operator_siti**. Tunjukkan menu Kategori dan Pengguna tidak ada.
2. Klik **foto profil** di kanan atas: panel profil muncul dari kanan; unggah foto; foto tampil di menu samping.
3. **Data Barang**: tambah barang di popup beserta gambar; kode otomatis AT-002; pratinjau gambar.
4. **Barang Masuk**: catat 10 unit ke Gudang Utama = 8 Baik + 2 Rusak Ringan (boleh dengan foto nota); stok per kondisi bertambah.
5. **Barang Keluar**: ajukan Peminjaman tanpa foto (ditolak), lalu dengan foto (Pending, stok tetap). Tunjukkan form berubah saat jenis diganti Pemindahan / Pemusnahan.
6. Login **manager_marco**: tidak ada tombol tambah barang; buka permintaan, lihat foto dan stok lokasi asal, koreksi rincian kondisi, coba Tolak tanpa alasan (ditolak), lalu Setujui (stok berkurang).
7. Login **operator_siti** lagi: tab **Sedang Dipinjam** → buka peminjaman bola basket → catat pengembalian 1 bola Rusak Ringan → status Dikembalikan.
8. Login **admin_ohim**: menu Pengguna menampilkan tiga role; role akun sendiri terkunci; menu **Lokasi** menampilkan jumlah stok per lokasi; koreksi barang masuk.
9. Detail barang Kursi Siswa: tabel **Stok per Lokasi** (Gudang Utama, Gudang 2) per kondisi. Tombol **Cetak** pada daftar barang.
10. Terminal: `php artisan test` → 96 test lulus.

Mengembalikan data ke kondisi awal sebelum demo: `php artisan migrate:fresh --seed`.
