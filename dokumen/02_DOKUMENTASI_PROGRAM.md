# Dokumentasi Program Inventaris Gudang Sekolah

| Keterangan | Isi |
|---|---|
| Nama Peserta | Renaissance Ricarda Sugiarto Putra |
| Skema | Pemrogram Junior (Junior Coder) |
| Aplikasi | Inventaris Gudang Sekolah (berbasis web) |
| Framework | Laravel 12 (PHP 8.2) |
| Database | MySQL / MariaDB (XAMPP) untuk lokal; PostgreSQL (Supabase) untuk versi online |
| Penyimpanan gambar | Folder `storage/app/public` (lokal); Supabase Storage (online) |
| Tampilan | Blade + Tailwind CSS 4 (dibuild dengan Vite), responsif untuk komputer dan HP |
| Pengujian | PHPUnit 11 — 78 test, 271 assertion, semua lulus |
| Dokumen terkait | 01 Dokumen Rancangan, 03 Persiapan Asesor, 04 Daftar Akun, 05 Tutorial Deploy |

<!-- daftar-isi -->

<!-- halaman-baru -->

## 1. Deskripsi Program

Inventaris Gudang Sekolah adalah aplikasi web untuk mencatat barang di gudang sekolah, mencatat barang yang masuk, dan mengelola permintaan barang keluar melalui persetujuan. Setiap barang, transaksi, dan pengguna dapat dilengkapi gambar (multimedia).

Masalah yang diselesaikan:

- Pencatatan barang yang manual membuat stok tidak jelas dan sulit ditelusuri.
- Barang dapat keluar tanpa persetujuan dan tanpa bukti.
- Tidak ada riwayat siapa yang mencatat dan siapa yang menyetujui.

Aplikasi menerapkan **pemisahan tugas (separation of duties)** dengan tiga role:

| Role | Peran | Tugas utama |
|---|---|---|
| Admin | Pengelola sistem | Mengelola pengguna, kategori, dan data barang; mengoreksi data barang masuk |
| Operator | Petugas gudang | Mendaftarkan barang, mencatat barang masuk, mengajukan barang keluar (dengan foto bukti) |
| Manager | Pengawas | Menyetujui / menolak barang keluar dan melihat seluruh data; tidak dapat menambah atau mengeluarkan barang |

Aturan utama: **stok bertambah saat barang masuk dicatat**, dan **stok baru berkurang setelah Manager menyetujui** permintaan barang keluar. Setiap verifikasi mencatat Manager yang memverifikasi, tanggal, dan catatannya.

### 1.1 Daftar Fitur

| No | Fitur | Keterangan |
|---|---|---|
| 1 | Login & logout | Login memakai username dan password (password tersimpan ter-hash); halaman login bergaya siluet monokrom dengan animasi, jam berjalan, salam dan suara suasana (audio) sesuai waktu pagi/siang/sore/malam |
| 2 | Dashboard | Sapaan sesuai jam, kartu statistik, grafik stok per kategori, status permintaan, stok menipis, aktivitas terbaru, aksi cepat per role |
| 3 | Profil saya | Klik foto profil → panel kanan untuk ubah nama, foto profil, dan password (wajib password lama) |
| 4 | Kelola pengguna | Tambah, ubah, hapus akun beserta role-nya (Admin) |
| 5 | Kategori | Tambah, ubah, hapus kategori; prefix kode barang otomatis |
| 6 | Data barang | Daftar, cari, filter kategori, detail riwayat, tambah dengan kode otomatis, gambar barang |
| 7 | Barang masuk | Catat penerimaan (stok bertambah), foto bukti opsional, filter tanggal, koreksi oleh Admin |
| 8 | Barang keluar | Ajukan permintaan dengan foto bukti wajib (Pending), verifikasi Setujui/Tolak oleh Manager, tab filter status |
| 9 | Cetak | Daftar barang, barang masuk, dan barang keluar dapat dicetak |
| 10 | Tema monokrom & mode terang / gelap | Tampilan hitam-putih ala video Bad Apple!!; tombol bulan/matahari di topbar dan login; tema baru muncul dengan efek lingkaran membesar; tema awal mengikuti jam (malam = gelap) |
| 11 | Musik Bad Apple!! | Tombol not musik di topbar dan login untuk memutar / menjeda lagu Bad Apple!! (MP3), posisi lagu berlanjut saat pindah halaman |
| 12 | Tanpa pindah halaman | Menu dibuka tanpa memuat ulang halaman (Turbo); form tambah, ubah, dan detail tampil sebagai popup |
| 13 | Sidebar bisa ditutup | Tombol burger menutup / membuka sidebar; posisinya diingat |

