# Dokumentasi Program Inventaris Gudang Sekolah

| Keterangan | Isi |
|---|---|
| Nama Peserta | Renaissance Ricarda Sugiarto Putra |
| Skema | Pemrogram Junior (Junior Coder) |
| Aplikasi | Inventaris Gudang Sekolah (berbasis web) |
| Framework | Laravel 12 (PHP 8.2) |
| Database | MySQL / MariaDB (XAMPP) untuk lokal, `gudang_sekolah`; PostgreSQL (Supabase) untuk versi online |
| Penyimpanan gambar | Folder `storage/app/public` (lokal); Supabase Storage (online) |
| Tampilan | Blade Template + Tailwind CSS 4 (di-build dengan Vite), responsif untuk komputer dan HP |
| Pengujian | PHPUnit 11 (`php artisan test`) |

## 1. Deskripsi Program

Inventaris Gudang Sekolah adalah aplikasi web untuk mencatat data barang di gudang sekolah, mencatat barang yang masuk, dan mengelola permintaan barang keluar dengan persetujuan berjenjang. Setiap barang, transaksi, dan pengguna dapat dilengkapi **gambar/foto** (multimedia). Aplikasi menerapkan prinsip **pemisahan tugas (separation of duties)**: pengelola sistem, pencatat transaksi, dan pemberi persetujuan adalah orang yang berbeda, sehingga setiap pengeluaran barang selalu diawasi.

Pengguna terdiri dari tiga role:

- **Admin** (pengelola sistem) – mengelola akun pengguna, kategori, dan data barang, serta mengoreksi data barang masuk yang salah input.
- **Operator** (petugas gudang) – mendaftarkan barang baru, mencatat barang masuk (foto bukti opsional), dan mengajukan permintaan barang keluar atas permintaan guru/staf (foto bukti **wajib**).
- **Manager** (pengawas, misalnya kepala/waka sarpras) – memverifikasi, yaitu menyetujui atau menolak, permintaan barang keluar dan melihat seluruh data. Manager **tidak dapat** menambah, mengubah, atau mengeluarkan barang.

Aturan utama: **stok bertambah saat barang masuk dicatat**, sedangkan **stok baru berkurang setelah Manager menyetujui** permintaan barang keluar. Setiap verifikasi mencatat Manager yang memverifikasi, tanggalnya, dan catatan/alasannya.

## 2. Cara Menjalankan Program

Prasyarat: XAMPP (Apache + MySQL aktif), PHP 8.2 (ekstensi `gd` dan `fileinfo` aktif), Composer, Node.js.

1. Aktifkan MySQL di XAMPP Control Panel.
2. Buat database: `CREATE DATABASE gudang_sekolah;` (bisa lewat phpMyAdmin).
3. Salin konfigurasi: `copy .env.example .env` lalu `php artisan key:generate` (lewati jika `.env` sudah ada).
4. Pasang dependensi (jika folder `vendor`/`node_modules` belum ada): `composer install` dan `npm install`.
5. Buat tabel sekaligus isi data sampel: `php artisan migrate:fresh --seed`
6. Hubungkan folder gambar agar bisa dibuka dari browser: `php artisan storage:link` (cukup sekali).
7. Build tampilan (CSS): `npm run build`
8. Jalankan server: `php artisan serve` lalu buka `http://localhost:8000`
9. Menjalankan pengujian unit: `php artisan test`

Langkah mengunggah ke GitHub dan menjalankan aplikasi secara online (Vercel + database & penyimpanan gambar Supabase) dijelaskan di file `TUTORIAL_DEPLOY.md`.

Akun login (data sampel):

| Nama | Username | Password | Role |
|---|---|---|---|
| Ibrahim Risyad | admin_ohim | admin123 | Admin |
| Marco Ivanos | manager_marco | @admin123 | Manager |
| Siti Aminah | operator_siti | operator123 | Operator |

## 3. Spesifikasi Program

### 3.1 Hak Akses per Role

| No | Fitur | Admin | Operator | Manager |
|---|---|---|---|---|
| 1 | Login, logout, dan ubah profil sendiri (nama, foto, password) | Ya | Ya | Ya |
| 2 | Dashboard ringkasan (statistik, grafik, stok menipis, aktivitas terbaru) | Ya | Ya | Ya |
| 3 | Kelola pengguna (tambah, ubah, hapus) | Ya | Tidak | Tidak |
| 4 | Kelola kategori (tambah, ubah, hapus) | Ya | Tidak | Tidak |
| 5 | Lihat data barang, cari, filter kategori, detail riwayat | Ya | Ya | Ya |
| 6 | Tambah barang baru + gambar (kode dibuat otomatis) | Ya | Ya | Tidak |
| 7 | Ubah dan hapus data barang / gambar barang | Ya | Tidak | Tidak |
| 8 | Catat barang masuk + foto bukti opsional (stok otomatis bertambah) | Tidak | Ya | Tidak |
| 9 | Koreksi (ubah/hapus) barang masuk, stok ikut disesuaikan | Ya | Tidak | Tidak |
| 10 | Ajukan permintaan barang keluar + foto bukti wajib (status Pending) | Tidak | Ya | Tidak |
| 11 | Verifikasi barang keluar: Setujui (stok berkurang) / Tolak (wajib alasan) | Tidak | Tidak | Ya |
| 12 | Lihat daftar dan detail barang masuk / barang keluar beserta fotonya, cetak | Ya | Ya | Ya |

