# Dokumentasi Program Inventaris Gudang Sekolah

| Keterangan | Isi |
|---|---|
| Nama Peserta | Renaissance Ricarda Sugiarto Putra |
| Skema | Pemrogram Junior (Junior Coder) |
| Aplikasi | Inventaris Gudang Sekolah (berbasis web) |
| Framework | Laravel 12 (PHP 8.2) |
| Database | MySQL / MariaDB (XAMPP) untuk lokal, `gudang_sekolah`; PostgreSQL (Supabase) untuk versi online |
| Tampilan | Blade Template + Tailwind CSS 4 (di-build dengan Vite), responsif untuk komputer dan HP |
| Pengujian | PHPUnit 11 (`php artisan test`) |

## 1. Deskripsi Program

Inventaris Gudang Sekolah adalah aplikasi web untuk mencatat data barang di gudang sekolah, mencatat barang yang masuk, dan mengelola permintaan barang keluar dengan persetujuan berjenjang. Aplikasi menerapkan prinsip **pemisahan tugas (separation of duties)**: orang yang mencatat transaksi berbeda dengan orang yang menyetujuinya, sehingga setiap pengeluaran barang selalu diawasi.

Pengguna terdiri dari dua role:

- **Operator** (petugas gudang) – mendaftarkan barang baru, mencatat barang masuk, dan mengajukan permintaan barang keluar atas permintaan guru/staf (pemohon).
- **Admin** (pengawas, misalnya kepala/waka sarpras) – memverifikasi (menyetujui/menolak) permintaan barang keluar, mengoreksi data, serta mengelola kategori, data barang, dan akun pengguna.

Aturan utama: **stok bertambah saat barang masuk dicatat**, sedangkan **stok baru berkurang setelah Admin menyetujui** permintaan barang keluar. Setiap verifikasi mencatat siapa Admin yang memverifikasi, tanggalnya, dan catatan/alasannya.

## 2. Cara Menjalankan Program

Prasyarat: XAMPP (Apache + MySQL aktif), PHP 8.2, Composer, Node.js.

1. Aktifkan MySQL di XAMPP Control Panel.
2. Buat database: `CREATE DATABASE gudang_sekolah;` (bisa lewat phpMyAdmin).
3. Salin konfigurasi: `copy .env.example .env` lalu `php artisan key:generate` (lewati jika `.env` sudah ada).
4. Pasang dependensi (jika folder `vendor`/`node_modules` belum ada): `composer install` dan `npm install`.
5. Buat tabel sekaligus isi data sampel: `php artisan migrate:fresh --seed`
6. Build tampilan (CSS): `npm run build`
7. Jalankan server: `php artisan serve` lalu buka `http://localhost:8000`
8. Menjalankan pengujian unit: `php artisan test`

Langkah mengunggah ke GitHub dan menjalankan aplikasi secara online (Render + database Supabase) dijelaskan di file `TUTORIAL_DEPLOY.md`.

Akun login (data sampel):

| Nama | Username | Password | Role |
|---|---|---|---|
| Ibrahim Risyad | operator_ohim | operator123 | Operator |
| Marco Ivanos | admin_marco | @admin123 | Admin |

## 3. Spesifikasi Program

### 3.1 Hak Akses per Role

| No | Fitur | Admin | Operator |
|---|---|---|---|
| 1 | Login dan logout | Ya | Ya |
| 2 | Dashboard ringkasan (jumlah barang, stok menipis, transaksi terbaru, permintaan Pending) | Ya | Ya |
| 3 | Kelola pengguna (tambah, ubah, hapus) | Ya | Tidak |
| 4 | Kelola kategori (tambah, ubah, hapus) | Ya | Tidak |
| 5 | Lihat data barang, cari, filter kategori, detail riwayat | Ya | Ya |
| 6 | Tambah barang baru (kode dibuat otomatis) | Ya | Ya |
| 7 | Ubah dan hapus data barang | Ya | Tidak |
| 8 | Catat barang masuk (stok otomatis bertambah) | Tidak | Ya |
| 9 | Koreksi (ubah/hapus) barang masuk, stok ikut disesuaikan | Ya | Tidak |
| 10 | Ajukan permintaan barang keluar (status Pending) | Tidak | Ya |
| 11 | Verifikasi barang keluar: Setujui (stok berkurang) / Tolak (wajib alasan) | Ya | Tidak |
| 12 | Lihat daftar dan detail barang masuk / barang keluar, cetak | Ya | Ya |

Alasan pembagian:

- Admin **tidak** mencatat transaksi dan Operator **tidak** memverifikasi, sehingga tidak ada orang yang bisa menyetujui permintaannya sendiri.
- Operator hanya **menambahkan** data. Perubahan atau penghapusan data yang sudah tercatat hanya dapat dilakukan Admin, sehingga barang masuk pun tetap berada di bawah pengawasan Admin.
- Operator tetap boleh mendaftarkan barang baru karena barang baru harus terdaftar sebelum dicatat sebagai barang masuk.