## 2. Cara Menjalankan Program

Prasyarat: XAMPP (MySQL aktif), PHP 8.2 dengan ekstensi `gd` dan `fileinfo`, Composer, Node.js.

1. Aktifkan **MySQL** di XAMPP Control Panel.
2. Buat database lewat phpMyAdmin: `CREATE DATABASE gudang_sekolah;`
3. Salin konfigurasi: `copy .env.example .env` lalu `php artisan key:generate` (lewati bila `.env` sudah ada).
4. Pasang dependensi (bila folder `vendor` / `node_modules` belum ada): `composer install` dan `npm install`.
5. Buat tabel dan isi data sampel: `php artisan migrate:fresh --seed`
6. Hubungkan folder gambar ke folder publik (cukup sekali): `php artisan storage:link`
7. Build tampilan: `npm run build`
8. Jalankan server: `php artisan serve`, lalu buka `http://localhost:8000`
9. Jalankan pengujian: `php artisan test`

Akun login ada di dokumen **04 Daftar Akun**. Cara mengunggah ke GitHub dan meng-online-kan aplikasi ada di dokumen **05 Tutorial Deploy**.

## 3. Spesifikasi Program

### 3.1 Hak Akses per Role

| No | Fitur | Admin | Operator | Manager |
|---|---|---|---|---|
| 1 | Login, logout, ubah profil sendiri | Ya | Ya | Ya |
| 2 | Dashboard | Ya | Ya | Ya |
| 3 | Kelola pengguna | Ya | Tidak | Tidak |
| 4 | Kelola kategori | Ya | Tidak | Tidak |
| 5 | Lihat data barang dan detail riwayatnya | Ya | Ya | Ya |
| 6 | Tambah barang baru + gambar | Ya | Ya | Tidak |
| 7 | Ubah / hapus barang | Ya | Tidak | Tidak |
| 8 | Catat barang masuk + foto opsional | Tidak | Ya | Tidak |
| 9 | Koreksi (ubah / hapus) barang masuk | Ya | Tidak | Tidak |
| 10 | Ajukan barang keluar + foto wajib | Tidak | Ya | Tidak |
| 11 | Verifikasi barang keluar (Setujui / Tolak) | Tidak | Tidak | Ya |
| 12 | Lihat barang masuk / keluar beserta foto, cetak | Ya | Ya | Ya |

Alasan pembagian:

- Tiga peran tanpa tumpang tindih: tidak ada orang yang dapat menyetujui permintaannya sendiri.
- Operator hanya **menambah** data; perubahan dan penghapusan dilakukan Admin.
- Manager hanya **melihat** dan memberi persetujuan.
- Operator boleh mendaftarkan barang baru karena barang harus terdaftar sebelum dicatat sebagai barang masuk.

### 3.2 Aturan Bisnis dan Validasi

- Kode barang dibuat otomatis berpola `PREFIX-NOMOR` (AT, FR, KN, AO, EK); nomor melanjutkan nomor terbesar pada prefix yang sama.
- Stok awal hanya diisi saat barang dibuat; selanjutnya stok hanya berubah lewat barang masuk dan barang keluar.
- Kondisi barang hanya: Baik, Rusak Ringan, Rusak Berat. Verifikasi hanya: Pending, Disetujui, Ditolak.
- Pengajuan barang keluar wajib mengisi pemohon dan foto bukti; jumlah tidak boleh melebihi stok, dan dicek ulang saat disetujui.
- Penolakan wajib disertai alasan. Permintaan yang sudah diverifikasi tidak dapat diverifikasi ulang.
- Pengajuan barang keluar tidak dapat diubah / dihapus (jejak audit); pengajuan keliru cukup ditolak Manager.
- Gambar hanya JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB. File lama dihapus saat gambar diganti atau datanya dihapus.
- Kategori yang masih dipakai, barang yang punya transaksi, dan pengguna yang punya transaksi tidak dapat dihapus.
- Admin tidak dapat menghapus akunnya sendiri atau mengubah role-nya sendiri (dikunci di form dan ditolak di server).
- Ganti password di Profil wajib memasukkan password lama yang benar. Username dan role hanya diubah Admin.
- Koreksi / hapus barang masuk menyesuaikan stok; ditolak bila stok tersebut sudah terpakai.
- Bila formulir dibuka terlalu lama hingga sesi berakhir (error 419), pengguna dikembalikan ke halaman sebelumnya dengan pesan dan isian tetap terisi.

