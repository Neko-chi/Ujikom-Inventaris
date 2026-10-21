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
| 5 | Service | `app/Services/*.php` | Aturan bisnis yang dipakai berulang (stok per lokasi & kondisi, gambar) |
| 6 | Model | `app/Models/*.php` | Mewakili tabel database (Eloquent ORM) |
| 7 | View | `resources/views/**/*.blade.php` | Tampilan HTML yang dikirim ke browser |

Contoh: Operator mengajukan barang keluar → `POST /barang-keluar` (web.php:45) → middleware `auth` + `role:Operator` → `BarangKeluarRequest` memeriksa isian dan stok per kondisi di lokasi asal → `BarangKeluarController@store` menyimpan foto lewat `GambarService::simpan()` lalu `BarangKeluar::create()` → kembali ke daftar dengan pesan sukses.

## 2. Peta Folder

| Folder / File | Isi |
|---|---|
| `routes/web.php` | Semua URL aplikasi (48 route) dan pembagian hak akses per role |
| `app/Http/Controllers/` | 10 controller: Auth, Dashboard, Profil, User, Kategori, Lokasi, Barang, BarangMasuk, BarangKeluar, Pengembalian |
| `app/Http/Requests/` | 8 class validasi form |
| `app/Http/Middleware/CekRole.php` | Pembatas akses berdasarkan role |
| `app/Models/` | 8 model: User, Kategori, Lokasi, Barang, StokBarang, BarangMasuk, BarangKeluar, Pengembalian |
| `app/Models/Concerns/PunyaRincianKondisi.php` | Trait rincian kondisi (Baik / Rusak Ringan / Rusak Berat) yang dipakai 3 model transaksi |
| `app/Services/` | `StokService` (ubah stok per lokasi & kondisi) dan `GambarService` (simpan/hapus gambar) |
| `app/Support/Suasana.php` | Penentu suasana pagi/siang/sore/malam (salam, animasi login, audio) |
| `app/Exceptions/StokTidakCukupException.php` | Error khusus saat stok kurang |
| `app/Providers/AppServiceProvider.php` | Pengaturan awal aplikasi (https, pagination, jumlah Pending) |
| `bootstrap/app.php` | Pendaftaran middleware `role` dan penanganan error 419 |
| `database/migrations/` | 8 file pembuat / pengubah tabel (terakhir: `2026_10_21_000001_tambah_lokasi_kondisi_peminjaman`) |
| `database/seeders/DatabaseSeeder.php` | Data sampel (3 akun, 5 kategori, 7 lokasi, 11 barang dengan stok per lokasi & kondisi, transaksi, 1 pengembalian) |
| `resources/views/` | Tampilan Blade (layout, halaman, komponen) |
| `resources/css/app.css` | Gaya tampilan (Tailwind CSS), animasi login, mode gelap |
| `resources/js/` | `app.js` (Turbo & interaksi umum), `popup.js` (popup & panel kanan), `tema.js` (mode gelap), `suasana.js` (salam, jam & audio login), `musik.js` (musik Bad Apple!!), `form-kondisi.js` (total kondisi dan form barang keluar sesuai jenis) |
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
| `routes/web.php:15` | `Route::redirect('/', '/login')` | Alamat utama langsung diarahkan ke halaman login |
| `routes/web.php:18` | grup `guest` | Halaman login hanya untuk yang belum login |
| `routes/web.php:23` | grup `auth` | Semua halaman lain wajib login |
| `routes/web.php:35` | grup `role:Admin,Operator` | Tambah barang |
| `routes/web.php:41` | grup `role:Operator` | Catat barang masuk, ajukan barang keluar, catat pengembalian (baris 47) |
| `routes/web.php:51` | grup `role:Admin` | Kelola pengguna, kategori, lokasi (baris 55), ubah/hapus barang, koreksi barang masuk |
| `routes/web.php:62` | grup `role:Manager` | Setujui / tolak barang keluar |
| `routes/web.php:68-72` | tanpa grup role | Halaman lihat data, untuk semua role |
| `CekRole.php:17` | `handle($request, $next, ...$roles)` | Bila role pengguna tidak ada di daftar `$roles`, tampilkan error 403. `...$roles` = parameter variadic (bisa banyak role) |
| `bootstrap/app.php:18` | `$middleware->alias(['role' => CekRole::class])` | Mendaftarkan nama pendek `role` agar bisa ditulis `role:Admin` di route |
| `bootstrap/app.php:29` | `$exceptions->render(...)` | Gambar gagal disimpan → kembali ke form dengan pesan jelas, bukan error 500 |
| `bootstrap/app.php:43` | `$exceptions->render(...)` | Error 419 (Page Expired) diganti pesan jelas dan isian form dikembalikan |