### 3.2 Aturan Bisnis dan Validasi

- Kode barang dibuat otomatis dengan pola `PREFIX-NOMOR`, prefix sesuai kamus data: AT (Alat Tulis), FR (Furnitur), KN (Kebersihan), AO (Alat Olahraga), EK (Elektronik). Nomor melanjutkan nomor terbesar pada prefix yang sama, misalnya setelah AT-001 berikutnya AT-002.
- Kategori baru di luar kamus data memakai huruf awal dua kata pertama (contoh "Alat Laboratorium" menjadi AL).
- Stok awal hanya diisi saat barang baru dibuat. Setelah itu stok hanya berubah lewat transaksi barang masuk dan barang keluar, sehingga riwayat stok dapat dipertanggungjawabkan.
- Kondisi barang (`status_barang`) hanya boleh: Baik, Rusak Ringan, Rusak Berat.
- Verifikasi barang keluar hanya: Pending, Disetujui, Ditolak. Permintaan baru selalu Pending.
- Pemohon (nama guru/staf yang meminta) wajib diisi saat mengajukan barang keluar.
- Jumlah barang keluar tidak boleh melebihi stok saat diajukan, dan dicek ulang saat disetujui.
- Penolakan wajib disertai alasan (`catatan_verifikasi`). Persetujuan boleh disertai catatan.
- Pengajuan barang keluar tidak dapat diubah atau dihapus (menjadi jejak audit). Pengajuan yang keliru cukup ditolak Admin dengan alasan.
- Permintaan yang sudah Disetujui/Ditolak tidak dapat diverifikasi ulang.
- Kategori yang masih dipakai barang, barang yang sudah memiliki transaksi, dan pengguna yang sudah memiliki transaksi tidak dapat dihapus.
- Admin tidak dapat menghapus akunnya sendiri atau mengubah role dirinya sendiri, agar sistem tidak kehilangan Admin karena salah pilih. Pengamanan dibuat dua lapis: pilihan role dikunci di form, dan server tetap menolak bila data role dimanipulasi.
- Koreksi/hapus barang masuk menyesuaikan stok kembali; ditolak jika stok tersebut sudah terpakai.

### 3.3 Alur Barang Keluar

1. Guru/staf (pemohon) meminta barang kepada petugas gudang.
2. **Operator** mengisi form "Ajukan Barang Keluar" (barang, jumlah, pemohon, tujuan). Status otomatis **Pending**, stok belum berubah.
3. **Admin** membuka menu Barang Keluar, filter **Pending**, lalu menekan **Verifikasi** untuk membuka detail permintaan.
4. Jika **Setujui**: sistem mengecek stok, mengurangi stok, lalu menyimpan status Disetujui, `id_verifikator`, `tanggal_verifikasi`, dan catatan dalam satu transaksi database.
5. Jika **Tolak**: Admin wajib mengisi alasan. Status menjadi Ditolak, verifikator dan tanggal dicatat, stok tidak berubah.
6. Operator dapat melihat hasil verifikasi beserta alasan pada halaman detail.

### 3.4 Batasan Sistem

- Kondisi barang (`status_barang`) pada tabel barang berlaku untuk satu jenis barang, bukan per unit. Jika sebagian unit rusak, sebaiknya dicatat sebagai barang terpisah.
- Barang keluar dianggap keluar dari stok gudang, baik barang habis pakai (pulpen) maupun barang yang dipindahkan ke ruangan (proyektor, kursi). Fitur peminjaman dan pengembalian belum termasuk dalam cakupan program ini.

## 4. Desain Database

Database `gudang_sekolah` berisi 5 tabel: `user`, `kategori`, `barang`, `barang_masuk`, `barang_keluar`. Tabel `migrations` dibuat otomatis oleh Laravel untuk mencatat migration yang sudah dijalankan. Session dan cache disimpan dalam file (bukan tabel) agar database tetap 5 tabel sesuai rancangan.

Relasi:

- `user` 1 : N `barang_masuk` (mengelola) – Operator yang mencatat.
- `user` 1 : N `barang_keluar` (mengelola) – Operator yang mengajukan, lewat `id_user`.
- `user` 1 : N `barang_keluar` (memverifikasi) – Admin yang memverifikasi, lewat `id_verifikator`.
- `barang` 1 : N `barang_masuk` (memasukkan) dan `barang` 1 : N `barang_keluar` (mengeluarkan).
- `kategori` 1 : N `barang` (memiliki).

Semua foreign key memakai `ON DELETE RESTRICT` agar data induk tidak bisa terhapus selama masih dipakai.