### 3.3 Alur Barang Keluar

1. Guru / staf (pemohon) meminta barang kepada petugas gudang.
2. **Operator** mengisi form "Ajukan Barang Keluar" dan mengunggah foto bukti. Status otomatis **Pending**; stok belum berubah.
3. **Manager** membuka menu Barang Keluar (tab Pending), lalu menekan **Verifikasi** untuk membuka detail beserta fotonya.
4. **Setujui**: stok dicek, stok dikurangi, status menjadi Disetujui, Manager dan tanggal verifikasi dicatat — semuanya dalam satu transaksi database.
5. **Tolak**: alasan wajib diisi; status menjadi Ditolak; stok tidak berubah.
6. Operator dapat melihat hasil verifikasi dan alasannya di halaman detail.

### 3.4 Batasan Sistem

- Kondisi barang dicatat per jenis barang, bukan per unit.
- Barang keluar dianggap keluar dari stok; fitur peminjaman dan pengembalian belum tersedia.
- Data sampel barang keluar belum memiliki foto karena dibuat sebelum foto diwajibkan.

## 4. Database

Database `gudang_sekolah` berisi **5 tabel**: `user`, `kategori`, `barang`, `barang_masuk`, `barang_keluar` (ditambah tabel `migrations` otomatis dari Laravel). Sesi dan cache disimpan di file / cookie, bukan tabel, agar database tetap 5 tabel sesuai rancangan. ERD, desain tabel, relasi, kamus data, dan data sampel lengkap ada di dokumen **01 Dokumen Rancangan**.

Relasi:

- `user` 1 : N `barang_masuk` (Operator pencatat, kolom `id_user`)
- `user` 1 : N `barang_keluar` (Operator pengaju, kolom `id_user`)
- `user` 1 : N `barang_keluar` (Manager verifikator, kolom `id_verifikator`)
- `barang` 1 : N `barang_masuk` dan `barang` 1 : N `barang_keluar` (kolom `id_barang`)
- `kategori` 1 : N `barang` (kolom `id_kategori`)

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

Gambar tidak disimpan di dalam database; kolom `foto` / `gambar` hanya berisi lokasi file. Database tetap kecil dan cepat.

## 5. Struktur Program (MVC)

Laravel memakai pola **Model – View – Controller**. Alur satu permintaan:

Browser → `routes/web.php` → Middleware (`auth`, `role`) → Form Request (validasi) → Controller → Model / Service → View (Blade) → Browser.

| Lokasi | Isi |
|---|---|
| `routes/web.php` | Daftar URL (38 route) dan pembagian hak akses per role |
| `app/Http/Middleware/CekRole.php` | Membatasi halaman berdasarkan role |
| `app/Http/Controllers/` | AuthController, DashboardController, ProfilController, UserController, KategoriController, BarangController, BarangMasukController, BarangKeluarController |
| `app/Http/Requests/` | Aturan validasi: ProfilRequest, UserRequest, KategoriRequest, BarangRequest, BarangMasukRequest, BarangKeluarRequest |
| `app/Models/` | Model Eloquent: User, Kategori, Barang, BarangMasuk, BarangKeluar |
| `app/Services/StokService.php` | Satu-satunya tempat yang mengubah stok |
| `app/Services/GambarService.php` | Satu-satunya tempat yang menyimpan, mengganti, menghapus, dan membuat alamat gambar |
| `app/Support/Suasana.php` | Penentu suasana pagi / siang / sore / malam: salam dashboard, animasi dan audio login |
| `app/Exceptions/StokTidakCukupException.php` | Error khusus saat stok tidak mencukupi |
| `app/Providers/AppServiceProvider.php` | Pagination berbahasa Indonesia, jumlah Pending untuk menu, paksa https di produksi |
| `bootstrap/app.php` | Alias middleware `role`, proxy tepercaya, penanganan error 419 |
| `config/filesystems.php` | Lokasi penyimpanan gambar (`UPLOAD_DISK`: `public` atau `s3`) |
| `database/migrations/`, `database/seeders/`, `database/factories/` | Struktur tabel, data sampel, data palsu untuk pengujian |
| `resources/views/` | Halaman Blade: layout, login, dashboard, profil, user, kategori, barang, barang-masuk, barang-keluar |
| `resources/views/components/` | Komponen: `icon`, `badge`, `empty`, `error`, `avatar`, `unggah-gambar`, `tombol-tema` |
| `resources/views/partials/` | `flash` (pesan notifikasi), `pagination`, dan `tema-awal` (skrip mode gelap) |
| `resources/css/app.css` | Gaya tampilan, animasi halaman login, dan warna mode gelap |
| `resources/js/` | `app.js` (menu HP, notifikasi, lihat password, pratinjau gambar), `tema.js` (mode gelap), `suasana.js` (salam, jam, dan audio login), `musik.js` (musik Bad Apple!!) |
| `public/images/siluet.png` | Gambar siluet halaman login (PNG transparan) |
| `public/audio/` | Suara suasana (`pagi.wav`, `siang.wav`, `sore.wav`, `malam.wav`) dan lagu `bad-apple.mp3` |
| `tests/Unit`, `tests/Feature` | 78 test PHPUnit |
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

