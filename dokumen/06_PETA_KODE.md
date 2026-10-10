# Peta Kode Program

| Keterangan | Isi |
|---|---|
| Aplikasi | Inventaris Gudang Sekolah |
| Kegunaan dokumen | Contekan untuk menjawab pertanyaan asesor: "fungsi X ada di mana?", "array-nya di mana?", "fungsi ini untuk apa?" |
| Cara membaca | `file:baris` artinya nama file dan nomor baris. Buka file di VS Code lalu tekan `Ctrl+G` dan ketik nomor barisnya. |

<!-- daftar-isi -->

<!-- halaman-baru -->

## 1. Alur Satu Permintaan (Request)

Setiap kali tombol diklik atau form dikirim, kode dijalankan berurutan seperti ini:

| Urutan | Bagian | Lokasi | Tugas |
|---|---|---|---|
| 1 | Route | `routes/web.php` | Menentukan URL mana memanggil controller dan fungsi apa |
| 2 | Middleware | `auth` (bawaan Laravel), `role` → `app/Http/Middleware/CekRole.php` | Memeriksa sudah login atau belum, dan role-nya boleh atau tidak |
| 3 | Form Request | `app/Http/Requests/*.php` | Memvalidasi isian form sebelum masuk controller |
| 4 | Controller | `app/Http/Controllers/*.php` | Mengatur alur: ambil data, panggil service, kirim ke view |
| 5 | Service | `app/Services/*.php` | Aturan bisnis yang dipakai berulang (stok, gambar) |
| 6 | Model | `app/Models/*.php` | Mewakili tabel database (Eloquent ORM) |
| 7 | View | `resources/views/**/*.blade.php` | Tampilan HTML yang dikirim ke browser |

Contoh: Operator mengajukan barang keluar → `POST /barang-keluar` (web.php:43) → middleware `auth` + `role:Operator` → `BarangKeluarRequest` memeriksa isian dan stok → `BarangKeluarController@store` menyimpan foto lewat `GambarService::simpan()` lalu `BarangKeluar::create()` → kembali ke daftar dengan pesan sukses.

## 2. Peta Folder

| Folder / File | Isi |
|---|---|
| `routes/web.php` | Semua URL aplikasi (38 route) dan pembagian hak akses per role |
| `app/Http/Controllers/` | 8 controller: Auth, Dashboard, Profil, User, Kategori, Barang, BarangMasuk, BarangKeluar |
| `app/Http/Requests/` | 6 class validasi form |
| `app/Http/Middleware/CekRole.php` | Pembatas akses berdasarkan role |
| `app/Models/` | 5 model: User, Kategori, Barang, BarangMasuk, BarangKeluar |
| `app/Services/` | `StokService` (ubah stok) dan `GambarService` (simpan/hapus gambar) |
| `app/Support/Suasana.php` | Penentu suasana pagi/siang/sore/malam (salam, animasi login, audio) |
| `app/Exceptions/StokTidakCukupException.php` | Error khusus saat stok kurang |
| `app/Providers/AppServiceProvider.php` | Pengaturan awal aplikasi (https, pagination, jumlah Pending) |
| `bootstrap/app.php` | Pendaftaran middleware `role` dan penanganan error 419 |
| `database/migrations/` | 7 file pembuat tabel |
| `database/seeders/DatabaseSeeder.php` | Data sampel (3 akun, 5 kategori, 11 barang, transaksi) |
| `resources/views/` | Tampilan Blade (layout, halaman, komponen) |
| `resources/css/app.css` | Gaya tampilan (Tailwind CSS), animasi login, mode gelap |
| `resources/js/` | `app.js` (Turbo & interaksi umum), `popup.js` (popup & panel kanan), `tema.js` (mode gelap), `suasana.js` (salam, jam & audio login), `musik.js` (musik Bad Apple!!) |
| `public/audio/` | 4 file suara suasana (`pagi.wav`, `siang.wav`, `sore.wav`, `malam.wav`) dan lagu `bad-apple.mp3` |
| `public/images/siluet.png` | Gambar siluet karakter (login dan banner dashboard) |
| `public/images/latar-karakter.png` | Karakter tersenyum bersayap untuk latar halaman aplikasi |
| `public/images/penyihir.png` | Penyihir terbang di banner dashboard |
| `public/images/karakter-sidebar.png` | Karakter samar di latar sidebar |
| `tests/` | Pengujian otomatis (Feature dan Unit) |
| `api/index.php` | Pintu masuk saat di-hosting di Vercel |

## 3. Daftar Fungsi per File

### 3.1 Route dan Middleware