Migration dan seluruh test sudah diuji di MySQL maupun PostgreSQL. Saat memakai PostgreSQL (Supabase), migration `enable_row_level_security` mengaktifkan Row Level Security di semua tabel agar data tidak dapat dibaca lewat REST API publik Supabase; di MySQL migration tersebut tidak melakukan apa-apa.

## 5. Revisi Dokumen Rancangan

Bagian ini merangkum seluruh perubahan terhadap dokumen rancangan awal beserta alasannya, dan menyediakan isi pengganti untuk ERD, Desain Database, Database Relation, Kamus Data, dan Data Sampel.

### 5.1 Ringkasan Perubahan

| No | Rancangan Awal | Revisi | Alasan |
|---|---|---|---|
| 1 | Role Admin dan Manager | Role Admin (pengawas/verifikator) dan Operator (petugas gudang) | Peran lebih jelas dan menerapkan pemisahan tugas: pencatat transaksi berbeda dengan pemberi persetujuan. |
| 2 | Verifikasi tidak mencatat siapa yang memverifikasi | Tambah `id_verifikator` (FK ke user) dan `tanggal_verifikasi` di `barang_keluar` | Peran pengawas tercatat di database dan dapat diaudit. |
| 3 | Penolakan tanpa alasan | Tambah `catatan_verifikasi` VARCHAR(255) | Alasan penolakan dapat dibaca Operator dan pemohon. |
| 4 | Tidak ada data peminta barang | Tambah `pemohon` VARCHAR(100) di `barang_keluar` | Mengetahui siapa yang meminta barang, bukan hanya lokasi tujuan. |
| 5 | `role` VARCHAR(7) | ENUM('Admin','Operator') | Nilai role terkunci sesuai kamus data, sama seperti kolom `verifikasi`. |
| 6 | `password` VARCHAR(50) | VARCHAR(255), disimpan ter-hash bcrypt | Hash bcrypt panjangnya 60 karakter; password tidak boleh disimpan dalam teks biasa. |
| 7 | `username` tanpa batasan | UNIQUE | Username dipakai untuk login sehingga tidak boleh kembar. |
| 8 | `barang.id_barang` VARCHAR(20) berisi "AT-001" | `id_barang` INT auto increment (PK) + `kode_barang` VARCHAR(20) UNIQUE berisi "AT-001" | Primary key angka lebih efisien untuk relasi; kode tetap unik dan tampil di aplikasi. FK `id_barang` di tabel transaksi ikut menjadi INT. |
| 9 | Nama database "Gudang Sekolah" | `gudang_sekolah` | Nama database MySQL sebaiknya tanpa spasi. |
| 10 | Tidak ada fitur kelola akun | Admin dapat mengelola pengguna | Akun baru tidak lagi harus dibuat lewat database secara manual. |
| 11 | Data sampel transaksi merujuk barang yang tidak ada (EK-002, AO-003, KN-005, EK-010, AO-007, FR-008) | Keenam barang ditambahkan ke data sampel barang | Tanpa itu foreign key gagal. |
| 12 | Tanggal barang keluar (2026–2027) lebih awal dari barang masuk (2029–2035); jumlah keluar 50 dan 100 melebihi stok | Tanggal diurutkan pada September–Oktober 2026; jumlah disesuaikan | Data sampel harus logis dan lolos validasi aplikasi. |
| 13 | Nilai "baik" huruf kecil | "Baik" | Disamakan dengan kamus data. |

Salah ketik pada dokumen awal yang ikut diperbaiki: "jumblah" (Database Relation), "mengelluarkan" (ERD), "id_verifikasi" (Kamus Data, seharusnya `verifikasi`), "Tabel : id_kategori" (seharusnya "Tabel : kategori"), "Iventaris", "Administratoer", "Keteranan", dan "bai" (seharusnya "baik").

### 5.2 Perubahan ERD dan Database Relation

- Entitas **pengguna**: atribut tetap (id_user, nama_user, username, password, role).
- Tambahkan relasi baru **pengguna 1 — memverifikasi — N barang keluar** (garis kedua dari pengguna ke barang keluar).
- Entitas **barang keluar**: tambahkan atribut `pemohon`, `id_verifikator`, `tanggal_verifikasi`, `catatan_verifikasi`.
- Entitas **barang**: tambahkan atribut `kode_barang`; `id_barang` tetap sebagai primary key.
- Pada gambar Database Relation, tabel `barang_keluar` mendapat FK kedua `id_verifikator` yang mengarah ke `user.id_user`.

### 5.3 Desain Database (Revisi)

Tabel `user`:

| Nama Field | Tipe Data | Panjang | Keterangan |
|---|---|---|---|
| id_user | int | 11 | Primary Key, auto increment |
| nama_user | varchar | 100 | |
| username | varchar | 50 | Unique |
| password | varchar | 255 | Hash bcrypt |
| role | enum | 'Admin','Operator' | |

Tabel `kategori`:

| Nama Field | Tipe Data | Panjang | Keterangan |
|---|---|---|---|
| id_kategori | int | 11 | Primary Key, auto increment |
| nama_kategori | varchar | 20 | Unique |

Tabel `barang`:

| Nama Field | Tipe Data | Panjang | Keterangan |
|---|---|---|---|
| id_barang | int | 11 | Primary Key, auto increment |
| kode_barang | varchar | 20 | Unique (contoh AT-001) |
| id_kategori | int | 11 | Foreign Key ke kategori |
| nama_barang | varchar | 100 | |
| stok | int | 11 | |
| satuan | varchar | 20 | |
| lokasi | varchar | 100 | |
| status_barang | varchar | 30 | |

Tabel `barang_masuk`:

| Nama Field | Tipe Data | Panjang | Keterangan |
|---|---|---|---|
| id_masuk | int | 11 | Primary Key, auto increment |
| id_barang | int | 11 | Foreign Key ke barang |
| id_user | int | 11 | Foreign Key ke user (Operator pencatat) |
| tanggal | date | | |
| jumlah | int | 11 | |
| sumber_barang | varchar | 255 | |
| status_barang | varchar | 30 | |

Tabel `barang_keluar`:

| Nama Field | Tipe Data | Panjang | Keterangan |
|---|---|---|---|
| id_keluar | int | 11 | Primary Key, auto increment |
| id_barang | int | 11 | Foreign Key ke barang |
| id_user | int | 11 | Foreign Key ke user (Operator pengaju) |
| tanggal | date | | |
| jumlah | int | 11 | |
| pemohon | varchar | 100 | Baru: nama guru/staf peminta |
| tujuan | varchar | 100 | |
| status_barang | varchar | 30 | |
| verifikasi | enum | 'Pending','Disetujui','Ditolak' | Default Pending |
| id_verifikator | int | 11 | Baru: Foreign Key ke user (Admin), boleh kosong |
| tanggal_verifikasi | date | | Baru: boleh kosong |
| catatan_verifikasi | varchar | 255 | Baru: alasan/catatan, boleh kosong |

### 5.4 Kamus Data (Revisi)

Tabel `user`, field `role`:

| Data | Keterangan |
|---|---|
| Admin | Pengawas sistem: memverifikasi (menyetujui/menolak) permintaan barang keluar, mengoreksi data, mengelola kategori, data barang, dan pengguna. |
| Operator | Petugas gudang: mendaftarkan barang baru, mencatat barang masuk, dan mengajukan permintaan barang keluar. |

Tabel `barang_keluar`, field `verifikasi`:

| Data | Keterangan |
|---|---|
| Pending | Permintaan masih menunggu verifikasi Admin |
| Disetujui | Permintaan disetujui Admin, stok dikurangi |
| Ditolak | Permintaan ditolak Admin dengan alasan, stok tidak berubah |

Kamus data kategori, kode prefix barang (AT, FR, KN, AO, EK), dan `status_barang` (Baik, Rusak Ringan, Rusak Berat) tidak berubah.

### 5.5 Data Sampel (Revisi)

Tabel `user`:

| id_user | nama_user | username | password | role |
|---|---|---|---|---|
| 1 | Ibrahim Risyad | operator_ohim | operator123 (tersimpan ter-hash) | Operator |
| 2 | Marco Ivanos | admin_marco | @admin123 (tersimpan ter-hash) | Admin |

Tabel `kategori`: tidak berubah (1 Alat Tulis, 2 Furnitur, 3 Kebersihan, 4 Alat Olahraga, 5 Elektronik).

Tabel `barang`:

| id_barang | kode_barang | id_kategori | nama_barang | stok | satuan | lokasi | status_barang |
|---|---|---|---|---|---|---|---|
| 1 | AT-001 | 1 | Pulpen | 5 | pack | sarpras | Baik |
| 2 | FR-001 | 2 | Meja Guru | 7 | buah | gudang utama | Baik |
| 3 | KN-001 | 3 | Sapu | 10 | buah | gudang utama | Baik |
| 4 | AO-001 | 4 | Bola Voli | 4 | buah | gudang olahraga | Baik |
| 5 | EK-001 | 5 | Proyektor Epson | 6 | unit | gudang utama | Baik |
| 6 | EK-002 | 5 | Laptop Asus | 9 | unit | gudang utama | Baik |
| 7 | AO-003 | 4 | Bola Basket | 10 | buah | gudang olahraga | Baik |
| 8 | KN-005 | 3 | Kain Pel | 23 | buah | gudang utama | Baik |
| 9 | EK-010 | 5 | Speaker Aktif | 6 | unit | gudang utama | Baik |
| 10 | AO-007 | 4 | Matras Senam | 20 | buah | gudang olahraga | Baik |
| 11 | FR-008 | 2 | Kursi Siswa | 120 | buah | gudang utama | Baik |

Tabel `barang_masuk`:

| id_masuk | id_barang | id_user | tanggal | jumlah | sumber_barang | status_barang |
|---|---|---|---|---|---|---|
| 1 | 6 (EK-002) | 1 | 15-9-2026 | 9 | Dana BOS 2026 | Baik |
| 2 | 7 (AO-003) | 1 | 30-9-2026 | 10 | Pembelian | Baik |
| 3 | 8 (KN-005) | 1 | 1-10-2026 | 23 | Bantuan Dinas | Baik |

Tabel `barang_keluar`:

| id_keluar | id_barang | id_user | tanggal | jumlah | pemohon | tujuan | status_barang | verifikasi | id_verifikator | tanggal_verifikasi | catatan_verifikasi |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | 9 (EK-010) | 1 | 5-10-2026 | 4 | Budi Santoso (Kaprog TKJ) | Lab TKJ3 | Baik | Disetujui | 2 | 5-10-2026 | - |
| 2 | 10 (AO-007) | 1 | 6-10-2026 | 10 | Sari Wulandari (Guru PJOK) | Lab JB 3 | Baik | Ditolak | 2 | 6-10-2026 | Matras sedang dipakai untuk persiapan lomba senam. |
| 3 | 11 (FR-008) | 1 | 8-10-2026 | 100 | Andi Pratama (Wali Kelas 10 NKPI 2) | Kelas 10 NKPI 2 | Baik | Pending | - | - | - |

## 6. Struktur Program (Arsitektur MVC)

Laravel memakai pola **Model - View - Controller**. Alur satu permintaan: Browser → `routes/web.php` → Middleware (`auth`, `role`) → Form Request (validasi) → Controller → Model / Service → View (Blade) → Browser.

| Lokasi | Isi |
|---|---|
| `routes/web.php` | Daftar URL dan pembagian hak akses per role |
| `app/Http/Middleware/CekRole.php` | Membatasi halaman berdasarkan role (Admin/Operator) |
| `app/Http/Controllers/` | AuthController, DashboardController, UserController, KategoriController, BarangController, BarangMasukController, BarangKeluarController |
| `app/Http/Requests/` | Aturan validasi form (UserRequest, KategoriRequest, BarangRequest, BarangMasukRequest, BarangKeluarRequest) |
| `app/Models/` | Model Eloquent untuk 5 tabel beserta relasinya |
| `app/Services/StokService.php` | Satu-satunya tempat yang mengubah stok (tambah/kurangi) |
| `app/Exceptions/StokTidakCukupException.php` | Error khusus saat stok tidak mencukupi |
| `database/migrations/` | Pembuatan 5 tabel sesuai desain database |
| `database/seeders/DatabaseSeeder.php` | Data sampel |
| `database/factories/UserFactory.php` | Pembuat data pengguna palsu untuk pengujian |
| `app/Providers/AppServiceProvider.php` | Pengaturan pagination berbahasa Indonesia dan view composer jumlah permintaan Pending |
| `resources/views/` | Tampilan Blade (layout, login, dashboard, user, kategori, barang, barang-masuk, barang-keluar) |
| `resources/views/components/` | Komponen tampilan yang dipakai ulang: `icon`, `badge`, `empty`, `error` |
| `resources/views/partials/` | Potongan tampilan: pesan notifikasi (`flash`) dan navigasi halaman (`pagination`) |
| `resources/css/app.css`, `resources/js/app.js` | Token desain (warna, font), kelas komponen (`btn-primary`, `card`, `input`, ...) dan interaksi kecil (menu HP, tutup notifikasi, lihat password) |
| `tests/Unit`, `tests/Feature` | Pengujian unit dan pengujian fitur |
| `Dockerfile`, `docker/entrypoint.sh` | Konfigurasi server untuk hosting online (PHP 8.2 + Apache + PostgreSQL) |

### 6.1 Desain Antarmuka

- **Konsisten**: warna, tombol, kartu, input, dan tabel memakai kelas komponen yang sama dari `resources/css/app.css`, sehingga setiap halaman terlihat seragam.
- **Bisa dipakai tanpa internet**: font *Plus Jakarta Sans* dibundel lewat Vite dan ikon berupa SVG inline (komponen `<x-icon>`), tidak memuat CDN.
- **Responsif**: di HP, sidebar berubah menjadi menu geser yang dibuka dengan tombol menu; tabel dapat digeser ke samping di dalam kartunya.
- **Sesuai role**: menu, tombol aksi, dan aksi cepat di dashboard hanya muncul bila role pengguna berhak memakainya. Jumlah permintaan Pending tampil sebagai penanda di menu Barang Keluar.
- **Dashboard**: sapaan sesuai jam, kartu statistik, grafik batang stok per kategori, komposisi status permintaan, daftar stok menipis, dan aktivitas terbaru (gabungan barang masuk dan keluar).
- **Siap cetak**: tombol Cetak menyembunyikan sidebar, filter, dan tombol aksi sehingga hanya tabel yang tercetak.