### 7.4 Stok — `StokService`

Perubahan stok terpusat di `tambah()` dan `kurangi()`. `kurangi()` melempar `StokTidakCukupException` bila stok kurang, sehingga stok tidak pernah negatif. Controller memanggilnya di dalam `DB::transaction()` agar data transaksi dan stok berhasil bersama atau batal bersama. `lockForUpdate()` mengunci baris barang agar dua proses bersamaan tidak mengacaukan stok.

### 7.5 Verifikasi — `BarangKeluarController::setujui()` dan `tolak()`

Hanya Manager yang dapat mengaksesnya. Di dalam transaksi, `kunciPermintaanPending()` mengunci baris permintaan dan memastikan statusnya masih Pending; bila dua Manager menyetujui bersamaan, yang kedua menunggu lalu mendapati status sudah berubah. `simpanVerifikasi()` mengisi status, `id_verifikator`, `tanggal_verifikasi`, dan catatan. Pada `tolak()`, catatan wajib diisi.

### 7.6 Validasi — Form Request

Contoh `BarangKeluarRequest`: `required`, `integer`, `min:1`, `exists:barang,id_barang`, `Rule::in(Barang::STATUS_BARANG)`, aturan foto (`required`, `image`, `mimes:jpg,jpeg,png,webp`, `max:2048`), dan pengecekan stok di method `after()`. `ProfilRequest` memakai `current_password` untuk memastikan password lama benar. Pesan error tampil di bawah input lewat komponen `<x-error>`.

### 7.7 Unggah Gambar — `GambarService`

1. Form memakai `enctype="multipart/form-data"`; komponen `<x-unggah-gambar>` menampilkan pratinjau dengan JavaScript.
2. Form Request memeriksa jenis isi dan ukuran file (`GambarService::ATURAN`).
3. `simpan()` menyimpan file dengan nama acak ke folder sesuai jenisnya (`profil`, `barang`, `barang-masuk`, `barang-keluar`) dan mengembalikan lokasinya untuk disimpan di database.
4. Model menyediakan alamat gambar: `$user->foto_url`, `$barang->gambar_url`, `$transaksi->foto_url`.
5. `ganti()` menghapus file lama setelah file baru tersimpan. Pada barang masuk, bila transaksi database gagal, file baru dihapus kembali.
6. Lokasi penyimpanan diatur `UPLOAD_DISK`: `public` (lokal) atau `s3` (Supabase Storage), tanpa mengubah kode.
7. Bila penyimpanan gagal (kunci salah, bucket tidak ada, koneksi putus), `simpan()` melempar `GambarGagalDisimpanException`. `bootstrap/app.php` mengembalikan pengguna ke form dengan pesan jelas, dan penyebab aslinya dicatat ke log (di Vercel: menu Logs).

### 7.8 Suasana Login dan Audio — `App\Support\Suasana`