| Lokasi | Fungsi / Bagian | Untuk apa |
|---|---|---|
| `routes/web.php:13` | `Route::redirect('/', '/login')` | Alamat utama langsung diarahkan ke halaman login |
| `routes/web.php:16` | grup `guest` | Halaman login hanya untuk yang belum login |
| `routes/web.php:21` | grup `auth` | Semua halaman lain wajib login |
| `routes/web.php:33` | grup `role:Admin,Operator` | Tambah barang |
| `routes/web.php:39` | grup `role:Operator` | Catat barang masuk, ajukan barang keluar |
| `routes/web.php:47` | grup `role:Admin` | Kelola pengguna, kategori, ubah/hapus barang, koreksi barang masuk |
| `routes/web.php:56` | grup `role:Manager` | Setujui / tolak barang keluar |
| `routes/web.php:62-66` | tanpa grup role | Halaman lihat data, untuk semua role |
| `CekRole.php:17` | `handle($request, $next, ...$roles)` | Bila role pengguna tidak ada di daftar `$roles`, tampilkan error 403. `...$roles` = parameter variadic (bisa banyak role) |
| `bootstrap/app.php:17` | `$middleware->alias(['role' => CekRole::class])` | Mendaftarkan nama pendek `role` agar bisa ditulis `role:Admin` di route |
| `bootstrap/app.php:30` | `$exceptions->render(...)` | Error 419 (Page Expired) diganti pesan jelas dan isian form dikembalikan |

### 3.2 Controller

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| `AuthController.php:16` | `index()` | Menampilkan halaman login beserta suasana waktu (`Suasana::dariJam()`) |
| `AuthController.php:25` | `login()` | Validasi username & password, `Auth::attempt()` mencocokkan hash password, lalu `session()->regenerate()` |
| `AuthController.php:44` | `logout()` | Keluar, hapus sesi, buat token CSRF baru |
| `DashboardController.php:18` | `index()` | Menghitung ringkasan (jumlah barang, total stok, dll.), stok per kategori, status permintaan, stok menipis |
| `DashboardController.php:59` | `aktivitasTerbaru()` | Menggabungkan barang masuk & keluar terbaru menjadi satu daftar urut tanggal |
| `ProfilController.php:20` | `edit()` | Form profil milik pengguna yang login |
| `ProfilController.php:25` | `update()` | Simpan nama, ganti/hapus foto, ganti password (bila diisi); dari panel kanan kembali ke halaman asal |
| `UserController.php:17` | `index()` | Daftar pengguna + jumlah transaksinya (`withCount`) |
| `UserController.php:27` / `:32` | `create()` / `store()` | Form dan simpan pengguna baru |
| `UserController.php:39` / `:44` | `edit()` / `update()` | Ubah pengguna; password kosong = tidak diganti; Admin tidak bisa menurunkan role dirinya sendiri (baris 54) |
| `UserController.php:63` | `destroy()` | Hapus pengguna, kecuali diri sendiri atau yang sudah punya transaksi |
| `KategoriController.php:15-46` | `index`, `create`, `store`, `edit`, `update`, `destroy` | CRUD kategori; kategori yang masih dipakai barang tidak bisa dihapus (baris 49) |
| `BarangController.php:23` | `index()` | Daftar barang dengan pencarian nama/kode (`whereLike`) dan filter kategori |
| `BarangController.php:54` | `store()` | Simpan barang baru; kode dibuat otomatis `Barang::generateKode()`; gambar disimpan `GambarService` |
| `BarangController.php:69` | `show()` | Detail barang beserta riwayat masuk & keluar |
| `BarangController.php:88` | `update()` | Ubah barang; ganti kategori = kode dibuat ulang (baris 101) |
| `BarangController.php:110` | `destroy()` | Hapus barang, ditolak bila sudah punya riwayat transaksi |
| `BarangMasukController.php:32` | `index()` | Daftar barang masuk dengan filter tanggal dari–sampai |
| `BarangMasukController.php:56` | `store()` | Catat barang masuk + tambah stok dalam satu `DB::transaction` |
| `BarangMasukController.php:83` | `update()` | Koreksi oleh Admin; stok disesuaikan sebesar selisih jumlah (baris 99-113) |
| `BarangMasukController.php:131` | `destroy()` | Hapus data masuk dan kembalikan stok |
| `BarangKeluarController.php:34` | `index()` | Daftar permintaan dengan filter status verifikasi |
| `BarangKeluarController.php:63` | `store()` | Operator mengajukan; status = Pending; foto bukti wajib |
| `BarangKeluarController.php:84` | `setujui()` | Manager menyetujui → stok dikurangi `StokService::kurangi()` |
| `BarangKeluarController.php:115` | `tolak()` | Manager menolak → stok tetap, alasan wajib |
| `BarangKeluarController.php:147` | `kunciPermintaanPending()` | `lockForUpdate()` mengunci baris agar 2 Manager tidak menyetujui bersamaan (stok tidak terpotong dua kali) |
| `BarangKeluarController.php:157` | `simpanVerifikasi()` | Mencatat hasil, id Manager, tanggal, dan catatan verifikasi |