### 3.2 Controller

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| `AuthController.php:16` | `index()` | Menampilkan halaman login beserta suasana waktu (`Suasana::dariJam()`) |
| `AuthController.php:25` | `login()` | Validasi username & password, `Auth::attempt()` mencocokkan hash password, lalu `session()->regenerate()` |
| `AuthController.php:44` | `logout()` | Keluar, hapus sesi, buat token CSRF baru |
| `DashboardController.php:18` | `index()` | Menghitung ringkasan (jumlah barang, total stok, sedang dipinjam, dll.), stok per kategori, status permintaan, stok menipis |
| `DashboardController.php:64` | `aktivitasTerbaru()` | Menggabungkan barang masuk & keluar terbaru menjadi satu daftar urut tanggal |
| `ProfilController.php:20` | `edit()` | Form profil milik pengguna yang login |
| `ProfilController.php:25` | `update()` | Simpan nama, ganti/hapus foto, ganti password (bila diisi); dari panel kanan kembali ke halaman asal |
| `UserController.php:17` | `index()` | Daftar pengguna + jumlah transaksinya (`withCount`) |
| `UserController.php:27` / `:32` | `create()` / `store()` | Form dan simpan pengguna baru |
| `UserController.php:39` / `:44` | `edit()` / `update()` | Ubah pengguna; password kosong = tidak diganti; Admin tidak bisa menurunkan role dirinya sendiri (baris 54) |
| `UserController.php:63` | `destroy()` | Hapus pengguna, kecuali diri sendiri atau yang sudah punya transaksi |
| `KategoriController.php:15-46` | `index`, `create`, `store`, `edit`, `update`, `destroy` | CRUD kategori; kategori yang masih dipakai barang tidak bisa dihapus (baris 49) |
| `LokasiController.php:16` | `index()` | Daftar lokasi + total stok (`withSum`) dan jumlah jenis barang per lokasi |
| `LokasiController.php:32-56` | `create`, `store`, `edit`, `update`, `destroy` | CRUD lokasi (Admin); lokasi yang masih berisi stok / dipakai transaksi tidak bisa dihapus (baris 59, `sudahDipakai()`) |
| `BarangController.php:31` | `index()` | Daftar barang dengan pencarian nama/kode (`whereLike`), filter kategori, dan filter lokasi (`whereHas('stokBarang')`) |
| `BarangController.php:67` | `store()` | Simpan barang baru (stok 0), lalu stok awal ditambah per kondisi ke lokasi terpilih (`tambahRincian`) dalam satu `DB::transaction`; kode dibuat otomatis `Barang::generateKode()`; gambar disimpan `GambarService` |
| `BarangController.php:93` | `show()` | Detail barang: stok per kondisi, stok per lokasi, jumlah dipinjam, riwayat masuk & keluar |
| `BarangController.php:113` | `update()` | Ubah kategori, nama, satuan, gambar; stok tidak diubah dari sini |
| `BarangController.php:135` | `destroy()` | Hapus barang, ditolak bila sudah punya riwayat transaksi |
| `BarangMasukController.php:34` | `index()` | Daftar barang masuk (kondisi & lokasi) dengan filter tanggal dari–sampai |
| `BarangMasukController.php:59` | `store()` | Catat barang masuk ke lokasi + tambah stok per kondisi (`tambahRincian`) dalam satu `DB::transaction` |
| `BarangMasukController.php:93` | `update()` | Koreksi oleh Admin: rincian lama dibatalkan dari lokasi lama (`kurangiRincian`), rincian baru ditambahkan; ditolak bila stok lama sudah terpakai |
| `BarangMasukController.php:135` | `destroy()` | Hapus data masuk dan kurangi kembali stok per kondisi |
| `BarangKeluarController.php:40` | `index()` | Daftar permintaan dengan tab status verifikasi, tab "Sedang Dipinjam", dan filter jenis keluar |
| `BarangKeluarController.php:61` | `show()` | Detail permintaan + stok per kondisi di lokasi asal (untuk panel verifikasi Manager) + riwayat pengembalian |
| `BarangKeluarController.php:78` | `create()` | Form pengajuan + peta stok `[id_barang][id_lokasi][kondisi]` untuk JavaScript |
| `BarangKeluarController.php:94` | `store()` | Operator mengajukan Peminjaman / Pemindahan / Pemusnahan; status = Pending; foto bukti wajib |
| `BarangKeluarController.php:118` | `setujui()` | Manager menyetujui; bila rincian kondisi dikoreksi, divalidasi ulang dengan `periksaRincian()` dan dicatat di catatan verifikasi; lalu `terapkanStok()` |
| `BarangKeluarController.php:168` | `tolak()` | Manager menolak → stok tetap, alasan wajib |
| `BarangKeluarController.php:199` | `terapkanStok()` | Peminjaman / Pemusnahan: stok lokasi asal berkurang. Pemindahan: stok lokasi asal berkurang dan lokasi tujuan bertambah |
| `BarangKeluarController.php:216` | `kunciPermintaanPending()` | `lockForUpdate()` mengunci baris agar 2 Manager tidak menyetujui bersamaan (stok tidak terpotong dua kali) |
| `BarangKeluarController.php:226` | `simpanVerifikasi()` | Mencatat hasil, id Manager, tanggal, dan catatan verifikasi |
| `PengembalianController.php:27` | `store()` | Operator mencatat pengembalian pinjaman: kunci baris peminjaman, cek ulang sisa pinjaman (baris 40), simpan, lalu stok bertambah sesuai kondisi saat kembali (baris 54) |