1. `Suasana::DAFTAR` menyimpan data tiap waktu: jam mulai, salam, kalimat, dan file audio (`public/audio/*.wav`).
2. `Suasana::dariJam()` menentukan waktu: pagi 04.00-10.59, siang 11.00-14.59, sore 15.00-17.59, malam 18.00-03.59. Fungsi yang sama dipakai untuk salam di dashboard.
3. `AuthController::index()` mengirim data ke halaman login; Blade menuliskannya sebagai JSON (`@json`) agar dibaca `resources/js/suasana.js`.
4. JavaScript memperbarui jam setiap detik dan mengganti atribut `data-waktu`; CSS mengubah warna cahaya apel berdasarkan atribut tersebut.
5. Tombol "Putar suara" memutar elemen `<audio loop>` dengan volume naik perlahan. Suara dibuat sendiri secara sintetis sehingga bebas hak cipta.

### 7.9 Mode Terang / Gelap — `resources/js/tema.js`

1. `partials/tema-awal.blade.php` dijalankan paling awal di `<head>` agar halaman tidak berkedip putih.
2. `gantiTema()` menukar class `dark` di `<html>` dan menyimpan pilihan di `sessionStorage`. Bila belum memilih, tema mengikuti jam: malam (18.00-03.59) gelap, selain itu terang. Bila browser mendukung View Transitions API, tema baru muncul sebagai lingkaran yang membesar dari tombol.
3. Di CSS, `html.dark` menukar nilai variabel warna, sehingga sidebar, banner, popup, dan latar karakter ikut berubah warna.

4. Warna utama (`brand`) dibuat hitam dan abu-abu (`slate`) dibuat netral sehingga seluruh aplikasi bergaya monokrom.

### 7.10 Musik Bad Apple!! — `resources/js/musik.js`

1. File lagu `public/audio/bad-apple.mp3` diputar oleh elemen `<audio loop preload="none">` di `partials/pemutar-musik.blade.php`.
2. Tombol not musik memanggil `play()` / `pause()`; ikon berubah sesuai status.
3. Posisi lagu disimpan di `sessionStorage` saat meninggalkan halaman, sehingga di halaman berikutnya lagu dilanjutkan dari detik terakhir.
4. Memutar musik menjeda suara suasana di halaman login, dan sebaliknya.

### 7.11 Tanpa Pindah Halaman dan Popup — Turbo

1. **Turbo Drive** (`resources/js/app.js`) mengambil halaman tujuan di belakang layar lalu mengganti isi `<body>`; halaman tidak dimuat ulang, sehingga lebih cepat dan musik tidak terputus.
2. Tombol Tambah / Ubah / Detail diberi `data-turbo-frame="modal"`. Turbo mengirim header `Turbo-Frame: modal`; layout (`layouts/app.blade.php`) lalu hanya mengirim isi halaman di dalam `<turbo-frame id="modal">` yang ditampilkan dalam `<dialog>`.
3. Halaman yang boleh menjadi popup ditandai `@section('popup', true)`. Tanpa Turbo, halaman yang sama tetap tampil utuh, sehingga route, controller, dan test tidak berubah.
4. Validasi gagal → form tampil lagi di popup dengan pesan error. Simpan berhasil → popup ditutup dan halaman daftar tampil dengan pesan sukses (`turbo:frame-missing` di `resources/js/popup.js`).
5. Profil dibuka di panel kanan (`data-turbo-frame="laci"`); setelah disimpan kembali ke halaman asal.

### 7.12 Penanganan Error 419 — `bootstrap/app.php`

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

Perintah: `php artisan test` — hasil terakhir **78 passed (271 assertions)**.

| File | Jenis | Jumlah | Yang diuji |
|---|---|---|---|
| `tests/Unit/StokServiceTest.php` | Unit | 7 | Tambah / kurangi stok, stok habis, stok tidak cukup, jumlah nol |
| `tests/Unit/KodeBarangTest.php` | Unit | 5 | Prefix dan nomor kode barang otomatis |
| `tests/Unit/SuasanaTest.php` | Unit | 3 | Suasana dan salam sesuai jam, file audio tersedia |
| `tests/Feature/AuthTest.php` | Fitur | 7 | Login berhasil / gagal, password ter-hash, tamu diarahkan ke login, logout, suasana login sesuai jam |
| `tests/Feature/HakAksesTest.php` | Fitur | 6 | Pembatasan halaman untuk Admin, Operator, Manager |
| `tests/Feature/UserTest.php` | Fitur | 8 | Kelola pengguna, username unik, role valid, Admin dapat mengubah nama / username sendiri tetapi tidak dapat mengubah role / menghapus akun sendiri |
| `tests/Feature/ProfilTest.php` | Fitur | 5 | Ubah profil, unggah / ganti / hapus foto, ganti password |
| `tests/Feature/KategoriBarangTest.php` | Fitur | 8 | CRUD kategori & barang, validasi, pencarian, gambar barang |
| `tests/Feature/TransaksiBarangTest.php` | Fitur | 18 | Barang masuk, barang keluar, verifikasi, foto bukti |
| `tests/Feature/SesiKedaluwarsaTest.php` | Fitur | 3 | Penanganan error 419 |
| `tests/Feature/GambarGagalTest.php` | Fitur | 3 | Penyimpanan gambar gagal (profil, barang, barang keluar): kembali ke form dengan pesan, bukan error 500, data tidak tersimpan |
| `tests/Feature/PopupTest.php` | Fitur | 5 | Halaman dikirim sebagai isi popup / panel kanan, halaman daftar tetap utuh, simpan profil kembali ke halaman asal dan menolak alamat situs lain |