Fungsi `__construct(private StokService $stok, ...)` di BarangController, BarangMasukController, BarangKeluarController, ProfilController adalah **dependency injection**: Laravel otomatis membuatkan objek service dan memasukkannya ke controller.

### 3.3 Form Request (Validasi)

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| Semua `*Request.php` | `authorize()` | Selalu `true`, karena hak akses sudah diatur middleware di route |
| Semua `*Request.php` | `rules()` | Mengembalikan **array aturan validasi** per kolom |
| Semua `*Request.php` | `attributes()` | Mengembalikan **array nama kolom** yang ramah dibaca pada pesan error |
| `BarangKeluarRequest.php:38` | `after()` | Validasi tambahan: jumlah tidak boleh melebihi stok |
| `BarangRequest.php:35` | `if ($this->isMethod('post'))` | Stok awal hanya diisi saat tambah barang, bukan saat ubah |
| `UserRequest.php:28` | `Rule::unique(...)->ignore(...)` | Username unik, kecuali milik pengguna yang sedang diubah |
| `ProfilRequest.php:25` | `current_password` | Password lama harus benar sebelum ganti password |

### 3.4 Model

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| `User.php:84` | `casts()` | `'password' => 'hashed'`: password otomatis di-hash bcrypt |
| `User.php:92/105/110` | `isAdmin()`, `isOperator()`, `isManager()` | Cek role, mengembalikan `true/false` |
| `User.php:98` | `inisial()` | "Marco Ivanos" → "MI" untuk avatar tanpa foto |
| `User.php:116` | `fotoUrl()` | Accessor: `$user->foto_url` = alamat foto profil |
| `User.php:122/128/134` | `barangMasuk()`, `barangKeluar()`, `verifikasiBarangKeluar()` | Relasi 1 pengguna → banyak transaksi (`hasMany`) |
| `User.php:140` | `punyaTransaksi()` | Cek apakah pengguna sudah punya transaksi (tidak boleh dihapus) |
| `Barang.php:68` | `gambarUrl()` | Accessor: `$barang->gambar_url` |
| `Barang.php:73` | `kategori()` | Relasi barang → kategori (`belongsTo`) |
| `Barang.php:78/83` | `barangMasuk()`, `barangKeluar()` | Relasi barang → transaksi (`hasMany`) |
| `Barang.php:94` | `prefixKategori()` | Nama kategori → prefix kode, contoh "Alat Tulis" → "AT" |
| `Barang.php:115` | `generateKode()` | Membuat kode berikutnya, contoh sudah ada AT-004 → AT-005 |
| `Barang.php:127` | `stokMenipis()` | `true` bila stok ≤ 5 |
| `BarangKeluar.php:83/89/95` | `barang()`, `user()`, `verifikator()` | Relasi ke barang, pengaju, dan Manager pemverifikasi |
| `BarangKeluar.php:100` | `isPending()` | Cek status masih Pending |
| `BarangMasuk.php:58/63` | `barang()`, `user()` | Relasi ke barang dan pencatat |
| `Kategori.php:25` | `barang()` | Relasi 1 kategori → banyak barang |

### 3.5 Service, Support, dan Exception

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| `StokService.php:22` | `tambah($barang, $jumlah)` | Stok bertambah (barang masuk) |
| `StokService.php:35` | `kurangi($barang, $jumlah)` | Stok berkurang; bila tidak cukup lempar `StokTidakCukupException` |
| `StokService.php:49` | `cukup()` | Cek stok ≥ jumlah |
| `StokService.php:54` | `pastikanJumlahValid()` | Jumlah ≤ 0 ditolak (`InvalidArgumentException`) |
| `GambarService.php:24` | `disk()` | Memilih tempat simpan: `public` (lokal) atau `s3` (Supabase) |
| `GambarService.php:35` | `simpan()` | Simpan file dengan nama acak, kembalikan path-nya; bila gagal lempar `GambarGagalDisimpanException` dan catat penyebabnya ke log |
| `GambarService.php:55` | `ganti()` | Simpan file baru lalu hapus file lama |
| `GambarService.php:67` | `hapus()` | Hapus file bila ada; gagal menghapus hanya dicatat di log |
| `GambarService.php:84` | `url()` | Alamat gambar untuk tag `<img>` |
| `Suasana.php:46` | `dariJam($jam)` | Jam 0-23 → `pagi` / `siang` / `sore` / `malam` |
| `Suasana.php:61` | `salam($jam)` | Jam → "Selamat pagi", dst. (dipakai Dashboard) |
| `Suasana.php:69` | `untukBrowser()` | Data suasana + alamat file audio untuk JavaScript login |
| `GambarGagalDisimpanException.php:15` | `__construct()` | Pesan "Gambar gagal disimpan ke penyimpanan…" |
| `StokTidakCukupException.php:13` | `__construct()` | Menyusun pesan "Stok X tidak mencukupi..." |
| `AppServiceProvider.php:18` | `boot()` | Paksa https di produksi, pakai tampilan pagination Indonesia, hitung jumlah Pending untuk sidebar (`View::composer`) |
| `DatabaseSeeder.php:21` | `run()` | Mengisi data sampel |