## 7. Pemetaan Unit Kompetensi

| No | Kode Unit | Judul Unit | Bukti pada Program |
|---|---|---|---|
| 1 | J.620100.004.02 | Menggunakan Struktur Data | Array konstanta `Barang::STATUS_BARANG`, `Barang::PREFIX_KATEGORI` (array asosiatif), `BarangKeluar::VERIFIKASI`, `User::ROLES`; Collection Laravel (`pluck`, `map`, `max`) pada `Barang::generateKode()`; penggabungan dan pengurutan dua koleksi (`concat`, `sortByDesc`, `take`) pada aktivitas terbaru di `DashboardController`; array menu di layout; destructuring array di seeder. |
| 2 | J.620100.009.01 | Menggunakan Spesifikasi Program | Bagian 3 dokumen ini (hak akses, aturan bisnis, alur) diterapkan pada route, middleware, dan Form Request; desain database revisi (Bagian 5) diimplementasikan di migration. |
| 3 | J.620100.010.01 | Menerapkan Perintah Eksekusi Bahasa Pemrograman Berbasis Teks, Grafik, dan Multimedia | Perintah berbasis teks: `php artisan migrate:fresh --seed`, `php artisan serve`, `php artisan test`, `npm run build`, `vendor/bin/pint`. Antarmuka grafis berbasis web dengan Blade + Tailwind. |
| 4 | J.620100.016.01 | Menulis Kode dengan Prinsip sesuai Guidelines dan Best Practices | Standar PSR-12 dicek dengan Laravel Pint; pola MVC; validasi di Form Request; pemisahan tugas (separation of duties) antar role; password di-hash bcrypt; proteksi CSRF (`@csrf`); escaping output Blade (`{{ }}` dan `@js`) untuk mencegah XSS; query Eloquent (aman dari SQL injection); `DB::transaction` dan `lockForUpdate` untuk konsistensi stok; penamaan konsisten. |
| 5 | J.620100.017.02 | Mengimplementasikan Pemrograman Terstruktur | Percabangan (`if/elseif/else`, `match` pada komponen badge), perulangan (`foreach`, `@foreach`, `@forelse`), fungsi/method kecil dengan satu tugas (`tambah`, `kurangi`, `cukup`, `prefixKategori`, `generateKode`, `simpanVerifikasi`, `punyaTransaksi`, `inisial`, `salam`). Contoh percabangan bertingkat: `DashboardController::salam()` menentukan Selamat pagi/siang/sore/malam dari jam. |
| 6 | J.620100.023.02 | Membuat Dokumen Kode Program | Komentar PHPDoc di setiap class dan method penting (`@property`, `@throws`), komentar Blade, serta dokumen ini. |
| 7 | J.620100.025.02 | Melakukan Debugging | Bagian 9: daftar bug yang ditemukan beserta perbaikannya dan teknik debugging yang dipakai. |
| 8 | J.620100.033.02 | Melaksanakan Pengujian Unit Program | Bagian 10: 50 test (unit + fitur) dengan PHPUnit, semua lulus. |

## 8. Penjelasan Kode Penting

### 8.1 Login (AuthController)

`Auth::attempt(['username' => ..., 'password' => ...])` mencari user berdasarkan username lalu mencocokkan password dengan hash bcrypt. Jika cocok, session dibuat ulang (`session()->regenerate()`) untuk mencegah session fixation. Model `User` memakai cast `'password' => 'hashed'` sehingga setiap password yang disimpan otomatis di-hash.

### 8.2 Hak Akses (Middleware CekRole)

Middleware menerima daftar role yang diizinkan, misalnya `->middleware('role:Admin')` atau `->middleware('role:Admin,Operator')`. Jika role pengguna tidak termasuk, sistem menampilkan error 403 (Forbidden). Di tampilan, tombol yang tidak boleh dipakai juga disembunyikan dengan `auth()->user()->isAdmin()` / `isOperator()`, dan menu Kategori serta Pengguna hanya muncul untuk Admin.

### 8.3 Kode Barang Otomatis (Barang::generateKode)

1. Ambil prefix dari nama kategori lewat `prefixKategori()` (memakai array `PREFIX_KATEGORI`).
2. Ambil semua kode barang berawalan prefix tersebut (`pluck`).
3. Ubah bagian nomor menjadi angka (`map`) dan cari nilai terbesar (`max`).
4. Tambah 1 dan format tiga digit dengan `sprintf('%s-%03d', ...)`, misalnya AO-008.

### 8.4 Pengelolaan Stok (StokService)

Seluruh perubahan stok terpusat di `StokService` agar aturan "stok tidak boleh negatif" hanya ditulis sekali. Method `kurangi()` melempar `StokTidakCukupException` jika stok kurang. Controller memanggil service di dalam `DB::transaction()`, sehingga bila terjadi error maka data transaksi dan stok sama-sama dibatalkan (rollback). `lockForUpdate()` mengunci baris barang agar dua proses bersamaan tidak membuat stok kacau.