Alasan pembagian:

- Tiga peran terpisah tanpa tumpang tindih: Admin mengelola sistem, Operator mencatat, Manager menyetujui. Tidak ada orang yang bisa menyetujui permintaannya sendiri.
- Operator hanya **menambahkan** data. Perubahan atau penghapusan data yang sudah tercatat hanya dapat dilakukan Admin.
- Manager hanya **melihat** dan memberi persetujuan, sehingga perannya murni sebagai pengawas.
- Operator tetap boleh mendaftarkan barang baru karena barang baru harus terdaftar sebelum dicatat sebagai barang masuk.

### 3.2 Aturan Bisnis dan Validasi

- Kode barang dibuat otomatis dengan pola `PREFIX-NOMOR`, prefix sesuai kamus data: AT (Alat Tulis), FR (Furnitur), KN (Kebersihan), AO (Alat Olahraga), EK (Elektronik). Nomor melanjutkan nomor terbesar pada prefix yang sama, misalnya setelah AT-001 berikutnya AT-002.
- Kategori baru di luar kamus data memakai huruf awal dua kata pertama (contoh "Alat Laboratorium" menjadi AL).
- Stok awal hanya diisi saat barang baru dibuat. Setelah itu stok hanya berubah lewat transaksi barang masuk dan barang keluar, sehingga riwayat stok dapat dipertanggungjawabkan.
- Kondisi barang (`status_barang`) hanya boleh: Baik, Rusak Ringan, Rusak Berat.
- Verifikasi barang keluar hanya: Pending, Disetujui, Ditolak. Permintaan baru selalu Pending.
- Pemohon (nama guru/staf yang meminta) dan **foto bukti** wajib diisi saat mengajukan barang keluar.
- Gambar yang diunggah harus berformat JPG, PNG, atau WEBP dengan ukuran maksimal 2 MB. File lain ditolak.
- Jumlah barang keluar tidak boleh melebihi stok saat diajukan, dan dicek ulang saat disetujui.
- Penolakan wajib disertai alasan (`catatan_verifikasi`). Persetujuan boleh disertai catatan.
- Pengajuan barang keluar tidak dapat diubah atau dihapus (menjadi jejak audit). Pengajuan yang keliru cukup ditolak Manager dengan alasan.
- Permintaan yang sudah Disetujui/Ditolak tidak dapat diverifikasi ulang.
- Kategori yang masih dipakai barang, barang yang sudah memiliki transaksi, dan pengguna yang sudah memiliki transaksi tidak dapat dihapus.
- Admin tidak dapat menghapus akunnya sendiri atau mengubah role dirinya sendiri. Pengamanan dibuat dua lapis: pilihan role dikunci di form, dan server tetap menolak bila data role dimanipulasi.
- Di halaman Profil, setiap pengguna hanya dapat mengubah nama, foto, dan password miliknya. Mengganti password wajib memasukkan password lama dengan benar. Username dan role hanya dapat diubah Admin.
- Saat gambar diganti atau datanya dihapus, file gambar lama ikut dihapus agar penyimpanan tidak penuh oleh file yang tidak terpakai.
- Koreksi/hapus barang masuk menyesuaikan stok kembali; ditolak jika stok tersebut sudah terpakai.

### 3.3 Alur Barang Keluar

1. Guru/staf (pemohon) meminta barang kepada petugas gudang.
2. **Operator** mengisi form "Ajukan Barang Keluar" (barang, jumlah, pemohon, tujuan) dan **mengunggah foto bukti**. Status otomatis **Pending**, stok belum berubah.
3. **Manager** membuka menu Barang Keluar, filter **Pending**, lalu menekan **Verifikasi** untuk membuka detail permintaan beserta fotonya.
4. Jika **Setujui**: sistem mengecek stok, mengurangi stok, lalu menyimpan status Disetujui, `id_verifikator`, `tanggal_verifikasi`, dan catatan dalam satu transaksi database.
5. Jika **Tolak**: Manager wajib mengisi alasan. Status menjadi Ditolak, verifikator dan tanggal dicatat, stok tidak berubah.
6. Operator dapat melihat hasil verifikasi beserta alasan pada halaman detail.

### 3.4 Batasan Sistem

- Kondisi barang (`status_barang`) pada tabel barang berlaku untuk satu jenis barang, bukan per unit. Jika sebagian unit rusak, sebaiknya dicatat sebagai barang terpisah.
- Barang keluar dianggap keluar dari stok gudang, baik barang habis pakai (pulpen) maupun barang yang dipindahkan ke ruangan (proyektor, kursi). Fitur peminjaman dan pengembalian belum termasuk dalam cakupan program ini.
- Data sampel barang keluar dibuat sebelum foto bukti diwajibkan, sehingga belum memiliki foto. Pengajuan baru lewat aplikasi selalu memiliki foto.

## 4. Desain Database