### 3.6 JavaScript

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| `resources/js/app.js:8` | `import * as Turbo` | Memasang **Turbo**: pindah menu dan kirim form tanpa memuat ulang halaman |
| `app.js:24` | `simpanSidebar()` | Menyimpan posisi sidebar (terbuka / tertutup) di `localStorage` |
| `app.js:32` | `bukaSidebarHp(buka)` | Buka/tutup menu geser di HP |
| `app.js:41` | `[data-sidebar-toggle]` | Tombol burger: layar besar menutup/membuka sidebar, HP membuka menu geser |
| `app.js:55` | `[data-dismiss]` | Menutup pesan notifikasi |
| `app.js:62` | `[data-toggle-password]` | Tampilkan / sembunyikan password |
| `app.js:74` | `[data-pratinjau]` | Pratinjau gambar sebelum diunggah + cek ukuran maks 2 MB |
| `app.js:103` | `turbo:load` | Dijalankan setiap halaman baru tampil (termasuk lewat Turbo) |
| `popup.js:29` | `siapkanFrame()` | Membuat `<turbo-frame>` "modal" dan "laci" di dalam dialog |
| `popup.js:40` | `pasangPopup()` | Mengatur popup dan panel kanan: buka saat isi dimuat, tutup dengan X / Esc / klik di luar |
| `popup.js:46` | `turbo:before-fetch-request` | Permintaan dari popup memakai alamat popup sebagai Referer, dan mengirim alamat halaman asal (`X-Halaman-Asal`) |
| `popup.js:68` | `turbo:frame-missing` | Setelah simpan berhasil: tutup popup lalu tampilkan halaman daftar beserta pesan sukses |
| `tema.js:9` | `simpanTema()` | Simpan pilihan `terang` / `gelap` di `sessionStorage` (berlaku selama tab terbuka) |
| `tema.js:17` | `tukarTema()` | Tukar class `dark` pada `<html>` dan simpan pilihan |
| `tema.js:27` | `gantiTema(tombol)` | Menjalankan pergantian tema dengan efek lingkaran (View Transitions API) |
| `tema.js:52` | `pasangTombolTema()` | Pasang klik tombol tema (event delegation); saat dicetak paksa mode terang |
| `suasana.js:10` | `waktuDariJam()` | Sama dengan `Suasana::dariJam()` tetapi memakai jam perangkat |
| `suasana.js:23` | `pasangSuasanaLogin()` | Mengatur halaman login: jam berjalan, salam, warna cahaya apel, audio |
| `suasana.js:44` | `terapkan(waktu)` | Ganti `data-waktu`, teks salam, kalimat, dan file audio |
| `suasana.js:64` | `perbaruiJam()` | Dipanggil tiap 1 detik (`setInterval`) untuk jam dan pergantian suasana |
| `suasana.js:72` | `putar()` | Memutar audio dengan volume naik perlahan (fade in) |
| `suasana.js:86` | `perbaruiTombolAudio()` | Mengganti ikon dan label tombol suara |
| `musik.js:46` | `siapkanAudio()` | Memasang event pada elemen audio dan melanjutkan lagu setelah halaman dimuat ulang |
| `musik.js:66` | `pasangMusik()` | Tombol musik (putar / jeda) dan penyimpanan posisi lagu |
| `partials/tema-awal.blade.php` | skrip kecil di `<head>` | Menentukan tema (pilihan pengguna atau ikut jam) dan posisi sidebar sebelum halaman tampil |

**Event delegation**: karena Turbo mengganti isi halaman tanpa memuat ulang, klik dipasang sekali di `document` lalu dicek dengan `event.target.closest('[data-...]')`. Dengan begitu tombol di halaman baru tetap berfungsi tanpa dipasang ulang.