### 8.5 Verifikasi Barang Keluar (BarangKeluarController)

Method `setujui()` dan `tolak()` hanya dapat diakses Admin. Keduanya memastikan status masih Pending, lalu memanggil method privat `simpanVerifikasi()` yang mengisi `verifikasi`, `id_verifikator` (Admin yang sedang login), `tanggal_verifikasi` (hari ini), dan `catatan_verifikasi`. Pada `setujui()`, pengurangan stok dan penyimpanan verifikasi dibungkus `DB::transaction()`. Pada `tolak()`, catatan wajib diisi.

### 8.6 Validasi (Form Request)

Contoh `BarangKeluarRequest`: aturan dasar (`required`, `integer`, `min:1`, `exists:barang,id_barang`, `Rule::in(Barang::STATUS_BARANG)`) lalu validasi tambahan di method `after()` yang mengecek jumlah tidak melebihi stok. `UserRequest` memastikan username unik, role hanya Admin/Operator, dan password dikonfirmasi (boleh kosong saat mengubah data). Pesan error tampil di bawah setiap input melalui komponen `<x-error>`.

## 9. Debugging

### 9.1 Bug yang Ditemukan dan Perbaikannya

| No | Lokasi | Masalah | Akibat | Perbaikan |
|---|---|---|---|---|
| 1 | `app/Models/Kategori.php` | Salah ketik `protected $tabl` | Laravel mencari tabel `kategoris` sehingga muncul error "table not found" | Diganti `protected $table = 'kategori'` |
| 2 | `app/Models/BarangMasuk.php` | `$fillable` berisi `jumblah` dan tidak ada `tanggal` | Kolom `jumlah` dan `tanggal` tidak ikut tersimpan, query gagal | `$fillable` disesuaikan dengan nama kolom |
| 3 | Semua migration | `down()` menghapus tabel yang salah (`users`, `kategoris`, `barangs`, ...) | `migrate:rollback` gagal / tabel tertinggal | Nama tabel di `down()` disamakan |
| 4 | Migration barang_masuk | Tidak ada foreign key | Data masuk bisa merujuk barang/user yang tidak ada | Ditambah foreign key ke `barang` dan `user` |
| 5 | `KategoriController::store` | Hanya validasi, tidak menyimpan dan tidak redirect | Data kategori tidak pernah tersimpan, halaman kosong | Ditambah `Kategori::create()` dan redirect dengan pesan |
| 6 | View `kategori.index` | View dipanggil tetapi file belum ada | Error "View not found" | Semua view dibuat |
| 7 | Migration barang | FK kategori `onDelete('cascade')` | Menghapus kategori ikut menghapus seluruh barangnya | Diganti `restrictOnDelete()` + pengecekan di controller |
| 8 | Layout | Link menu berisi `#` | Menu tidak berfungsi | Diganti `route(...)` dan penanda menu aktif |
| 9 | Log lama `storage/logs/laravel.log` | "Unmatched '}' in routes/web.php", "Target class [DashboardController] does not exist", "Route [dashboard] not defined", koneksi MySQL ditolak | Aplikasi error saat dibuka | Sintaks route diperbaiki, route diberi `->name()`, MySQL dinyalakan |
| 10 | `database/factories/UserFactory.php` | Username acak dari Faker kadang berisi titik (misal `john.doe`), padahal validasi `alpha_dash` menolak titik | Test "ubah pengguna" kadang gagal kadang lulus (flaky test) | Format username factory diganti `user_####??` sehingga selalu valid |
| 11 | `config/app.php` | Zona waktu aplikasi masih `UTC` | Verifikasi yang dilakukan pukul 00.00–07.00 WIB tercatat dengan tanggal kemarin; salam dan tanggal di dashboard tidak sesuai | Zona waktu diubah ke `Asia/Jakarta` lewat `APP_TIMEZONE`, bahasa tanggal ke Indonesia (`APP_LOCALE=id`) |

### 9.2 Teknik Debugging yang Digunakan

- Membaca pesan error dan stack trace pada halaman error Laravel (`APP_DEBUG=true` di `.env`, wajib `false` saat produksi).
- Membaca file log `storage/logs/laravel.log`.
- `dd($variabel)` / `dump()` untuk memeriksa isi variabel di tengah proses.
- `php artisan route:list` untuk memastikan route dan nama route terdaftar.
- `php artisan tinker` untuk mencoba query model secara langsung.
- Menjalankan `php artisan test` setelah setiap perubahan, dan menjalankannya berulang kali untuk mendeteksi test yang hasilnya tidak konsisten (kasus bug no. 10).

## 10. Pengujian Unit Program