Fungsi `__construct(private StokService $stok, ...)` di BarangController, BarangMasukController, BarangKeluarController, PengembalianController, ProfilController adalah **dependency injection**: Laravel otomatis membuatkan objek service dan memasukkannya ke controller.

### 3.3 Form Request (Validasi)

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| Semua `*Request.php` | `authorize()` | Selalu `true`, karena hak akses sudah diatur middleware di route |
| Semua `*Request.php` | `rules()` | Mengembalikan **array aturan validasi** per kolom |
| Semua `*Request.php` | `attributes()` | Mengembalikan **array nama kolom** yang ramah dibaca pada pesan error |
| `BarangKeluarRequest.php:27` | `rules()` | `jenis_keluar` harus salah satu `BarangKeluar::JENIS`; `id_lokasi_tujuan` wajib bila Pemindahan (`required_if`) dan berbeda dari lokasi asal (`different`); `tanggal_kembali` wajib bila Peminjaman |
| `BarangKeluarRequest.php:51` | `after()` | Validasi tambahan memanggil `periksaRincian()` |
| `BarangKeluarRequest.php:77` | `periksaRincian()` | Total minimal 1; Pemusnahan hanya Rusak Berat; tiap kondisi tidak boleh melebihi stok kondisi itu di lokasi asal. Dipakai juga saat Manager mengoreksi |
| `BarangMasukRequest.php:39` | `after()` | Total rincian kondisi minimal 1 |
| `PengembalianRequest.php:38` | `after()` | Total minimal 1 dan tidak boleh melebihi sisa pinjaman |
| `BarangRequest.php:33` | `if ($this->isMethod('post'))` | Stok awal (lokasi + rincian kondisi) hanya diisi saat tambah barang, bukan saat ubah; `after()` (baris 44) mewajibkan lokasi bila stok awal diisi |
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
| `Barang.php:77` | `gambarUrl()` | Accessor: `$barang->gambar_url` |
| `Barang.php:82` | `kategori()` | Relasi barang → kategori (`belongsTo`) |
| `Barang.php:87/92` | `barangMasuk()`, `barangKeluar()` | Relasi barang → transaksi (`hasMany`) |
| `Barang.php:98` | `stokBarang()` | Relasi barang → baris stok per lokasi & kondisi (`hasMany`) |
| `Barang.php:108` | `stokPerKondisi()` | `['Baik' => 8, 'Rusak Ringan' => 0, 'Rusak Berat' => 2]` (semua lokasi) |
| `Barang.php:124` | `stokPerLokasi()` | `['Gudang Utama' => ['Baik' => 110, ...], 'Gudang 2' => [...]]` untuk tabel Stok per Lokasi |
| `Barang.php:136` | `jumlahDipinjam()` | Jumlah barang yang sedang dipinjam (belum kembali) |
| `Barang.php:152` | `prefixKategori()` | Nama kategori → prefix kode, contoh "Alat Tulis" → "AT" |
| `Barang.php:173` | `generateKode()` | Membuat kode berikutnya, contoh sudah ada AT-004 → AT-005 |
| `Barang.php:185` | `stokMenipis()` | `true` bila stok ≤ 5 |
| `BarangKeluar.php:121/127/133` | `barang()`, `user()`, `verifikator()` | Relasi ke barang, pengaju, dan Manager pemverifikasi |
| `BarangKeluar.php:138/143` | `lokasiAsal()`, `lokasiTujuan()` | Relasi ke lokasi asal dan lokasi tujuan (Pemindahan) |
| `BarangKeluar.php:149` | `pengembalian()` | Relasi 1 peminjaman → banyak pengembalian (`hasMany`) |
| `BarangKeluar.php:154/159` | `isPending()`, `isPeminjaman()` | Cek status / jenis |
| `BarangKeluar.php:165/171` | `jumlahKembali()`, `sisaPinjaman()` | Jumlah yang sudah kembali dan yang masih dipinjam |
| `BarangKeluar.php:182` | `statusPinjam()` | `match (true)`: Dikembalikan / Dikembalikan Sebagian / Dipinjam |
| `BarangKeluar.php:197` | `terlambat()` | `true` bila lewat `tanggal_kembali` dan belum kembali semua |
| `BarangMasuk.php:71/76/81` | `barang()`, `user()`, `lokasi()` | Relasi ke barang, pencatat, dan lokasi penyimpanan |
| `Pengembalian.php:63/68/73` | `barangKeluar()`, `user()`, `lokasi()` | Relasi ke peminjaman, pencatat, dan lokasi penyimpanan |
| `StokBarang.php:38/43` | `barang()`, `lokasi()` | Satu baris stok milik satu barang di satu lokasi |
| `Lokasi.php:34` | `stokBarang()` | Relasi 1 lokasi → banyak baris stok |
| `Lokasi.php:40` | `sudahDipakai()` | Cek lokasi masih berisi stok atau tercatat di transaksi (tidak boleh dihapus) |
| `PunyaRincianKondisi.php:19` | `rincian()` | Kolom `jumlah_baik`, `jumlah_rusak_ringan`, `jumlah_rusak_berat` → array `['Baik' => 5, ...]` |
| `PunyaRincianKondisi.php:30` | `ringkasKondisi()` | Teks "5 Baik · 10 Rusak Ringan" |
| `PunyaRincianKondisi.php:47` | `isiRincian()` | Mengisi tiga kolom kondisi dan total `jumlah` dari array rincian |
| `PunyaRincianKondisi.php:62` | `rincianDariInput()` | Membaca rincian dari isian form |
| `Kategori.php:25` | `barang()` | Relasi 1 kategori → banyak barang |