## 4. Daftar Array dan Konstanta

| Lokasi | Nama | Jenis array | Isi / Kegunaan |
|---|---|---|---|
| `User.php:32` | `ROLES` | Array biasa (indeks) | `['Admin', 'Operator', 'Manager']`, dipakai validasi role |
| `User.php:38` | `INFO_ROLE` | Array asosiatif bersarang | Per role: ikon, warna badge, keterangan tugas (halaman Pengguna) |
| `User.php:72` | `$fillable` | Array biasa | Kolom yang boleh diisi massal (mencegah *mass assignment*) |
| `User.php:80` | `$hidden` | Array biasa | `password` disembunyikan saat model diubah ke JSON |
| `Barang.php:28` | `STATUS_BARANG` | Array biasa | `['Baik', 'Rusak Ringan', 'Rusak Berat']` |
| `Barang.php:31` | `PREFIX_KATEGORI` | Array asosiatif | `'Alat Tulis' => 'AT'`, dst. (Kamus Data) |
| `Barang.php:48` | `$fillable` | Array biasa | Kolom barang yang boleh diisi |
| `BarangKeluar.php:38` | `VERIFIKASI` | Array biasa | `['Pending', 'Disetujui', 'Ditolak']` |
| `GambarService.php:20` | `ATURAN` | Array biasa | Aturan validasi gambar: jenis file dan maks 2 MB; disisipkan ke Request dengan operator spread `...` |
| `Suasana.php:16` | `DAFTAR` | Array asosiatif bersarang | Per waktu: jam mulai, salam, kalimat, file audio |
| `DashboardController.php:20` | `$ringkasan` | Array asosiatif | 4 angka kartu statistik dashboard |
| `DatabaseSeeder.php:34` | `$daftarBarang` | Array 2 dimensi | 11 barang sampel; dibongkar dengan *destructuring* `[$kode, $idKategori, ...]` |
| `api/index.php:11` | `$pengaturanVercel`, `$bawaan` | Array asosiatif | Pengaturan khusus hosting Vercel |
| `layouts/app.blade.php:46` | `$menu` | Array asosiatif bersarang | Struktur menu sidebar: grup → [route, pola aktif, label, ikon, khusus admin, badge] |
| `dashboard.blade.php:10` | `$aksiCepat` | Array 2 dimensi (dari `match`) | Tombol aksi cepat berbeda per role |
| `dashboard.blade.php:58` | (langsung di `@foreach`) | Array 2 dimensi | 4 kartu statistik: judul, angka, ikon, warna |
| `dashboard.blade.php:115` | `$warnaStatus` | Array asosiatif | Warna setiap status verifikasi |
| `components/icon.blade.php:9` | `$ikon` | Array asosiatif | Nama ikon → data gambar SVG |
| `components/badge.blade.php:5` | hasil `match` | Array (destructuring) | Warna label sesuai nilai status |
| `partials/flash.blade.php:2` | (langsung di `@foreach`) | Array asosiatif bersarang | Gaya pesan `sukses` dan `gagal` |
| `auth/login.blade.php:23` | (langsung di `@foreach`) | Array 2 dimensi | Posisi, jeda, durasi, dan ukuran 4 apel yang jatuh |
| `auth/login.blade.php:32` | (langsung di `@foreach`) | Array 2 dimensi | Posisi, jeda, dan durasi 10 partikel cahaya yang melayang |
| Semua `*Request.php` | hasil `rules()` | Array asosiatif | Kolom → daftar aturan validasi |

## 5. Struktur Kontrol (Percabangan dan Perulangan)

| Konsep | Contoh lokasi | Penjelasan |
|---|---|---|
| `if / else` | `UserController.php:49` | Password kosong → tidak diganti |
| `if / elseif` | `BarangMasukController.php:104` | Selisih positif → tambah stok, negatif → kurangi |
| `match` (PHP 8) | `dashboard.blade.php:10`, `badge.blade.php:5` | Pilih nilai berdasarkan role / status, lebih ringkas dari `switch` |
| `foreach` | `Suasana.php:49`, `DatabaseSeeder.php:50` | Mengulang isi array |
| `@foreach` / `@forelse` (Blade) | `dashboard.blade.php:89` | Mengulang data di tampilan; `@forelse` punya bagian `@empty` bila data kosong |
| `for ... of` (JS) | `suasana.js:12` | Mengulang data suasana di JavaScript |
| `try / catch` | `BarangKeluarController.php:88`, `BarangMasukController.php:92` | Menangkap error stok kurang dan menghapus file yang terlanjur disimpan |
| `throw` | `StokService.php:40` | Melempar error bila stok tidak cukup |
| Operator ternary `? :` | `BarangMasukController.php:60` | Foto ada → simpan, tidak ada → `null` |
| Null-safe `?->` | `UserRequest.php:22` | Ambil id hanya bila data ada |