Pengujian memakai PHPUnit dengan database SQLite di memori (konfigurasi `phpunit.xml`), sehingga data asli di MySQL tidak terganggu. Setiap test memakai `RefreshDatabase` agar mulai dari database kosong.

Perintah: `php artisan test`

| File | Yang Diuji | Jumlah Test |
|---|---|---|
| `tests/Unit/StokServiceTest.php` | Tambah stok, kurangi stok, stok habis, stok tidak cukup (exception), stok tidak berubah saat gagal, jumlah nol ditolak, cek stok cukup | 7 |
| `tests/Unit/KodeBarangTest.php` | Prefix sesuai kamus data, prefix kategori baru, kode pertama 001, kode melanjutkan nomor terbesar | 5 |
| `tests/Feature/AuthTest.php` | Halaman login, login berhasil, login gagal, password ter-hash, tamu diarahkan ke login, logout | 6 |
| `tests/Feature/HakAksesTest.php` | Halaman daftar untuk kedua role, Operator dilarang mengakses fitur Admin, Operator dilarang mengoreksi barang masuk, Admin dilarang mencatat transaksi, Operator dilarang memverifikasi | 5 |
| `tests/Feature/UserTest.php` | Tambah Operator (password ter-hash), username unik & role valid, ubah tanpa ganti password, Admin tidak bisa ubah role/hapus akun sendiri, pilihan role terkunci di form akun sendiri, pengguna bertransaksi tidak bisa dihapus, pengguna tanpa transaksi bisa dihapus | 7 |
| `tests/Feature/KategoriBarangTest.php` | Tambah kategori, validasi unik & maks 20 karakter, kategori terpakai tidak bisa dihapus, kode otomatis, ubah barang tidak mengubah stok, validasi status, pencarian | 7 |
| `tests/Feature/TransaksiBarangTest.php` | Operator mencatat barang masuk, Admin mengoreksi/menghapus barang masuk, hapus ditolak bila stok terpakai, jumlah minimal 1, pengajuan Pending, pemohon wajib, melebihi stok ditolak, setujui mencatat verifikator, tolak wajib alasan, persetujuan gagal saat stok kurang, tidak bisa verifikasi ulang, detail menampilkan pengaju & verifikator | 13 |

Hasil terakhir: **Tests: 50 passed (143 assertions)**, dijalankan tiga kali berturut-turut dengan hasil sama.

Contoh test case:

| Skenario | Langkah | Hasil yang Diharapkan | Hasil |
|---|---|---|---|
| Admin menyetujui | Stok 10, Operator ajukan keluar 4, Admin setujui | Verifikasi = Disetujui, id_verifikator = Admin, tanggal hari ini, stok = 6 | Lulus |
| Tolak tanpa alasan | Admin menekan Tolak tanpa catatan | Error validasi, status tetap Pending | Lulus |
| Stok tidak cukup | Stok 3, permintaan 100, Admin setujui | Pesan gagal, status tetap Pending, stok tetap 3 | Lulus |
| Pemisahan tugas | Admin membuka form barang masuk / barang keluar | 403 Forbidden | Lulus |
| Hak akses | Operator membuka /user atau /kategori | 403 Forbidden | Lulus |

## 11. Skenario Demo untuk Asesor

1. Login sebagai **operator_ohim**. Tunjukkan dashboard dan bahwa menu Kategori dan Pengguna tidak tersedia.
2. Menu **Data Barang**: tambah barang kategori Alat Tulis, perhatikan kode otomatis **AT-002**. Coba cari "bola" dan filter kategori.
3. Menu **Barang Masuk**: catat 5 unit untuk barang tadi, lalu buka Data Barang dan tunjukkan stok bertambah.
4. Menu **Barang Keluar**: ajukan permintaan melebihi stok (muncul error), lalu ajukan jumlah yang valid dengan nama pemohon (status Pending, stok belum berubah).
5. Logout, login sebagai **admin_marco**. Dashboard menampilkan peringatan permintaan yang menunggu verifikasi.
6. Buka permintaan tadi, coba **Tolak** tanpa alasan (ditolak validasi), lalu **Setujui**: stok berkurang, nama Admin dan tanggal verifikasi tercatat.
7. Buka permintaan FR-008 (100 kursi, stok 120) dan setujui; stok menjadi 20.
8. Menu **Barang Masuk**: tunjukkan Admin dapat mengoreksi data, tetapi tidak dapat mencatat barang masuk baru.
9. Menu **Pengguna**: tambah akun Operator baru, lalu buka Ubah pada akun sendiri dan tunjukkan pilihan role terkunci serta tombol Hapus tidak tersedia.
10. Tunjukkan tombol **Cetak** pada daftar barang.
11. Di terminal jalankan `php artisan test` dan tunjukkan 50 test lulus.

Untuk mengembalikan data ke kondisi awal sebelum demo: `php artisan migrate:fresh --seed`.