### 3.5 Service, Support, dan Exception

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| `StokService.php:32` | `tersedia($idBarang, $idLokasi, $kondisi)` | Jumlah stok satu kondisi di satu lokasi |
| `StokService.php:37` | `cukup()` | Cek stok kondisi itu di lokasi itu ≥ jumlah |
| `StokService.php:42` | `tambah($barang, $idLokasi, $kondisi, $jumlah)` | Stok satu kondisi di satu lokasi bertambah |
| `StokService.php:56` | `kurangi($barang, $idLokasi, $kondisi, $jumlah)` | Stok berkurang; bila tidak cukup lempar `StokTidakCukupException` |
| `StokService.php:76` | `tambahRincian($barang, $idLokasi, $rincian)` | Menambah beberapa kondisi sekaligus (barang masuk, pengembalian, pemindahan ke tujuan) |
| `StokService.php:93` | `kurangiRincian($barang, $idLokasi, $rincian)` | Memeriksa semua kondisi dulu, baru mengurangi (tidak ada pengurangan setengah jalan) |
| `StokService.php:110` | `baris()` | Ambil / buat baris `stok_barang` dan kunci dengan `lockForUpdate()` |
| `StokService.php:119` | `perbaruiTotal()` | Menghitung ulang `barang.stok` = jumlah semua baris stok barang itu |
| `StokService.php:125` | `pastikanValid()` | Jumlah ≤ 0 atau kondisi tidak dikenal ditolak (`InvalidArgumentException`) |
| `GambarService.php:24` | `disk()` | Memilih tempat simpan: `public` (lokal) atau `s3` (Supabase) |
| `GambarService.php:35` | `simpan()` | Simpan file dengan nama acak, kembalikan path-nya; bila gagal lempar `GambarGagalDisimpanException` dan catat penyebabnya ke log |
| `GambarService.php:55` | `ganti()` | Simpan file baru lalu hapus file lama |
| `GambarService.php:67` | `hapus()` | Hapus file bila ada; gagal menghapus hanya dicatat di log |
| `GambarService.php:84` | `url()` | Alamat gambar untuk tag `<img>` |
| `Suasana.php:46` | `dariJam($jam)` | Jam 0-23 → `pagi` / `siang` / `sore` / `malam` |
| `Suasana.php:61` | `salam($jam)` | Jam → "Selamat pagi", dst. (dipakai Dashboard) |
| `Suasana.php:69` | `untukBrowser()` | Data suasana + alamat file audio untuk JavaScript login |
| `GambarGagalDisimpanException.php:15` | `__construct()` | Pesan "Gambar gagal disimpan ke penyimpanan…" |
| `StokTidakCukupException.php:13` | `__construct()` | Menyusun pesan "Stok X kondisi Rusak Ringan di Gudang Utama tidak mencukupi..." |
| `AppServiceProvider.php:18` | `boot()` | Paksa https di produksi, pakai tampilan pagination Indonesia, hitung jumlah Pending untuk sidebar (`View::composer`) |
| `DatabaseSeeder.php:26` | `run()` | Mengisi data sampel: akun, kategori, lokasi, barang + stok per lokasi & kondisi, transaksi, pengembalian |

### 3.6 JavaScript

| Lokasi | Fungsi | Untuk apa |
|---|---|---|
| `resources/js/app.js:8` | `import * as Turbo` | Memasang **Turbo**: pindah menu dan kirim form tanpa memuat ulang halaman |
| `app.js:26` | `simpanSidebar()` | Menyimpan posisi sidebar (terbuka / tertutup) di `localStorage` |
| `app.js:34` | `bukaSidebarHp(buka)` | Buka/tutup menu geser di HP |
| `app.js:43` | `[data-sidebar-toggle]` | Tombol burger: layar besar menutup/membuka sidebar, HP membuka menu geser |
| `app.js:57` | `[data-dismiss]` | Menutup pesan notifikasi |
| `app.js:64` | `[data-toggle-password]` | Tampilkan / sembunyikan password |
| `app.js:76` | `[data-pratinjau]` | Pratinjau gambar sebelum diunggah + cek ukuran maks 2 MB |
| `app.js:105` | `turbo:load` | Dijalankan setiap halaman baru tampil (termasuk lewat Turbo) |
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
| `form-kondisi.js:9` | `hitungTotal()` | Menjumlahkan 3 kotak kondisi dan menampilkan totalnya |
| `form-kondisi.js:18` | `aturFormKeluar()` | Menampilkan bagian sesuai jenis (`data-hanya-jenis`), mengunci Baik / Rusak Ringan untuk Pemusnahan, mengganti label Pemohon / Peminjam |
| `form-kondisi.js:51` | `tampilkanStok()` | Membaca peta stok JSON, menampilkan stok tiap kondisi di lokasi asal, dan memasang batas `max` |
| `form-kondisi.js:77` | `pasangFormKondisi()` | Memasang event `input` / `change` (event delegation) serta `turbo:load` dan `turbo:frame-load` |
| `partials/tema-awal.blade.php` | skrip kecil di `<head>` | Menentukan tema (pilihan pengguna atau ikut jam) dan posisi sidebar sebelum halaman tampil |