## 6. Konsep OOP di Kode

| Konsep | Contoh | Penjelasan singkat |
|---|---|---|
| Class & objek | `class StokService` | Cetakan; Laravel membuat objeknya otomatis |
| Pewarisan (*inheritance*) | `class Barang extends Model`, `User extends Authenticatable`, `StokTidakCukupException extends RuntimeException` | Mewarisi kemampuan class induk |
| Enkapsulasi | `private function pastikanJumlahValid()` (StokService:54), `private function kunciPermintaanPending()` | Hanya bisa dipanggil dari dalam class itu sendiri |
| Konstanta class | `User::ROLE_ADMIN`, `BarangKeluar::PENDING` | Nilai tetap, menghindari salah ketik |
| Method statis | `Barang::generateKode()`, `Suasana::dariJam()` | Dipanggil tanpa membuat objek |
| *Dependency injection* | `__construct(private StokService $stok)` | Objek service dimasukkan otomatis oleh Laravel |
| *Constructor property promotion* | `private StokService $stok` di parameter constructor | Fitur PHP 8: deklarasi properti sekaligus di constructor |

## 7. Fitur Tambahan Terbaru

### 7.0 Tanpa Pindah Halaman, Popup, dan Panel Profil (Turbo)

| Bagian | Lokasi | Cara kerja |
|---|---|---|
| Turbo Drive | `resources/js/app.js:8` | Library **Hotwire Turbo**. Saat menu diklik atau form dikirim, halaman diambil di belakang layar lalu isi `<body>` diganti; tidak ada muat ulang penuh, musik tidak terputus |
| Mode popup di layout | `layouts/app.blade.php:1-29` | Bila permintaan membawa header `Turbo-Frame: modal` / `laci` **dan** halamannya punya `@section('popup')`, layout hanya mengirim isi halaman di dalam `<turbo-frame>` tanpa sidebar |
| Halaman yang bisa jadi popup | `barang/form`, `barang/show`, `barang-masuk/form`, `barang-keluar/form`, `barang-keluar/show`, `kategori/form`, `user/form`, `profil/edit` | Diberi `@section('popup', true)`. Bila dibuka langsung (tanpa Turbo), halaman tetap tampil utuh seperti biasa |
| Tautan pembuka popup | atribut `data-turbo-frame="modal"` di tombol Tambah, Ubah, Detail | Isi halaman dimuat ke popup tengah |
| Panel profil kanan | foto profil di topbar dan kartu pengguna di sidebar (`data-turbo-frame="laci"`) | Menu "Profil Saya" diganti panel yang muncul dari kanan |
| Wadah popup | `<dialog id="dialog-modal">` dan `<dialog id="dialog-laci">` di layout, frame dibuat `popup.js` | Elemen `<dialog>` bawaan browser: bisa ditutup dengan Esc, latar belakang digelapkan |
| Validasi gagal | `popup.js:46` | Laravel kembali ke form di dalam popup dengan pesan error |
| Simpan berhasil | `popup.js:68` | Server mengarahkan ke halaman daftar; popup ditutup dan halaman daftar tampil dengan pesan sukses |
| Simpan profil | `ProfilController::update()` | Kembali ke halaman yang sedang dibuka (header `X-Halaman-Asal`, hanya alamat aplikasi sendiri) agar nama dan foto di sidebar ikut berubah |
| Tombol burger | `app.js:41`, `app.css:456` (`html.sidebar-tutup`) | Layar besar: sidebar ditutup/dibuka, isi halaman melebar; posisi diingat di `localStorage`. HP: membuka menu geser |
| Latar karakter | `public/images/latar-karakter.png`, `app.css:470` (`.latar-karakter`), `layouts/app.blade.php:65` | Karakter tersenyum bersayap, besar di tengah area isi. Warnanya kebalikan latar seperti di login: hitam di mode terang, putih di mode gelap |
| Percikan cahaya | `layouts/app.blade.php:68` (34 titik), `app.css:496` (`.percikan`) | Titik cahaya dan bintang kecil naik lalu menghilang (`@keyframes percikan-naik`); putih di mode gelap, hitam di mode terang |
| Kotak ikut tema | `app.css:539` (`html.dark main .card`) | Kotak (kartu) mengikuti tema; di mode gelap diberi garis dan cahaya putih tipis di tepinya agar terpisah dari karakter latar |
| Penyihir di banner | `public/images/penyihir.png`, `dashboard.blade.php:34`, `app.css:701` (`.penyihir-banner`) | Penyihir terbang diam dan samar; warnanya mengikuti tinta teks banner sehingga menyatu |
| Karakter sidebar | `public/images/karakter-sidebar.png`, `layouts/app.blade.php:86`, `app.css:711` (`.karakter-sidebar`) | Karakter samar sebagai latar sidebar di belakang menu, tanpa bingkai; warnanya ikut tema |
| Warna ikut tema | sidebar `bg-surface`, banner `app.css:633` (`.banner-sapaan`) | Sidebar dan banner sapaan berwarna terang di mode terang dan gelap di mode gelap |