Contoh test case:

| Skenario | Langkah | Hasil yang diharapkan | Hasil |
|---|---|---|---|
| Manager menyetujui | Stok 10, Operator ajukan 4, Manager setujui | Disetujui, verifikator = Manager, stok = 6 | Lulus |
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
| 1 | J.620100.004.02 | Menggunakan Struktur Data | Array `STATUS_BARANG`, `VERIFIKASI`, `ROLES`; array asosiatif `PREFIX_KATEGORI`; array bersarang `INFO_ROLE`; Collection (`pluck`, `map`, `max`, `concat`, `sortByDesc`); objek model dan relasi |
| 2 | J.620100.009.01 | Menggunakan Spesifikasi Program | Hak akses, aturan bisnis, dan alur (Bagian 3) diterapkan di route, middleware, Form Request; rancangan database (dokumen 01) diterapkan di migration |
| 3 | J.620100.010.01 | Menerapkan Perintah Eksekusi Bahasa Pemrograman Berbasis Teks, Grafik, dan Multimedia | Perintah teks: `php artisan ...`, `npm run build`, `composer`, `git`; antarmuka grafis Blade + Tailwind dan grafik dashboard; multimedia: unggah, pratinjau, tampil, ganti, hapus gambar |
| 4 | J.620100.016.01 | Menulis Kode dengan Prinsip sesuai Guidelines dan Best Practices | PSR-12 (Laravel Pint), MVC, Form Request, service class, pemisahan tugas, keamanan (Bagian 8), transaksi database |
| 5 | J.620100.017.02 | Mengimplementasikan Pemrograman Terstruktur | Percabangan `if/elseif/else` dan `match`, perulangan `foreach` / `@forelse`, fungsi dengan parameter dan nilai kembali, prosedur `void`, `try...catch` |
| 6 | J.620100.023.02 | Membuat Dokumen Kode Program | PHPDoc di class dan method, komentar Blade, dokumen 01–05 |
| 7 | J.620100.025.02 | Melakukan Debugging | 14 bug beserta perbaikannya dan teknik debugging (Bagian 9) |
| 8 | J.620100.033.02 | Melaksanakan Pengujian Unit Program | 78 test PHPUnit unit dan fitur (Bagian 10) |

## 12. Skenario Demo

1. Login **operator_siti**. Tunjukkan menu Kategori dan Pengguna tidak ada.
2. Klik **foto profil** di kanan atas: panel profil muncul dari kanan; unggah foto; foto tampil di menu samping.
3. **Data Barang**: tambah barang di popup beserta gambar; kode otomatis AT-002; pratinjau gambar.
4. **Barang Masuk**: catat 5 unit (boleh dengan foto nota); stok bertambah.
5. **Barang Keluar**: ajukan tanpa foto (ditolak), lalu dengan foto (Pending, stok tetap).
6. Login **manager_marco**: tidak ada tombol tambah barang; buka permintaan, lihat foto, coba Tolak tanpa alasan (ditolak), lalu Setujui (stok berkurang).
7. Login **admin_ohim**: menu Pengguna menampilkan tiga role; role akun sendiri terkunci; koreksi barang masuk.
8. Tombol **Cetak** pada daftar barang.
9. Terminal: `php artisan test` → 78 test lulus.

Mengembalikan data ke kondisi awal sebelum demo: `php artisan migrate:fresh --seed`.