**Event delegation**: karena Turbo mengganti isi halaman tanpa memuat ulang, klik dipasang sekali di `document` lalu dicek dengan `event.target.closest('[data-...]')`. Dengan begitu tombol di halaman baru tetap berfungsi tanpa dipasang ulang.

## 4. Daftar Array dan Konstanta

| Lokasi | Nama | Jenis array | Isi / Kegunaan |
|---|---|---|---|
| `User.php:32` | `ROLES` | Array biasa (indeks) | `['Admin', 'Operator', 'Manager']`, dipakai validasi role |
| `User.php:38` | `INFO_ROLE` | Array asosiatif bersarang | Per role: ikon, warna badge, keterangan tugas (halaman Pengguna) |
| `User.php:72` | `$fillable` | Array biasa | Kolom yang boleh diisi massal (mencegah *mass assignment*) |
| `User.php:80` | `$hidden` | Array biasa | `password` disembunyikan saat model diubah ke JSON |
| `Barang.php:32` | `KONDISI` | Array biasa | `['Baik', 'Rusak Ringan', 'Rusak Berat']` |
| `Barang.php:35` | `KOLOM_KONDISI` | Array asosiatif | `'Baik' => 'jumlah_baik'`, `'Rusak Ringan' => 'jumlah_rusak_ringan'`, `'Rusak Berat' => 'jumlah_rusak_berat'` |
| `Barang.php:42` | `PREFIX_KATEGORI` | Array asosiatif | `'Alat Tulis' => 'AT'`, dst. (Kamus Data) |
| `Barang.php:59` | `$fillable` | Array biasa | Kolom barang yang boleh diisi |
| `BarangKeluar.php:48` | `JENIS` | Array biasa | `['Peminjaman', 'Pemindahan', 'Pemusnahan']` |
| `BarangKeluar.php:51` | `INFO_JENIS` | Array asosiatif | Jenis keluar → keterangan yang tampil di form |
| `BarangKeluar.php:64` | `VERIFIKASI` | Array biasa | `['Pending', 'Disetujui', 'Ditolak']` |
| `Lokasi.php:23` | `JENIS` | Array biasa | `['Gudang', 'Ruangan']` |
| `PunyaRincianKondisi.php:19` | hasil `rincian()` | Array asosiatif | Rincian kondisi satu transaksi, contoh `['Baik' => 5, 'Rusak Ringan' => 10, 'Rusak Berat' => 5]` |
| `BarangKeluarController.php:81` | `$petaStok` | Array asosiatif 3 tingkat | `[id_barang][id_lokasi][kondisi] => jumlah`; dikirim ke form sebagai JSON |
| `Barang.php:124` | hasil `stokPerLokasi()` | Array asosiatif bersarang | `nama_lokasi => [kondisi => jumlah]` |
| `form-kondisi.js:7` | `KONDISI` | Array JavaScript | Urutan kondisi untuk menampilkan stok |
| `GambarService.php:20` | `ATURAN` | Array biasa | Aturan validasi gambar: jenis file dan maks 2 MB; disisipkan ke Request dengan operator spread `...` |
| `Suasana.php:16` | `DAFTAR` | Array asosiatif bersarang | Per waktu: jam mulai, salam, kalimat, file audio |
| `DashboardController.php:20` | `$ringkasan` | Array asosiatif | 4 angka kartu statistik dashboard |
| `DatabaseSeeder.php:52` | `$daftarBarang` | Array bersarang | 11 barang sampel beserta stok `lokasi => [Baik, Rusak Ringan, Rusak Berat]`; dibongkar dengan *destructuring* `[$kode, $idKategori, ...]` |
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
| `if / elseif` | `PengembalianRequest.php:53`, `BarangController.php:120` | Total 0 → error "minimal 1", melebihi sisa → error "melebihi sisa pinjaman"; gambar baru → ganti, centang hapus → hapus |
| `match (true)` | `BarangKeluar.php:189` | Status pinjam berdasarkan jumlah yang sudah kembali |
| `match` (PHP 8) | `dashboard.blade.php:10`, `badge.blade.php:5` | Pilih nilai berdasarkan role / status, lebih ringkas dari `switch` |
| `foreach` | `Suasana.php:49`, `StokService.php:95`, `DatabaseSeeder.php:68` | Mengulang isi array (di StokService: memeriksa setiap kondisi dalam rincian) |
| `@foreach` / `@forelse` (Blade) | `dashboard.blade.php:89` | Mengulang data di tampilan; `@forelse` punya bagian `@empty` bila data kosong |
| `for ... of` (JS) | `suasana.js:12` | Mengulang data suasana di JavaScript |
| `try / catch` | `BarangKeluarController.php:126`, `BarangMasukController.php:103` | Menangkap error stok kurang dan menghapus file yang terlanjur disimpan |
| `throw` | `StokService.php:62` | Melempar error bila stok tidak cukup |
| Operator ternary `? :` | `BarangMasukController.php:63` | Foto ada → simpan, tidak ada → `null` |
| Null-safe `?->` | `UserRequest.php:22` | Ambil id hanya bila data ada |