### 7.1 Tema Monokrom ala Bad Apple!! dan Mode Terang / Gelap

| Bagian | Lokasi | Cara kerja |
|---|---|---|
| Warna dasar | `app.css:17` (dalam `@theme`) | Warna utama `brand` dibuat hitam dan `slate` dibuat abu-abu netral, sehingga seluruh aplikasi hitam-putih seperti video Bad Apple!!. Warna status (hijau, kuning, merah) tetap berwarna agar artinya jelas |
| Teks di atas tombol utama | `--color-on-brand` (`app.css:47`) | Putih di mode terang, hitam di mode gelap; dipakai kelas `text-on-brand` |
| Mode gelap | `app.css:762` (`html.dark`) | Nilai variabel warna ditukar: abu-abu dibalik, tinta menjadi putih. Semua halaman ikut berubah tanpa diubah satu per satu |
| Tema awal | `partials/tema-awal.blade.php` | Dijalankan di `<head>` sebelum halaman tampil. Bila pengguna sudah memilih di tab ini (`sessionStorage`), pakai pilihan itu; bila belum, **ikut jam**: malam (18.00-03.59) gelap, selain itu terang |
| Tombol | `components/tombol-tema.blade.php` di topbar (`layouts/app.blade.php:150`) dan `login.blade.php:48` | Ikon bulan (mode terang) / matahari (mode gelap) |
| Transisi lingkaran | `tema.js:27` `gantiTema()` + `app.css:739` | **View Transitions API**: tema baru muncul sebagai lingkaran yang membesar dari tombol yang diklik. Browser lama memakai transisi warna biasa |

### 7.2 Halaman Login Siluet (Gambar, Animasi, Audio)

| Bagian | Lokasi | Cara kerja |
|---|---|---|
| Warna monokrom | `app.css:151` (`.login`) dan `app.css:167` (`html.dark .login`) | Dua variabel utama: `--latar` dan `--tinta`. Mode terang = tinta hitam di latar putih, mode gelap = kebalikannya |
| Siluet | `public/images/siluet.png`, `app.css:278` (`.siluet-gambar`) | Gambar PNG transparan dipakai sebagai *mask* CSS lalu diwarnai `--tinta`. Satu gambar cukup untuk dua mode |
| Posisi ikut tema | `app.css:217` (`.siluet`) dan `app.css:227` (`html.dark .siluet`) | Mode terang (siang): karakter di kiri dan dibalik (`scale: -1 1`) menghadap kartu di kanan. Mode gelap (malam): karakter di kanan, kartu di kiri |
| Teks raksasa | `app.css:251` (`.teks-raksasa`) | Tulisan "GUDANG SEKOLAH" bergaris tipis mengisi ruang kosong |
| Apel jatuh | `app.css:269` (`.apel-jatuh`), `login.blade.php:23` | 4 apel kecil (SVG) jatuh dan berputar perlahan, nuansa Bad Apple!! |
| Cahaya apel | `app.css:288` (`.cahaya-apel`) | Cahaya berdenyut di sekitar apel; warnanya mengikuti waktu (pagi kuning, siang merah, sore jingga, malam merah tua) |
| Animasi lain | `app.css` `@keyframes` (muncul-siluet, muncul-kartu, muncul-teks, denyut-apel, melayang, apel-jatuh) | Animasi CSS murni, tanpa library |
| Penentu waktu | `app/Support/Suasana.php` | Pagi 04.00-10.59, siang 11.00-14.59, sore 15.00-17.59, malam 18.00-03.59 |
| Data ke browser | `AuthController.php:16` → `login.blade.php:16` | Array `DAFTAR` dikirim sebagai JSON (`@json`) |
| Jam berjalan | `suasana.js:60` | Diperbarui setiap detik; salam, warna cahaya apel, dan suara ikut berganti saat jamnya lewat |
| Suara suasana (audio) | `public/audio/*.wav`, tag `<audio>` di `login.blade.php:110`, `suasana.js:68` | Tombol "Putar suara ..." memutar suara suasana (kicau burung pagi, angin & tonggeret siang, lonceng angin sore, jangkrik malam) secara berulang (`loop`) |
| Layar kecil | `app.css` `@media (width < 1024px)` | Siluet menjadi hiasan samar di belakang kartu, teks raksasa pindah ke atas |
| Aksesibilitas | `app.css:746` | Bila perangkat diatur "kurangi animasi", animasi dimatikan |