Database `gudang_sekolah` berisi 5 tabel: `user`, `kategori`, `barang`, `barang_masuk`, `barang_keluar`. Tabel `migrations` dibuat otomatis oleh Laravel untuk mencatat migration yang sudah dijalankan. Session dan cache disimpan dalam file (bukan tabel) agar database tetap 5 tabel sesuai rancangan.

Gambar **tidak disimpan di dalam database**. Database hanya menyimpan lokasi file (path), misalnya `barang/aB3x...jpg`, sedangkan file gambarnya ada di folder penyimpanan (lokal) atau Supabase Storage (online). Cara ini membuat database tetap kecil dan cepat.

Relasi:

- `user` 1 : N `barang_masuk` (mengelola) – Operator yang mencatat.
- `user` 1 : N `barang_keluar` (mengelola) – Operator yang mengajukan, lewat `id_user`.
- `user` 1 : N `barang_keluar` (memverifikasi) – Manager yang memverifikasi, lewat `id_verifikator`.
- `barang` 1 : N `barang_masuk` (memasukkan) dan `barang` 1 : N `barang_keluar` (mengeluarkan).
- `kategori` 1 : N `barang` (memiliki).

Semua foreign key memakai `ON DELETE RESTRICT` agar data induk tidak bisa terhapus selama masih dipakai.

Migration dan seluruh test sudah diuji di MySQL maupun PostgreSQL. Saat memakai PostgreSQL (Supabase), migration `enable_row_level_security` mengaktifkan Row Level Security di semua tabel agar data tidak dapat dibaca lewat REST API publik Supabase; di MySQL migration tersebut tidak melakukan apa-apa.

Perubahan struktur dilakukan lewat migration baru (`2026_10_08_000001_tambah_role_manager_dan_foto`), bukan dengan mengubah migration lama. Dengan begitu, database yang sudah berjalan (misalnya di Supabase) cukup diperbarui dengan `php artisan migrate` tanpa kehilangan data.

## 5. Revisi Dokumen Rancangan

Bagian ini merangkum seluruh perubahan terhadap dokumen rancangan awal beserta alasannya, dan menyediakan isi pengganti untuk ERD, Desain Database, Database Relation, Kamus Data, dan Data Sampel.

### 5.1 Ringkasan Perubahan

| No | Rancangan Awal | Revisi | Alasan |
|---|---|---|---|
| 1 | Role Admin dan Manager | Role Admin (pengelola sistem), Operator (petugas gudang), dan Manager (pengawas/verifikator) | Peran lebih jelas dan menerapkan pemisahan tugas: pengelola sistem, pencatat transaksi, dan pemberi persetujuan adalah orang berbeda. |
| 2 | Verifikasi tidak mencatat siapa yang memverifikasi | Tambah `id_verifikator` (FK ke user) dan `tanggal_verifikasi` di `barang_keluar` | Peran Manager tercatat di database dan dapat diaudit. |
| 3 | Penolakan tanpa alasan | Tambah `catatan_verifikasi` VARCHAR(255) | Alasan penolakan dapat dibaca Operator dan pemohon. |
| 4 | Tidak ada data peminta barang | Tambah `pemohon` VARCHAR(100) di `barang_keluar` | Mengetahui siapa yang meminta barang, bukan hanya lokasi tujuan. |
| 5 | `role` VARCHAR(7) | ENUM('Admin','Operator','Manager') | Nilai role terkunci sesuai kamus data, sama seperti kolom `verifikasi`. |
| 6 | `password` VARCHAR(50) | VARCHAR(255), disimpan ter-hash bcrypt | Hash bcrypt panjangnya 60 karakter; password tidak boleh disimpan dalam teks biasa. |
| 7 | `username` tanpa batasan | UNIQUE | Username dipakai untuk login sehingga tidak boleh kembar. |
| 8 | `barang.id_barang` VARCHAR(20) berisi "AT-001" | `id_barang` INT auto increment (PK) + `kode_barang` VARCHAR(20) UNIQUE berisi "AT-001" | Primary key angka lebih efisien untuk relasi; kode tetap unik dan tampil di aplikasi. FK `id_barang` di tabel transaksi ikut menjadi INT. |
| 9 | Nama database "Gudang Sekolah" | `gudang_sekolah` | Nama database MySQL sebaiknya tanpa spasi. |
| 10 | Tidak ada fitur kelola akun | Admin dapat mengelola pengguna; semua pengguna dapat mengubah profil sendiri | Akun baru tidak lagi harus dibuat lewat database secara manual. |
| 11 | Tidak ada gambar | Tambah `user.foto`, `barang.gambar`, `barang_masuk.foto`, `barang_keluar.foto` (VARCHAR(255), berisi lokasi file) | Fitur multimedia: foto profil, gambar barang, foto bukti penerimaan (opsional), dan foto bukti permintaan (wajib). |
| 12 | Data sampel transaksi merujuk barang yang tidak ada (EK-002, AO-003, KN-005, EK-010, AO-007, FR-008) | Keenam barang ditambahkan ke data sampel barang | Tanpa itu foreign key gagal. |
| 13 | Tanggal barang keluar (2026–2027) lebih awal dari barang masuk (2029–2035); jumlah keluar 50 dan 100 melebihi stok | Tanggal diurutkan pada September–Oktober 2026; jumlah disesuaikan | Data sampel harus logis dan lolos validasi aplikasi. |
| 14 | Nilai "baik" huruf kecil | "Baik" | Disamakan dengan kamus data. |
| 15 | Dua akun sampel (Admin, Manager) | Tiga akun sampel; satu Operator ditambahkan | Setiap role memiliki akun untuk demo. |