## 6. Konsep OOP di Kode

| Konsep | Contoh | Penjelasan singkat |
|---|---|---|
| Class & objek | `class StokService` | Cetakan; Laravel membuat objeknya otomatis |
| Pewarisan (*inheritance*) | `class Barang extends Model`, `User extends Authenticatable`, `StokTidakCukupException extends RuntimeException` | Mewarisi kemampuan class induk |
| Enkapsulasi | `private function pastikanValid()` (StokService:125), `private function kunciPermintaanPending()`, `private function terapkanStok()` | Hanya bisa dipanggil dari dalam class itu sendiri |
| Konstanta class | `User::ROLE_ADMIN`, `BarangKeluar::PENDING`, `BarangKeluar::PEMINJAMAN`, `Barang::RUSAK_BERAT` | Nilai tetap, menghindari salah ketik |
| Method statis | `Barang::generateKode()`, `Suasana::dariJam()`, `BarangKeluarRequest::periksaRincian()` | Dipanggil tanpa membuat objek |
| *Dependency injection* | `__construct(private StokService $stok)` | Objek service dimasukkan otomatis oleh Laravel |
| *Trait* | `use PunyaRincianKondisi;` di BarangMasuk, BarangKeluar, Pengembalian | Kumpulan method yang "ditempelkan" ke beberapa class tanpa pewarisan, agar kode rincian kondisi tidak ditulis tiga kali |
| *Constructor property promotion* | `private StokService $stok` di parameter constructor | Fitur PHP 8: deklarasi properti sekaligus di constructor |

## 7. Fitur Tambahan Terbaru

### 7.0 Tanpa Pindah Halaman, Popup, dan Panel Profil (Turbo)

| Bagian | Lokasi | Cara kerja |
|---|---|---|
| Turbo Drive | `resources/js/app.js:8` | Library **Hotwire Turbo**. Saat menu diklik atau form dikirim, halaman diambil di belakang layar lalu isi `<body>` diganti; tidak ada muat ulang penuh, musik tidak terputus |
| Mode popup di layout | `layouts/app.blade.php:1-29` | Bila permintaan membawa header `Turbo-Frame: modal` / `laci` **dan** halamannya punya `@section('popup')`, layout hanya mengirim isi halaman di dalam `<turbo-frame>` tanpa sidebar |
| Halaman yang bisa jadi popup | `barang/form`, `barang/show`, `barang-masuk/form`, `barang-keluar/form`, `barang-keluar/show`, `kategori/form`, `lokasi/form`, `user/form`, `profil/edit` | Diberi `@section('popup', true)`. Bila dibuka langsung (tanpa Turbo), halaman tetap tampil utuh seperti biasa |
| Tautan pembuka popup | atribut `data-turbo-frame="modal"` di tombol Tambah, Ubah, Detail | Isi halaman dimuat ke popup tengah |
| Panel profil kanan | foto profil di topbar dan kartu pengguna di sidebar (`data-turbo-frame="laci"`) | Menu "Profil Saya" diganti panel yang muncul dari kanan |
| Wadah popup | `<dialog id="dialog-modal">` dan `<dialog id="dialog-laci">` di layout, frame dibuat `popup.js` | Elemen `<dialog>` bawaan browser: bisa ditutup dengan Esc, latar belakang digelapkan |
| Validasi gagal | `popup.js:46` | Laravel kembali ke form di dalam popup dengan pesan error |
| Simpan berhasil | `popup.js:68` | Server mengarahkan ke halaman daftar; popup ditutup dan halaman daftar tampil dengan pesan sukses |
| Simpan profil | `ProfilController::update()` | Kembali ke halaman yang sedang dibuka (header `X-Halaman-Asal`, hanya alamat aplikasi sendiri) agar nama dan foto di sidebar ikut berubah |
| Tombol burger | `app.js:41`, `app.css:456` (`html.sidebar-tutup`) | Layar besar: sidebar ditutup/dibuka, isi halaman melebar; posisi diingat di `localStorage`. HP: membuka menu geser |
| Latar karakter | `public/images/latar-karakter.png`, `app.css:470` (`.latar-karakter`), `layouts/app.blade.php:66` | Karakter tersenyum bersayap, besar di tengah area isi. Warnanya kebalikan latar seperti di login: hitam di mode terang, putih di mode gelap |
| Percikan cahaya | `layouts/app.blade.php:69` (34 titik), `app.css:496` (`.percikan`) | Titik cahaya dan bintang kecil naik lalu menghilang (`@keyframes percikan-naik`); putih di mode gelap, hitam di mode terang |
| Kotak ikut tema | `app.css:539` (`html.dark main .card`) | Kotak (kartu) mengikuti tema; di mode gelap diberi garis dan cahaya putih tipis di tepinya agar terpisah dari karakter latar |
| Penyihir di banner | `public/images/penyihir.png`, `dashboard.blade.php:34`, `app.css:701` (`.penyihir-banner`) | Penyihir terbang diam dan samar; warnanya mengikuti tinta teks banner sehingga menyatu |
| Karakter sidebar | `public/images/karakter-sidebar.png`, `layouts/app.blade.php:87`, `app.css:711` (`.karakter-sidebar`) | Karakter samar sebagai latar sidebar di belakang menu, tanpa bingkai; warnanya ikut tema |
| Warna ikut tema | sidebar `bg-surface`, banner `app.css:633` (`.banner-sapaan`) | Sidebar dan banner sapaan berwarna terang di mode terang dan gelap di mode gelap |