### 7.3 Musik Bad Apple!! (Audio MP3)

| Bagian | Lokasi | Cara kerja |
|---|---|---|
| File lagu | `public/audio/bad-apple.mp3` | Lagu Bad Apple!! dalam format MP3 |
| Elemen audio | `partials/pemutar-musik.blade.php` | `<audio id="musik-bad-apple" loop preload="none">`; dipasang di layout aplikasi dan halaman login. `preload="none"` agar file 5 MB baru diunduh saat diputar |
| Tombol | `components/tombol-musik.blade.php` di topbar (`layouts/app.blade.php:149`) dan `login.blade.php:44` | Ikon not musik; berubah menjadi ikon jeda saat diputar |
| Logika | `resources/js/musik.js` fungsi `pasangMusik()` | Klik = `play()` bila berhenti, `pause()` bila sedang diputar |
| Lanjut antar halaman | `sessionStorage` (`musik-detik`, `musik-main`) | Posisi lagu disimpan saat meninggalkan halaman (`pagehide`); di halaman berikutnya lagu dilanjutkan dari detik terakhir. Bila browser menolak memutar otomatis, cukup klik tombol musik lagi |
| Tidak bertabrakan | event `suasana-diputar` | Memutar suara suasana menjeda musik, dan sebaliknya |

Catatan: gambar siluet dan lagu berasal dari video Bad Apple!! (https://youtu.be/FtutLA63Cp8); gambar diubah menjadi PNG transparan 3 kali lebih besar dengan tepi yang dihaluskan. Suara suasana di `public/audio/*.wav` dibuat sendiri secara sintetis dengan program Python.

## 8. Pertanyaan Cepat "Di Mana...?"

| Pertanyaan | Jawaban |
|---|---|
| Di mana proses login? | `AuthController::login()` (`AuthController.php:25`) |
| Di mana password di-hash? | `User.php:84`, cast `'password' => 'hashed'` (bcrypt) |
| Di mana hak akses role dicek? | `CekRole.php:17`, dipasang di `routes/web.php` |
| Di mana stok bertambah / berkurang? | Hanya di `StokService.php` (`tambah()` baris 22, `kurangi()` baris 35) |
| Di mana stok dicek tidak boleh minus? | `StokService::kurangi()` dan validasi `BarangKeluarRequest::after()` |
| Di mana kode barang dibuat otomatis? | `Barang::generateKode()` (`Barang.php:115`) |
| Di mana validasi form? | `app/Http/Requests/` |
| Di mana file gambar disimpan? | `GambarService::simpan()`; lokal di `storage/app/public`, online di Supabase Storage |
| Di mana file audio? | `public/audio/` |
| Di mana transaksi database (*transaction*)? | `BarangMasukController` dan `BarangKeluarController`, `DB::transaction(...)` |
| Di mana relasi tabel? | Method `belongsTo` / `hasMany` di `app/Models/` dan *foreign key* di `database/migrations/` |
| Di mana struktur tabel dibuat? | `database/migrations/` |
| Di mana data sampel? | `database/seeders/DatabaseSeeder.php` |
| Di mana menu sidebar? | Array `$menu` di `layouts/app.blade.php:46` |
| Di mana salam "Selamat pagi"? | `Suasana::salam()`, dipanggil di `DashboardController.php:51` |
| Di mana popup tambah/ubah/detail? | `resources/js/popup.js` dan bagian atas `layouts/app.blade.php` |
| Di mana pindah halaman tanpa muat ulang? | Turbo, dipasang di `resources/js/app.js` |
| Di mana musik Bad Apple!!? | File `public/audio/bad-apple.mp3`, elemen di `partials/pemutar-musik.blade.php`, logika di `resources/js/musik.js` |
| Di mana pengaturan mode gelap? | `tema.js`, `partials/tema-awal.blade.php`, `app.css:762` |
| Di mana pengujian? | `tests/Feature/` dan `tests/Unit/`; jalankan `php artisan test` |
| Di mana error 419 ditangani? | `bootstrap/app.php:30` |