Salah ketik pada dokumen awal yang ikut diperbaiki: "jumblah" (Database Relation), "mengelluarkan" (ERD), "id_verifikasi" (Kamus Data, seharusnya `verifikasi`), "Tabel : id_kategori" (seharusnya "Tabel : kategori"), "Iventaris", "Administratoer", "Keteranan", dan "bai" (seharusnya "baik").

### 5.2 Perubahan ERD dan Database Relation

- Entitas **pengguna**: tambahkan atribut `foto`.
- Tambahkan relasi baru **pengguna (Manager) 1 — memverifikasi — N barang keluar** (garis kedua dari pengguna ke barang keluar).
- Entitas **barang keluar**: tambahkan atribut `pemohon`, `foto`, `id_verifikator`, `tanggal_verifikasi`, `catatan_verifikasi`.
- Entitas **barang masuk**: tambahkan atribut `foto`.
- Entitas **barang**: tambahkan atribut `kode_barang` dan `gambar`; `id_barang` tetap sebagai primary key.
- Pada gambar Database Relation, tabel `barang_keluar` mendapat FK kedua `id_verifikator` yang mengarah ke `user.id_user`.

### 5.3 Desain Database (Revisi)

Tabel `user`:

| Nama Field | Tipe Data | Panjang | Keterangan |
|---|---|---|---|
| id_user | int | 11 | Primary Key, auto increment |
| nama_user | varchar | 100 | |
| username | varchar | 50 | Unique |
| password | varchar | 255 | Hash bcrypt |
| role | enum | 'Admin','Operator','Manager' | |
| foto | varchar | 255 | Baru: lokasi foto profil, boleh kosong |

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
| gambar | varchar | 255 | Baru: lokasi gambar barang, boleh kosong |

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
| foto | varchar | 255 | Baru: lokasi foto bukti penerimaan, opsional |

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
| foto | varchar | 255 | Baru: lokasi foto bukti permintaan (wajib di aplikasi) |
| verifikasi | enum | 'Pending','Disetujui','Ditolak' | Default Pending |
| id_verifikator | int | 11 | Baru: Foreign Key ke user (Manager), boleh kosong |
| tanggal_verifikasi | date | | Baru: boleh kosong |
| catatan_verifikasi | varchar | 255 | Baru: alasan/catatan, boleh kosong |

### 5.4 Kamus Data (Revisi)

Tabel `user`, field `role`:

| Data | Keterangan |
|---|---|
| Admin | Pengelola sistem: mengelola pengguna, kategori, dan data barang, serta mengoreksi barang masuk. |
| Operator | Petugas gudang: mendaftarkan barang baru, mencatat barang masuk, dan mengajukan permintaan barang keluar. |
| Manager | Pengawas: menyetujui atau menolak permintaan barang keluar dan melihat seluruh data, tanpa menambah atau mengeluarkan barang. |

Tabel `barang_keluar`, field `verifikasi`:

| Data | Keterangan |
|---|---|
| Pending | Permintaan masih menunggu verifikasi Manager |
| Disetujui | Permintaan disetujui Manager, stok dikurangi |
| Ditolak | Permintaan ditolak Manager dengan alasan, stok tidak berubah |

Kolom gambar (`user.foto`, `barang.gambar`, `barang_masuk.foto`, `barang_keluar.foto`): berisi lokasi file di folder penyimpanan, misalnya `barang-keluar/Xy12...jpg`. Format yang diterima JPG, PNG, WEBP, maksimal 2 MB.

Kamus data kategori, kode prefix barang (AT, FR, KN, AO, EK), dan `status_barang` (Baik, Rusak Ringan, Rusak Berat) tidak berubah.

### 5.5 Data Sampel (Revisi)

Tabel `user` (kolom `foto` kosong):

| id_user | nama_user | username | password | role |
|---|---|---|---|---|
| 1 | Ibrahim Risyad | admin_ohim | admin123 (tersimpan ter-hash) | Admin |
| 2 | Marco Ivanos | manager_marco | @admin123 (tersimpan ter-hash) | Manager |
| 3 | Siti Aminah | operator_siti | operator123 (tersimpan ter-hash) | Operator |

Tabel `kategori`: tidak berubah (1 Alat Tulis, 2 Furnitur, 3 Kebersihan, 4 Alat Olahraga, 5 Elektronik).

Tabel `barang` (kolom `gambar` kosong):

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

Tabel `barang_masuk` (kolom `foto` kosong):

| id_masuk | id_barang | id_user | tanggal | jumlah | sumber_barang | status_barang |
|---|---|---|---|---|---|---|
| 1 | 6 (EK-002) | 3 | 15-9-2026 | 9 | Dana BOS 2026 | Baik |
| 2 | 7 (AO-003) | 3 | 30-9-2026 | 10 | Pembelian | Baik |
| 3 | 8 (KN-005) | 3 | 1-10-2026 | 23 | Bantuan Dinas | Baik |