### 7.1 Tema Monokrom ala Bad Apple!! dan Mode Terang / Gelap

| Bagian | Lokasi | Cara kerja |
|---|---|---|
| Warna dasar | `app.css:17` (dalam `@theme`) | Warna utama `brand` dibuat hitam dan `slate` dibuat abu-abu netral, sehingga seluruh aplikasi hitam-putih seperti video Bad Apple!!. Warna status (hijau, kuning, merah) tetap berwarna agar artinya jelas |
| Teks di atas tombol utama | `--color-on-brand` (`app.css:47`) | Putih di mode terang, hitam di mode gelap; dipakai kelas `text-on-brand` |
| Mode gelap | `app.css:762` (`html.dark`) | Nilai variabel warna ditukar: abu-abu dibalik, tinta menjadi putih. Semua halaman ikut berubah tanpa diubah satu per satu |
| Tema awal | `partials/tema-awal.blade.php` | Dijalankan di `<head>` sebelum halaman tampil. Bila pengguna sudah memilih di tab ini (`sessionStorage`), pakai pilihan itu; bila belum, **ikut jam**: malam (18.00-03.59) gelap, selain itu terang |
| Tombol | `components/tombol-tema.blade.php` di topbar (`layouts/app.blade.php:151`) dan `login.blade.php:48` | Ikon bulan (mode terang) / matahari (mode gelap) |
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
| Tombol | `components/tombol-musik.blade.php` di topbar (`layouts/app.blade.php:150`) dan `login.blade.php:44` | Ikon not musik; berubah menjadi ikon jeda saat diputar |
| Logika | `resources/js/musik.js` fungsi `pasangMusik()` | Klik = `play()` bila berhenti, `pause()` bila sedang diputar |
| Lanjut antar halaman | `sessionStorage` (`musik-detik`, `musik-main`) | Posisi lagu disimpan saat meninggalkan halaman (`pagehide`); di halaman berikutnya lagu dilanjutkan dari detik terakhir. Bila browser menolak memutar otomatis, cukup klik tombol musik lagi |
| Tidak bertabrakan | event `suasana-diputar` | Memutar suara suasana menjeda musik, dan sebaliknya |