Tabel `barang_keluar` (kolom `foto` kosong untuk data sampel):

| id_keluar | id_barang | id_user | tanggal | jumlah | pemohon | tujuan | status_barang | verifikasi | id_verifikator | tanggal_verifikasi | catatan_verifikasi |
|---|---|---|---|---|---|---|---|---|---|---|---|
| 1 | 9 (EK-010) | 3 | 5-10-2026 | 4 | Budi Santoso (Kaprog TKJ) | Lab TKJ3 | Baik | Disetujui | 2 | 5-10-2026 | - |
| 2 | 10 (AO-007) | 3 | 6-10-2026 | 10 | Sari Wulandari (Guru PJOK) | Lab JB 3 | Baik | Ditolak | 2 | 6-10-2026 | Matras sedang dipakai untuk persiapan lomba senam. |
| 3 | 11 (FR-008) | 3 | 8-10-2026 | 100 | Andi Pratama (Wali Kelas 10 NKPI 2) | Kelas 10 NKPI 2 | Baik | Pending | - | - | - |

## 6. Struktur Program (Arsitektur MVC)

Laravel memakai pola **Model - View - Controller**. Alur satu permintaan: Browser → `routes/web.php` → Middleware (`auth`, `role`) → Form Request (validasi) → Controller → Model / Service → View (Blade) → Browser.

| Lokasi | Isi |
|---|---|
| `routes/web.php` | Daftar URL dan pembagian hak akses per role |
| `app/Http/Middleware/CekRole.php` | Membatasi halaman berdasarkan role (Admin/Operator/Manager) |
| `app/Http/Controllers/` | AuthController, DashboardController, ProfilController, UserController, KategoriController, BarangController, BarangMasukController, BarangKeluarController |
| `app/Http/Requests/` | Aturan validasi form (ProfilRequest, UserRequest, KategoriRequest, BarangRequest, BarangMasukRequest, BarangKeluarRequest) |
| `app/Models/` | Model Eloquent untuk 5 tabel beserta relasinya |
| `app/Services/StokService.php` | Satu-satunya tempat yang mengubah stok (tambah/kurangi) |
| `app/Services/GambarService.php` | Satu-satunya tempat yang menyimpan, mengganti, menghapus, dan membuat alamat gambar |
| `app/Exceptions/StokTidakCukupException.php` | Error khusus saat stok tidak mencukupi |
| `config/filesystems.php` | Lokasi penyimpanan gambar (`UPLOAD_DISK`: `public` lokal, `s3` Supabase Storage) |
| `database/migrations/` | Pembuatan 5 tabel dan perubahan strukturnya |
| `database/seeders/DatabaseSeeder.php` | Data sampel |
| `database/factories/UserFactory.php` | Pembuat data pengguna palsu untuk pengujian |
| `app/Providers/AppServiceProvider.php` | Pengaturan pagination berbahasa Indonesia dan view composer jumlah permintaan Pending |
| `resources/views/` | Tampilan Blade (layout, login, dashboard, profil, user, kategori, barang, barang-masuk, barang-keluar) |
| `resources/views/components/` | Komponen tampilan yang dipakai ulang: `icon`, `badge`, `empty`, `error`, `avatar`, `unggah-gambar` |
| `resources/views/partials/` | Potongan tampilan: pesan notifikasi (`flash`) dan navigasi halaman (`pagination`) |
| `resources/css/app.css`, `resources/js/app.js` | Token desain (warna, font), kelas komponen (`btn-primary`, `card`, `input`, ...) dan interaksi kecil (menu HP, tutup notifikasi, lihat password, pratinjau gambar) |
| `tests/Unit`, `tests/Feature` | Pengujian unit dan pengujian fitur |
| `vercel.json`, `api/index.php` | Konfigurasi hosting online di Vercel (runtime PHP, region Singapura, cache Laravel di `/tmp`) |
| `Dockerfile`, `docker/entrypoint.sh` | Konfigurasi alternatif untuk hosting berbasis Docker |

### 6.1 Desain Antarmuka

- **Konsisten**: warna, tombol, kartu, input, dan tabel memakai kelas komponen yang sama dari `resources/css/app.css`, sehingga setiap halaman terlihat seragam.
- **Bisa dipakai tanpa internet**: font *Plus Jakarta Sans* dibundel lewat Vite dan ikon berupa SVG inline (komponen `<x-icon>`), tidak memuat CDN.
- **Responsif**: di HP, sidebar berubah menjadi menu geser yang dibuka dengan tombol menu; tabel dapat digeser ke samping di dalam kartunya.
- **Sesuai role**: menu, tombol aksi, dan aksi cepat di dashboard hanya muncul bila role pengguna berhak memakainya. Jumlah permintaan Pending tampil sebagai penanda di menu Barang Keluar.
- **Dashboard**: sapaan sesuai jam, kartu statistik, grafik batang stok per kategori, komposisi status permintaan, daftar stok menipis, dan aktivitas terbaru (gabungan barang masuk dan keluar).
- **Multimedia**: foto profil tampil di menu samping, topbar, dan daftar pengguna; gambar barang tampil di daftar dan detail barang; foto bukti tampil di daftar dan detail transaksi. Saat memilih file, gambar langsung tampil sebagai pratinjau sebelum disimpan.
- **Siap cetak**: tombol Cetak menyembunyikan sidebar, filter, dan tombol aksi sehingga hanya tabel yang tercetak.

## 7. Pemetaan Unit Kompetensi

| No | Kode Unit | Judul Unit | Bukti pada Program |
|---|---|---|---|
| 1 | J.620100.004.02 | Menggunakan Struktur Data | Array konstanta `Barang::STATUS_BARANG`, `Barang::PREFIX_KATEGORI` (array asosiatif), `BarangKeluar::VERIFIKASI`, `User::ROLES`; array asosiatif bersarang `User::INFO_ROLE`; Collection Laravel (`pluck`, `map`, `max`) pada `Barang::generateKode()`; penggabungan dan pengurutan dua koleksi (`concat`, `sortByDesc`, `take`) pada aktivitas terbaru di `DashboardController`; array menu di layout; destructuring array di seeder. |
| 2 | J.620100.009.01 | Menggunakan Spesifikasi Program | Bagian 3 dokumen ini (hak akses, aturan bisnis, alur) diterapkan pada route, middleware, dan Form Request; desain database revisi (Bagian 5) diimplementasikan di migration. |
| 3 | J.620100.010.01 | Menerapkan Perintah Eksekusi Bahasa Pemrograman Berbasis Teks, Grafik, dan Multimedia | Perintah berbasis teks: `php artisan migrate:fresh --seed`, `php artisan storage:link`, `php artisan serve`, `php artisan test`, `npm run build`, `vendor/bin/pint`. Antarmuka grafis berbasis web dengan Blade + Tailwind, grafik di dashboard. Multimedia: unggah, pratinjau, tampil, ganti, dan hapus gambar (foto profil, gambar barang, foto bukti transaksi) melalui `GambarService`. |
| 4 | J.620100.016.01 | Menulis Kode dengan Prinsip sesuai Guidelines dan Best Practices | Standar PSR-12 dicek dengan Laravel Pint; pola MVC; validasi di Form Request (termasuk validasi file gambar); pemisahan tugas antar role; password di-hash bcrypt; proteksi CSRF (`@csrf`); escaping output Blade (`{{ }}` dan `@js`) untuk mencegah XSS; query Eloquent (aman dari SQL injection); `DB::transaction` dan `lockForUpdate` untuk konsistensi stok; file gambar diberi nama acak dan dibatasi jenis serta ukurannya; penamaan konsisten. |
| 5 | J.620100.017.02 | Mengimplementasikan Pemrograman Terstruktur | Percabangan (`if/elseif/else`, `match` pada komponen badge dan dashboard), perulangan (`foreach`, `@foreach`, `@forelse`), fungsi/method kecil dengan satu tugas (`tambah`, `kurangi`, `cukup`, `prefixKategori`, `generateKode`, `simpanVerifikasi`, `punyaTransaksi`, `inisial`, `salam`, `simpan`, `ganti`, `hapus`). Contoh percabangan bertingkat: `DashboardController::salam()` menentukan Selamat pagi/siang/sore/malam dari jam. |
| 6 | J.620100.023.02 | Membuat Dokumen Kode Program | Komentar PHPDoc di setiap class dan method penting (`@property`, `@throws`), komentar Blade, serta dokumen ini. |
| 7 | J.620100.025.02 | Melakukan Debugging | Bagian 9: daftar bug yang ditemukan beserta perbaikannya dan teknik debugging yang dipakai. |
| 8 | J.620100.033.02 | Melaksanakan Pengujian Unit Program | Bagian 10: 62 test (unit + fitur) dengan PHPUnit, semua lulus di SQLite dan PostgreSQL. |

## 8. Penjelasan Kode Penting

### 8.1 Login (AuthController)

`Auth::attempt(['username' => ..., 'password' => ...])` mencari user berdasarkan username lalu mencocokkan password dengan hash bcrypt. Jika cocok, session dibuat ulang (`session()->regenerate()`) untuk mencegah session fixation. Model `User` memakai cast `'password' => 'hashed'` sehingga setiap password yang disimpan otomatis di-hash.

### 8.2 Hak Akses (Middleware CekRole)

Middleware menerima daftar role yang diizinkan, misalnya `->middleware('role:Admin')`, `->middleware('role:Manager')`, atau `->middleware('role:Admin,Operator')`. Jika role pengguna tidak termasuk, sistem menampilkan error 403 (Forbidden). Di tampilan, tombol yang tidak boleh dipakai juga disembunyikan dengan `isAdmin()` / `isOperator()` / `isManager()`, dan menu Kategori serta Pengguna hanya muncul untuk Admin.

### 8.3 Kode Barang Otomatis (Barang::generateKode)

1. Ambil prefix dari nama kategori lewat `prefixKategori()` (memakai array `PREFIX_KATEGORI`).
2. Ambil semua kode barang berawalan prefix tersebut (`pluck`).
3. Ubah bagian nomor menjadi angka (`map`) dan cari nilai terbesar (`max`).
4. Tambah 1 dan format tiga digit dengan `sprintf('%s-%03d', ...)`, misalnya AO-008.

### 8.4 Pengelolaan Stok (StokService)