Catatan: gambar siluet dan lagu berasal dari video Bad Apple!! (https://youtu.be/FtutLA63Cp8); gambar diubah menjadi PNG transparan 3 kali lebih besar dengan tepi yang dihaluskan. Suara suasana di `public/audio/*.wav` dibuat sendiri secara sintetis dengan program Python.

### 7.4 Peminjaman, Kondisi per Jumlah, dan Lokasi (Masukan Pembina)

| Bagian | Lokasi | Cara kerja |
|---|---|---|
| Struktur database | `database/migrations/2026_10_21_000001_tambah_lokasi_kondisi_peminjaman.php` | Membuat tabel `lokasi`, `stok_barang`, `pengembalian`; menambah kolom jenis keluar, lokasi, dan `jumlah_baik` / `jumlah_rusak_ringan` / `jumlah_rusak_berat`; `pindahkanDataLama()` memindahkan data lama sebelum kolom `lokasi` dan `status_barang` dihapus |
| Data master lokasi | menu **Lokasi** (Admin), `LokasiController`, `lokasi/index.blade.php`, `lokasi/form.blade.php` | Daftar tempat (Gudang / Ruangan) beserta total stok di tiap tempat |
| Stok per lokasi & kondisi | `StokService` (Bagian 3.5), tabel `stok_barang` | Satu baris per barang + lokasi + kondisi; `barang.stok` = total |
| Input kondisi | `components/input-kondisi.blade.php` + `form-kondisi.js` | Tiga kotak angka (Baik, Rusak Ringan, Rusak Berat) dengan total otomatis; dipakai di form barang, barang masuk, barang keluar, verifikasi Manager, dan pengembalian |
| Tampilan kondisi | `components/rincian-kondisi.blade.php` | Label berwarna per kondisi (hijau Baik, kuning Rusak Ringan, merah Rusak Berat) |
| Pilihan jenis keluar | `barang-keluar/form.blade.php` (kartu pilihan `data-pilih-jenis`) | Peminjaman (isi tanggal kembali), Pemindahan (isi lokasi tujuan), Pemusnahan (hanya Rusak Berat) |
| Pemeriksaan Manager | `barang-keluar/show.blade.php` (panel verifikasi) | Menampilkan stok di lokasi asal; Manager boleh mengubah rincian sebelum Setujui; koreksi tercatat di catatan verifikasi |
| Pengembalian | `barang-keluar/show.blade.php` (kartu Pengembalian), `PengembalianController::store()` | Riwayat pengembalian dan form untuk Operator selama masih ada sisa pinjaman |
| Status pinjam | `BarangKeluar::statusPinjam()`, `terlambat()`, `components/badge.blade.php` | Label Dipinjam / Dikembalikan Sebagian / Dikembalikan / Terlambat |
| Tab "Sedang Dipinjam" | `BarangKeluarController::index()` | Menyaring peminjaman disetujui yang jumlah kembalinya masih kurang dari jumlah pinjam |
| Detail barang | `barang/show.blade.php` | Label kondisi, jumlah sedang dipinjam, jumlah lokasi, tabel Stok per Lokasi |
| Dashboard | `DashboardController::index()` | Kartu "Sedang Dipinjam" menggantikan kartu total barang masuk |

## 8. Pertanyaan Cepat "Di Mana...?"

| Pertanyaan | Jawaban |
|---|---|
| Di mana proses login? | `AuthController::login()` (`AuthController.php:25`) |
| Di mana password di-hash? | `User.php:84`, cast `'password' => 'hashed'` (bcrypt) |
| Di mana hak akses role dicek? | `CekRole.php:17`, dipasang di `routes/web.php` |
| Di mana stok bertambah / berkurang? | Hanya di `StokService.php` (`tambah()` baris 42, `kurangi()` baris 56, `tambahRincian()` baris 76, `kurangiRincian()` baris 93) |
| Di mana stok dicek tidak boleh minus? | `StokService::kurangi()` / `kurangiRincian()` dan validasi `BarangKeluarRequest::periksaRincian()` |
| Di mana kode barang dibuat otomatis? | `Barang::generateKode()` (`Barang.php:173`) |
| Di mana validasi form? | `app/Http/Requests/` |
| Di mana file gambar disimpan? | `GambarService::simpan()`; lokal di `storage/app/public`, online di Supabase Storage |
| Di mana file audio? | `public/audio/` |
| Di mana transaksi database (*transaction*)? | `BarangController::store()`, `BarangMasukController`, `BarangKeluarController`, `PengembalianController`, `DB::transaction(...)` |
| Di mana relasi tabel? | Method `belongsTo` / `hasMany` di `app/Models/` dan *foreign key* di `database/migrations/` |
| Di mana struktur tabel dibuat? | `database/migrations/` |
| Di mana data sampel? | `database/seeders/DatabaseSeeder.php` |
| Di mana menu sidebar? | Array `$menu` di `layouts/app.blade.php:46` |
| Di mana salam "Selamat pagi"? | `Suasana::salam()`, dipanggil di `DashboardController.php:56` |
| Di mana popup tambah/ubah/detail? | `resources/js/popup.js` dan bagian atas `layouts/app.blade.php` |
| Di mana pindah halaman tanpa muat ulang? | Turbo, dipasang di `resources/js/app.js` |
| Di mana musik Bad Apple!!? | File `public/audio/bad-apple.mp3`, elemen di `partials/pemutar-musik.blade.php`, logika di `resources/js/musik.js` |
| Di mana pengaturan mode gelap? | `tema.js`, `partials/tema-awal.blade.php`, `app.css:762` |
| Di mana pengujian? | `tests/Feature/` dan `tests/Unit/`; jalankan `php artisan test` |
| Di mana error 419 ditangani? | `bootstrap/app.php:43` |
| Di mana peminjaman / pemindahan / pemusnahan diproses? | `BarangKeluarController::terapkanStok()` (baris 199), dipanggil saat `setujui()` |
| Di mana pengembalian dicatat? | `PengembalianController::store()`; route `pengembalian.store` (`routes/web.php:47`) |
| Di mana Manager mengoreksi kondisi? | `BarangKeluarController::setujui()` (baris 118) dan panel verifikasi di `barang-keluar/show.blade.php` |
| Di mana daftar lokasi? | Menu Lokasi: `LokasiController`, model `Lokasi`, tabel `lokasi` |
| Di mana stok per lokasi ditampilkan? | `Barang::stokPerLokasi()`, tabel "Stok per Lokasi" di `barang/show.blade.php` |