Seluruh perubahan stok terpusat di `StokService` agar aturan "stok tidak boleh negatif" hanya ditulis sekali. Method `kurangi()` melempar `StokTidakCukupException` jika stok kurang. Controller memanggil service di dalam `DB::transaction()`, sehingga bila terjadi error maka data transaksi dan stok sama-sama dibatalkan (rollback). `lockForUpdate()` mengunci baris barang agar dua proses bersamaan tidak membuat stok kacau.

### 8.5 Verifikasi Barang Keluar (BarangKeluarController)

Method `setujui()` dan `tolak()` hanya dapat diakses Manager. Di dalam `DB::transaction()`, baris permintaan dikunci dan statusnya dicek ulang lewat `kunciPermintaanPending()`, lalu method privat `simpanVerifikasi()` mengisi `verifikasi`, `id_verifikator` (Manager yang sedang login), `tanggal_verifikasi` (hari ini), dan `catatan_verifikasi`. Pada `setujui()`, pengurangan stok ikut berada di transaksi yang sama. Pada `tolak()`, catatan wajib diisi.

### 8.6 Validasi (Form Request)

Contoh `BarangKeluarRequest`: aturan dasar (`required`, `integer`, `min:1`, `exists:barang,id_barang`, `Rule::in(Barang::STATUS_BARANG)`), aturan foto (`required`, `image`, `mimes:jpg,jpeg,png,webp`, `max:2048`), lalu validasi tambahan di method `after()` yang mengecek jumlah tidak melebihi stok. `ProfilRequest` memakai aturan `current_password` agar password lama harus benar sebelum diganti. Pesan error tampil di bawah setiap input melalui komponen `<x-error>`.

### 8.7 Unggah Gambar (GambarService)

1. Form memakai `enctype="multipart/form-data"` agar file ikut terkirim; komponen `<x-unggah-gambar>` menampilkan pratinjau dengan JavaScript sebelum disimpan.
2. Form Request memeriksa jenis dan ukuran file (`GambarService::ATURAN`).
3. `GambarService::simpan()` menyimpan file dengan **nama acak** ke folder sesuai jenisnya (`profil`, `barang`, `barang-masuk`, `barang-keluar`) dan mengembalikan lokasinya; lokasi inilah yang disimpan di database.
4. Model menyediakan alamat gambar siap pakai (`$barang->gambar_url`, `$user->foto_url`, `$transaksi->foto_url`) lewat accessor.
5. Saat gambar diganti (`ganti()`) atau data dihapus, file lama dihapus. Pada barang masuk, file baru disimpan sebelum transaksi database; bila transaksi gagal, file baru dihapus kembali dan file lama tetap utuh.
6. Lokasi penyimpanan cukup diatur lewat `UPLOAD_DISK`: `public` (folder lokal) atau `s3` (Supabase Storage), tanpa mengubah kode.

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
| 12 | `BarangKeluarController::setujui()` | Status Pending dicek di luar transaksi database (*race condition*) | Bila dua orang menekan Setujui hampir bersamaan, keduanya lolos pengecekan dan stok berkurang dua kali | Baris permintaan dikunci dengan `lockForUpdate()` lalu statusnya dicek ulang di dalam `DB::transaction()` (method `kunciPermintaanPending`) |
| 13 | Migration `tambah_role_manager_dan_foto` | Mengubah pilihan enum `role` dengan `->change()` menghasilkan SQL tidak valid di PostgreSQL (`syntax error at or near "check"`) | Database online (Supabase) gagal diperbarui, padahal di MySQL berhasil | Di PostgreSQL, CHECK constraint `user_role_check` dihapus lalu dibuat ulang secara manual; di MySQL/SQLite tetap memakai `->change()`. Diuji dari skema lama, rollback, dan upgrade ulang |

### 9.2 Teknik Debugging yang Digunakan

- Membaca pesan error dan stack trace pada halaman error Laravel (`APP_DEBUG=true` di `.env`, wajib `false` saat produksi).
- Membaca file log `storage/logs/laravel.log`.
- `dd($variabel)` / `dump()` untuk memeriksa isi variabel di tengah proses.
- `php artisan route:list` untuk memastikan route dan nama route terdaftar.
- `php artisan tinker` untuk mencoba query model secara langsung.
- Menjalankan `php artisan test` setelah setiap perubahan, dan menjalankannya berulang kali untuk mendeteksi test yang hasilnya tidak konsisten (kasus bug no. 10).
- Menguji migration di database yang sama dengan server online (PostgreSQL), termasuk dari kondisi skema lama, sebelum di-deploy (kasus bug no. 13).

## 10. Pengujian Unit Program

Pengujian memakai PHPUnit dengan database SQLite di memori (konfigurasi `phpunit.xml`), sehingga data asli di MySQL tidak terganggu. Setiap test memakai `RefreshDatabase` agar mulai dari database kosong. File yang diunggah selama pengujian disimpan di penyimpanan palsu (`Storage::fake()`), dan gambar uji dibuat dengan `UploadedFile::fake()->image()`.

Perintah: `php artisan test`

| File | Yang Diuji | Jumlah Test |
|---|---|---|
| `tests/Unit/StokServiceTest.php` | Tambah stok, kurangi stok, stok habis, stok tidak cukup (exception), stok tidak berubah saat gagal, jumlah nol ditolak, cek stok cukup | 7 |
| `tests/Unit/KodeBarangTest.php` | Prefix sesuai kamus data, prefix kategori baru, kode pertama 001, kode melanjutkan nomor terbesar | 5 |
| `tests/Feature/AuthTest.php` | Halaman login, login berhasil, login gagal, password ter-hash, tamu diarahkan ke login, logout | 6 |
| `tests/Feature/HakAksesTest.php` | Halaman daftar & profil untuk ketiga role, Operator dilarang mengakses fitur Admin, Operator dilarang mengoreksi barang masuk, Admin & Manager dilarang mencatat transaksi, Manager hanya bisa melihat, hanya Manager yang bisa memverifikasi | 6 |
| `tests/Feature/UserTest.php` | Tambah Operator (password ter-hash), username unik & role valid, ubah tanpa ganti password, Admin tidak bisa ubah role/hapus akun sendiri, pilihan role terkunci di form akun sendiri, pengguna bertransaksi tidak bisa dihapus, pengguna tanpa transaksi bisa dihapus | 7 |
| `tests/Feature/ProfilTest.php` | Ubah nama & unggah foto profil, ganti foto menghapus foto lama, hapus foto, ganti password wajib password lama benar, profil tidak bisa mengubah role/username | 5 |
| `tests/Feature/KategoriBarangTest.php` | Tambah kategori, validasi unik & maks 20 karakter, kategori terpakai tidak bisa dihapus, kode otomatis, ubah barang tidak mengubah stok, validasi status, pencarian, unggah/ganti/hapus gambar barang | 8 |
| `tests/Feature/TransaksiBarangTest.php` | Barang masuk menambah stok, koreksi/hapus barang masuk, hapus ditolak bila stok terpakai, jumlah minimal 1, pengajuan Pending, pemohon wajib, melebihi stok ditolak, setujui mencatat verifikator, tolak wajib alasan, persetujuan gagal saat stok kurang, tidak bisa verifikasi ulang, detail menampilkan pengaju & verifikator, foto keluar wajib, foto tersimpan & tampil, file bukan gambar / lebih dari 2 MB ditolak, foto barang masuk opsional & ikut terhapus, foto baru dihapus bila koreksi gagal | 18 |

Hasil terakhir: **Tests: 62 passed (203 assertions)**, lulus di SQLite (lokal) dan PostgreSQL (seperti database online).

Contoh test case:

| Skenario | Langkah | Hasil yang Diharapkan | Hasil |
|---|---|---|---|
| Manager menyetujui | Stok 10, Operator ajukan keluar 4, Manager setujui | Verifikasi = Disetujui, id_verifikator = Manager, tanggal hari ini, stok = 6 | Lulus |
| Foto wajib | Operator mengajukan barang keluar tanpa foto | Error validasi pada field foto, data tidak tersimpan | Lulus |
| File tidak sah | Operator mengunggah PDF / gambar 3 MB | Error validasi, data tidak tersimpan | Lulus |
| Tolak tanpa alasan | Manager menekan Tolak tanpa catatan | Error validasi, status tetap Pending | Lulus |
| Stok tidak cukup | Stok 3, permintaan 100, Manager setujui | Pesan gagal, status tetap Pending, stok tetap 3 | Lulus |
| Hak akses | Admin / Operator menekan Setujui | 403 Forbidden | Lulus |
| Manager hanya melihat | Manager membuka form tambah barang | 403 Forbidden | Lulus |

## 11. Skenario Demo untuk Asesor

1. Login sebagai **operator_siti**. Tunjukkan dashboard dan bahwa menu Kategori dan Pengguna tidak tersedia.
2. Menu **Profil Saya**: unggah foto profil, lalu tunjukkan foto langsung tampil di menu samping.
3. Menu **Data Barang**: tambah barang kategori Alat Tulis beserta gambarnya, perhatikan kode otomatis **AT-002** dan pratinjau gambar sebelum disimpan.
4. Menu **Barang Masuk**: catat 5 unit untuk barang tadi (boleh dengan foto nota), lalu tunjukkan stok bertambah.
5. Menu **Barang Keluar**: coba ajukan tanpa foto (ditolak), lalu ajukan dengan foto bukti (status Pending, stok belum berubah).
6. Logout, login sebagai **manager_marco**. Tunjukkan Manager tidak punya tombol Tambah Barang dan tidak bisa mencatat transaksi.
7. Buka permintaan tadi: tunjukkan foto bukti, coba **Tolak** tanpa alasan (ditolak), lalu **Setujui** (stok berkurang, nama Manager dan tanggal tercatat).
8. Logout, login sebagai **admin_ohim**. Menu **Pengguna**: tunjukkan tiga role; buka Ubah pada akun sendiri dan tunjukkan pilihan role terkunci.
9. Menu **Barang Masuk**: tunjukkan Admin dapat mengoreksi data, tetapi tidak dapat mencatat barang masuk baru.
10. Tunjukkan tombol **Cetak** pada daftar barang.
11. Di terminal jalankan `php artisan test` dan tunjukkan 62 test lulus.

Untuk mengembalikan data ke kondisi awal sebelum demo: `php artisan migrate:fresh --seed`.
