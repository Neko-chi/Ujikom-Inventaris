# Persiapan Presentasi dan Tanya Jawab Asesor

| Keterangan | Isi |
|---|---|
| Peserta | Renaissance Ricarda Sugiarto Putra |
| Skema | Pemrogram Junior (Junior Coder) |
| Aplikasi | Inventaris Gudang Sekolah (Laravel 12) — 3 role: Admin, Operator, Manager |
| Akun demo | Admin `admin_ohim` / `admin123` · Manager `manager_marco` / `@admin123` · Operator `operator_siti` / `operator123` |

Dokumen ini berisi naskah presentasi, alur demo, serta pertanyaan yang kemungkinan diajukan asesor untuk setiap unit kompetensi beserta contoh jawabannya. Setiap jawaban menyebut **lokasi bukti di kode** agar bisa langsung dibuka di depan asesor (VS Code: **Ctrl+P** untuk mencari file, **Ctrl+Shift+O** untuk mencari method di dalam file).

**Cara menjawab yang baik:**

1. Jawab inti pertanyaannya dalam satu kalimat.
2. Tunjukkan buktinya: buka file / method, atau peragakan di aplikasi.
3. Jelaskan alasannya ("kenapa begitu").
4. Bila tidak tahu, jawab jujur lalu jelaskan cara mencarinya (dokumentasi Laravel, log, debugging). Asesor menilai cara berpikir, bukan hafalan.

<!-- daftar-isi -->

<!-- halaman-baru -->

## A. Presentasi Pembuka (± 2 menit)

> "Aplikasi saya bernama **Inventaris Gudang Sekolah**, dibuat dengan **Laravel 12**. Lokal memakai MySQL, versi online memakai PostgreSQL di Supabase dan di-hosting di Vercel.
>
> Masalah yang diselesaikan: pencatatan barang gudang sering manual, stok tidak jelas, dan barang bisa keluar tanpa persetujuan dan tanpa bukti.
>
> Ada tiga role. **Admin** mengelola sistem: pengguna, kategori, dan data barang. **Operator** mencatat barang masuk dan mengajukan barang keluar dengan foto bukti. **Manager** menyetujui atau menolak barang keluar dan hanya bisa melihat data. Tugasnya dipisah supaya tidak ada orang yang menyetujui permintaannya sendiri.
>
> Stok bertambah saat barang masuk dicatat, dan baru berkurang setelah Manager menyetujui. Setiap persetujuan mencatat siapa, kapan, dan alasannya.
>
> Aplikasi juga mendukung multimedia: gambar (foto profil, gambar barang, foto bukti transaksi), halaman login bergaya siluet yang beranimasi dan menyesuaikan waktu pagi, siang, sore, malam, serta audio suasana dan lagu Bad Apple!! yang bisa diputar. Tampilannya monokrom hitam-putih ala Bad Apple!! dengan mode terang dan gelap.
>
> Database berisi 5 tabel, dan program diuji dengan **75 test PHPUnit** yang semuanya lulus."

### Alur demo (± 5 menit)

1. Halaman login → tunjukkan salam dan warna cahaya apel sesuai jam → klik **Putar suara** → klik tombol not musik (lagu Bad Apple!!) → klik tombol bulan: tema berganti dengan efek lingkaran, siluet pindah sisi dan berubah dari hitam ke putih.
2. Login **operator_siti** → menu Kategori dan Pengguna tidak ada.
3. Klik **foto profil** di kanan atas → panel profil muncul dari kanan → unggah foto → foto langsung tampil di menu samping.
4. **Data Barang** → Tambah barang (popup, halaman tidak berpindah) + gambar → kode otomatis AT-002, ada pratinjau gambar. Klik tombol burger untuk menutup/membuka sidebar.
5. **Barang Masuk** → catat 5 unit → stok bertambah.
6. **Barang Keluar** → ajukan tanpa foto (ditolak) → ajukan dengan foto (Pending, stok tetap).
7. Login **manager_marco** → tanda jumlah Pending di menu, tidak ada tombol tambah barang.
8. Buka permintaan → lihat foto → **Tolak** tanpa alasan (ditolak) → **Setujui** (stok berkurang, nama Manager tercatat).
9. Login **admin_ohim** → menu Pengguna menampilkan 3 role; role akun sendiri terkunci.
10. Terminal → `php artisan test` → 75 test lulus.

## B. Pertanyaan per Unit Kompetensi

### B1. J.620100.004.02 — Menggunakan Struktur Data

**Inti unit:** memilih dan memakai struktur data yang tepat (array, array asosiatif, objek, koleksi) untuk menyimpan dan mengolah data.

| Struktur data | Lokasi | Kegunaan |
|---|---|---|
| Array (list) | `Barang::STATUS_BARANG`, `BarangKeluar::VERIFIKASI`, `User::ROLES` | Daftar nilai sah sesuai kamus data; dipakai di validasi dan pilihan form |
| Array asosiatif | `Barang::PREFIX_KATEGORI` | Memetakan nama kategori ke prefix kode, misalnya `'Alat Tulis' => 'AT'` |
| Array asosiatif bersarang | `User::INFO_ROLE` | Setiap role → ikon, warna, keterangan; dipakai di halaman Pengguna |
| Collection | `Barang::generateKode()` | `pluck` → `map` → `max` untuk mencari nomor kode terbesar |
| Penggabungan koleksi | `DashboardController::aktivitasTerbaru()` | `concat` barang masuk + keluar, lalu `sortByDesc` dan `take` |
| Objek & relasi | `app/Models/*.php` | Satu baris tabel = satu objek; relasi `hasMany` / `belongsTo` |

**1. Struktur data apa saja yang dipakai?**
Array untuk daftar nilai tetap, array asosiatif untuk pemetaan (prefix kode, info role), Collection Laravel untuk mengolah hasil query, dan objek model untuk mewakili baris tabel.

**2. Apa beda array biasa dan array asosiatif?**
Array biasa diakses dengan indeks angka: `STATUS_BARANG[0]` = `'Baik'`. Array asosiatif diakses dengan kunci teks: `PREFIX_KATEGORI['Furnitur']` = `'FR'`.

**3. Kenapa daftar status disimpan sebagai konstanta?**
Supaya ada satu sumber kebenaran. Daftar yang sama dipakai untuk validasi (`Rule::in(Barang::STATUS_BARANG)`) dan pilihan di form. Bila ada perubahan, cukup ubah satu tempat.

**4. Jelaskan cara kerja kode barang otomatis.**
`Barang::generateKode()`: ambil prefix kategori (misal `AO`) → `pluck` semua kode `AO-...` menjadi `[AO-001, AO-003, AO-007]` → `map` ambil angkanya `[1, 3, 7]` → `max` = 7 (bila kosong `?? 0`) → `sprintf('%s-%03d', 'AO', 8)` = `AO-008`.

**5. Kenapa memakai Collection, bukan perulangan `for`?**
Hasilnya sama, tetapi Collection lebih ringkas dan mudah dibaca karena setiap langkah punya nama yang jelas (ambil, ubah, cari maksimum).

**6. Kenapa kolom `role` dan `verifikasi` bertipe ENUM?**
Agar database hanya menerima nilai yang ada di kamus data. Pengamanannya berlapis: validasi di aplikasi dan batasan di database.

**7. Kenapa ada `id_barang` (angka) dan `kode_barang` (teks)?**
Primary key angka lebih efisien untuk relasi dan indeks. `kode_barang` adalah kode yang mudah dibaca manusia dan bersifat unik.

**8. Apa itu relasi 1:N di program ini?**
Satu kategori memiliki banyak barang (`Kategori::barang()` = `hasMany`), satu barang dimiliki satu kategori (`Barang::kategori()` = `belongsTo`). Begitu juga barang–transaksi dan user–transaksi.

### B2. J.620100.009.01 — Menggunakan Spesifikasi Program

**Inti unit:** memahami spesifikasi (kebutuhan dan rancangan) lalu menerapkannya menjadi program.

Bukti: hak akses, aturan bisnis, dan alur di dokumen 02 Bagian 3; rancangan database di dokumen 01; penerapan di `routes/web.php`, `CekRole`, Form Request, `StokService`, dan migration.

**1. Apa kebutuhan utama aplikasi ini?**
Mencatat barang dan kategori, mencatat barang masuk (stok bertambah), mengajukan barang keluar dengan foto bukti yang disetujui Manager (stok berkurang), mengelola pengguna, mengunggah gambar, serta menampilkan ringkasan dan riwayat.

**2. Apa input, proses, dan output fitur barang keluar?**
- Input: barang, tanggal, jumlah, pemohon, tujuan, kondisi, foto.
- Proses: validasi (wajib, jumlah ≥ 1, tidak melebihi stok, foto JPG/PNG/WEBP ≤ 2 MB), simpan foto dengan nama acak, simpan status Pending; saat disetujui, stok dicek ulang lalu dikurangi dalam satu transaksi.
- Output: data permintaan dan statusnya, foto bukti, stok terbaru, pesan sukses / gagal.

**3. Bagaimana hak akses diterapkan?**
Route dikelompokkan dengan middleware `role:Admin`, `role:Operator`, `role:Manager`, atau `role:Admin,Operator`. `CekRole::handle()` memeriksa role; bila tidak sesuai, server mengembalikan 403. Tombol yang tidak berhak juga disembunyikan.

**4. Kenapa ada tiga role, apa bedanya?**
Agar setiap tugas punya penanggung jawab: Admin = pengelola sistem, Operator = pencatat, Manager = pemberi persetujuan yang hanya bisa melihat. Pada rancangan awal hanya ada Admin dan Manager, dan persetujuan Manager tidak tercatat di database.

**5. Kenapa Manager tidak boleh menambah atau mengeluarkan barang?**
Karena Manager adalah pemberi persetujuan. Bila bisa mencatat, ia dapat mengajukan lalu menyetujui permintaannya sendiri. Dibatasi di route dan dibuktikan oleh `HakAksesTest`.

**6. Program berbeda dari rancangan awal, kenapa?**
Saat implementasi ditemukan masalah: password VARCHAR(50) tidak muat untuk hash, data sampel merujuk barang yang tidak ada, persetujuan tidak tercatat, belum ada fitur gambar. Rancangan direvisi dan semua perubahan tercatat di dokumen 01 Bagian 6.

**7. Apa batasan sistem?**
Kondisi barang dicatat per jenis (bukan per unit) dan fitur peminjaman / pengembalian belum ada. Keduanya bahan pengembangan berikutnya.

### B3. J.620100.010.01 — Perintah Eksekusi Berbasis Teks, Grafik, dan Multimedia

**Inti unit:** menjalankan program melalui perintah teks dan menampilkan hasil secara grafis, termasuk multimedia.

| Jenis | Contoh |
|---|---|
| Perintah teks | `php artisan migrate:fresh --seed`, `php artisan storage:link`, `php artisan serve`, `php artisan test`, `php artisan route:list`, `php artisan tinker`, `npm run build`, `composer install`, `vendor/bin/pint`, `git push` |
| Grafis | Halaman web Blade + Tailwind, grafik batang stok per kategori dan komposisi status di dashboard |
| Multimedia | Gambar: unggah foto profil, gambar barang, foto bukti, dengan pratinjau. Animasi: siluet dan kartu login muncul perlahan, cahaya apel berdenyut, partikel melayang, efek lingkaran saat ganti tema. Audio: suara suasana di halaman login (`<audio>`). Musik: lagu Bad Apple!! (MP3) dengan tombol putar / jeda. Ikon SVG, mode gelap, fitur cetak |

**1. Bagaimana menjalankan aplikasi dari awal?**
Nyalakan MySQL di XAMPP → `php artisan migrate:fresh --seed` → `php artisan storage:link` → `npm run build` → `php artisan serve` → buka `http://localhost:8000`.

**2. Apa itu `php artisan`?**
Alat perintah bawaan Laravel untuk menjalankan tugas: membuat tabel (`migrate`), mengisi data (`db:seed`), menjalankan server (`serve`), pengujian (`test`), melihat route (`route:list`), dan lainnya.

**3. Apa beda `migrate`, `migrate:fresh`, dan `--seed`?**
`migrate` menjalankan migration yang belum dijalankan (data aman). `migrate:fresh` menghapus semua tabel lalu membuat ulang (data hilang). `--seed` sekaligus mengisi data sampel.

**4. Apa fungsi `npm run build`?**
Menjalankan Vite untuk memproses Tailwind CSS dan JavaScript menjadi file siap pakai di `public/build`.

**5. Bagaimana alur unggah gambar?**
Form `enctype="multipart/form-data"` → JavaScript menampilkan pratinjau (`URL.createObjectURL`) → Form Request memvalidasi (`image`, `mimes:jpg,jpeg,png,webp`, `max:2048`) → `GambarService::simpan()` menyimpan dengan nama acak ke folder sesuai jenis → lokasi file disimpan di kolom database → ditampilkan lewat `foto_url` / `gambar_url`.

**6. Kenapa gambar tidak disimpan langsung di database?**
Database hanya menyimpan lokasi file (teks pendek), file gambarnya di folder penyimpanan. Database tetap kecil dan cepat, dan gambar dikirim langsung ke browser.

**7. Apa fungsi `php artisan storage:link`?**
Membuat jalan pintas dari `public/storage` ke `storage/app/public` agar gambar bisa dibuka dari browser lewat alamat `/storage/...`.

**8. Kenapa versi online memakai Supabase Storage?**
Folder aplikasi di Vercel hanya bisa dibaca dan `/tmp` dikosongkan sewaktu-waktu, sehingga file unggahan akan hilang. Cukup mengubah `UPLOAD_DISK` menjadi `s3` tanpa mengubah kode, karena semua penyimpanan gambar lewat `GambarService`.

**9. Kenapa ikon tidak diambil dari internet (CDN)?**
Agar aplikasi tetap tampil sempurna tanpa koneksi internet, misalnya saat ujian.

**10. Mana bagian audio di aplikasi ini?**
Halaman login. Tombol **Putar suara** memutar file `public/audio/pagi.wav` (kicau burung), `siang.wav` (angin dan tonggeret), `sore.wav` (lonceng angin), atau `malam.wav` (jangkrik) sesuai suasana. Diputar dengan tag `<audio loop>` dan dikendalikan JavaScript `suasana.js` fungsi `putar()`.

**11. Kenapa suara tidak langsung berbunyi saat halaman dibuka?**
Browser modern memblokir suara yang diputar otomatis sebelum pengguna berinteraksi. Karena itu suara diputar lewat tombol, sekaligus agar tidak mengganggu.

**12. Dari mana file suaranya? Apakah ada hak cipta?**
Dibuat sendiri secara sintetis dengan program Python (gelombang sinus untuk kicau dan jangkrik, *noise* untuk angin), format WAV 16 kHz, sehingga bebas hak cipta.

**13. Bagaimana animasi login tahu sekarang pagi atau malam?**
`App\Support\Suasana::dariJam()` menentukan waktu dari jam server untuk tampilan awal, lalu `suasana.js` memakai jam perangkat dan memeriksanya setiap detik. Hasilnya dipasang di atribut `data-waktu`; salam, kalimat, dan file audio ikut diganti, dan CSS mengubah warna cahaya di sekitar apel berdasarkan atribut itu.

**14. Bagaimana mode gelap bekerja?**
Tombol memanggil `gantiTema()` di `tema.js` yang menambah/menghapus class `dark` pada `<html>` dan menyimpan pilihan di `sessionStorage`. Bila belum memilih, tema awal mengikuti jam: malam gelap, siang terang. Semua warna Tailwind berupa variabel CSS; saat ada class `dark`, nilai variabelnya ditukar di `app.css`, sehingga semua halaman ikut berubah tanpa mengubah tiap halaman. Pergantiannya memakai View Transitions API: tema baru muncul sebagai lingkaran yang membesar dari tombol.

**15. Bagaimana siluet bisa hitam di mode terang dan putih di mode gelap dengan satu gambar?**
Gambar `public/images/siluet.png` transparan dan dipakai sebagai *mask* CSS. Bentuk siluet diambil dari gambar, sedangkan warnanya dari variabel `--tinta`: hitam di mode terang, putih di mode gelap. Karena warnanya CSS, perubahannya bisa dianimasikan dengan halus.

**16. Bagaimana musik Bad Apple!! diputar dan kenapa bisa lanjut saat pindah halaman?**
File `public/audio/bad-apple.mp3` diputar elemen `<audio loop preload="none">`; tombol not musik memanggil `play()` / `pause()` di `resources/js/musik.js`. Saat meninggalkan halaman, posisi lagu (`currentTime`) disimpan di `sessionStorage`, lalu di halaman berikutnya lagu dilanjutkan dari detik itu. `preload="none"` membuat file 5 MB baru diunduh ketika diputar, sehingga halaman tetap cepat.

**17. Kenapa karakter pindah sisi saat ganti tema?**
Mengikuti konsep siang dan malam: mode terang (siang) karakter di kiri dan dibalik dengan `scale: -1 1` agar menghadap kartu; mode gelap (malam) karakter di kanan. Diatur di CSS `.siluet` dan `html.dark .siluet`.

**18. Kenapa saat pindah menu halaman tidak dimuat ulang?**
Memakai library **Hotwire Turbo** (`resources/js/app.js`). Turbo mengambil halaman tujuan dengan `fetch`, lalu hanya mengganti isi `<body>`. URL tetap berubah dan tombol Back browser tetap berfungsi. Keuntungannya lebih cepat dan musik tidak terputus.

**19. Bagaimana form tambah / ubah / detail bisa muncul sebagai popup?**
Tautannya diberi `data-turbo-frame="modal"`, sehingga Turbo mengirim header `Turbo-Frame: modal`. Layout memeriksa header itu: bila halamannya ditandai `@section('popup', true)`, yang dikirim hanya isi halaman di dalam `<turbo-frame id="modal">`, lalu ditampilkan dalam `<dialog>`. Route dan controller tidak berubah, dan bila dibuka langsung halaman tetap tampil utuh.

**20. Setelah simpan di popup, bagaimana popup tertutup?**
Controller mengarahkan ke halaman daftar yang tidak punya isi popup. Turbo memicu event `turbo:frame-missing`; `popup.js` menutup popup lalu menampilkan halaman daftar beserta pesan suksesnya. Bila validasi gagal, Laravel kembali ke form sehingga error tampil di dalam popup.

**21. Kenapa kotak tetap terlihat jelas di atas karakter latar?**
Kotak (kartu) mengikuti tema dan latarnya tidak tembus pandang, sehingga karakter di belakang hanya tampak di sela-sela kotak. Di mode gelap kotak diberi garis dan cahaya putih tipis (`html.dark main .card` di `app.css`) agar tepinya tetap terlihat.

**22. Bagaimana efek percikan cahaya dibuat?**
Layout membuat 34 elemen kecil dengan posisi, ukuran, durasi, dan jeda acak (`mt_rand` dengan seed tetap). CSS `@keyframes percikan-naik` menggerakkan tiap titik naik setinggi layar sambil muncul lalu memudar. Warnanya `--color-slate-900`, sehingga hitam di mode terang dan putih di mode gelap.

### B4. J.620100.016.01 — Menulis Kode sesuai Guidelines dan Best Practices

**Inti unit:** menulis kode yang rapi, konsisten, aman, dan mudah dirawat.

| Praktik | Penerapan |
|---|---|
| Standar penulisan | PSR-12, dirapikan otomatis dengan Laravel Pint |
| Arsitektur | MVC; validasi di Form Request; logika stok di `StokService`; logika gambar di `GambarService` |
| Penamaan | camelCase untuk method (`generateKode`), PascalCase untuk class, snake_case untuk kolom |
| Password | Hash bcrypt (cast `'hashed'` di model User) |
| CSRF | `@csrf` di setiap form; error 419 ditangani dengan pesan jelas |
| XSS | Output `{{ }}` di-escape; `@js()` untuk teks di JavaScript |
| SQL Injection | Eloquent / Query Builder (parameter binding) |
| Hak akses | Middleware `auth` + `CekRole`; pemisahan tugas tiga role |
| Unggah file | Validasi isi file dan ukuran, nama acak, file lama dihapus |
| Konsistensi data | `DB::transaction()` + `lockForUpdate()` |
| Konfigurasi rahasia | `.env` (tidak di-commit) |

**1. Guidelines apa yang diikuti?**
PSR-12 untuk gaya penulisan PHP (dicek dengan Laravel Pint) dan konvensi Laravel (MVC, Form Request, Eloquent, penamaan).

**2. Apa itu MVC?**
Model mengurus data (`Barang.php`), View menampilkan halaman (`barang/index.blade.php`), Controller memproses permintaan (`BarangController.php`). Alurnya: Route → Middleware → Controller → Model → View.

**3. Bagaimana password disimpan?**
Di-hash dengan bcrypt melalui cast `'password' => 'hashed'`. Saat login, `Auth::attempt()` mencocokkan password dengan hash-nya. Hash tidak dapat dikembalikan ke password asli.

**4. Bagaimana mencegah SQL injection, CSRF, dan XSS?**
SQL injection: query lewat Eloquent dengan parameter binding. CSRF: token `@csrf` di setiap form. XSS: output Blade `{{ }}` otomatis di-escape.

**5. Kenapa ada `StokService` dan `GambarService`?**
Prinsip *single responsibility* dan DRY. Stok dan gambar diubah dari banyak tempat; aturannya cukup ditulis sekali dan mudah diuji tersendiri.

**6. Apa fungsi `DB::transaction()` dan `lockForUpdate()`?**
Transaksi memastikan beberapa perubahan berhasil semua atau batal semua (misalnya pengurangan stok dan status Disetujui). `lockForUpdate()` mengunci baris selama transaksi agar proses lain menunggu.

**7. Kenapa data rahasia disimpan di `.env`?**
Agar password database dan `APP_KEY` tidak tertulis di kode dan tidak ikut ter-upload ke GitHub.

### B5. J.620100.017.02 — Mengimplementasikan Pemrograman Terstruktur

**Inti unit:** menyusun program dengan urutan, percabangan, perulangan, serta fungsi / prosedur.

| Struktur | Contoh | Lokasi |
|---|---|---|
| Urut | Validasi → simpan → kurangi stok → redirect | `BarangKeluarController::setujui()` |
| `if` | Stok cukup atau tidak | `StokService::kurangi()` |
| `if / elseif` | Koreksi barang masuk: selisih positif tambah stok, negatif kurangi | `BarangMasukController::update()` |
| `foreach` + `if` | Menentukan pagi / siang / sore / malam dari jam | `Suasana::dariJam()` |
| `match` | Warna badge sesuai status; aksi cepat per role | `components/badge.blade.php`, `dashboard.blade.php` |
| `foreach` | Mengisi data barang | `DatabaseSeeder::run()` |
| `@foreach` / `@forelse` | Menampilkan baris tabel | `resources/views/*/index.blade.php` |
| Fungsi (return) | `cukup(Barang $barang, int $jumlah): bool` | `StokService` |
| Prosedur (`void`) | `pastikanJumlahValid(int $jumlah): void` | `StokService` |
| Penanganan error | `try { ... } catch (StokTidakCukupException $e)` | `BarangKeluarController::setujui()` |

**1. Apa itu pemrograman terstruktur?**
Menyusun program dari urutan, percabangan, dan perulangan, serta memecahnya menjadi fungsi kecil dengan satu tugas supaya mudah dibaca, diuji, dan diperbaiki.

**2. Contoh percabangan:**
```php
if ($jam < 11) {
    return 'Selamat pagi';
} elseif ($jam < 15) {
    return 'Selamat siang';
} elseif ($jam < 18) {
    return 'Selamat sore';
}

return 'Selamat malam';
```

**3. Jelaskan `StokService::kurangi()` baris per baris.**
```php
public function kurangi(Barang $barang, int $jumlah): Barang
{
    $this->pastikanJumlahValid($jumlah);          // 1. jumlah harus > 0

    if (! $this->cukup($barang, $jumlah)) {       // 2. cek stok
        throw new StokTidakCukupException($barang, $jumlah);   // 3. hentikan dengan error
    }

    $barang->stok -= $jumlah;                     // 4. kurangi stok
    $barang->save();                              // 5. simpan

    return $barang;                               // 6. kembalikan data terbaru
}
```

**4. Apa beda fungsi dan prosedur?**
Fungsi mengembalikan nilai (`cukup()` → `true`/`false`). Prosedur menjalankan aksi tanpa nilai kembali (`pastikanJumlahValid()` bertipe `void`).

**5. Apa fungsi `try ... catch`?**
Menangkap error supaya program tidak berhenti. Di `setujui()`, bila stok tidak cukup, exception ditangkap dan pengguna melihat pesan "Stok ... tidak mencukupi".

### B6. J.620100.023.02 — Membuat Dokumen Kode Program

**Inti unit:** mendokumentasikan kode dan membuat dokumen pendukung.

Bukti: PHPDoc di setiap model (`@property`) dan method penting (`@throws`); komentar penjelasan pada logika penting (misalnya `kunciPermintaanPending()`, `api/index.php`); komentar Blade `{{-- --}}`; dokumen 01–05 dan `README.md`.

**1. Bagaimana mendokumentasikan kode?**
Dengan PHPDoc di atas class dan method (fungsi, parameter, error), komentar untuk menjelaskan *kenapa*, dan dokumen terpisah untuk cara menjalankan, struktur, dan keputusan desain.

**2. Contoh PHPDoc:**
```php
/**
 * @throws StokTidakCukupException jika stok kurang dari jumlah yang diminta
 */
public function kurangi(Barang $barang, int $jumlah): Barang
```

**3. Kapan perlu komentar?**
Untuk menjelaskan alasan yang tidak terlihat dari kode, misalnya kenapa baris dikunci atau kenapa cache diarahkan ke `/tmp`. Kode yang namanya sudah jelas (`generateKode()`, `stokMenipis()`) tidak perlu dikomentari.

**4. Dokumen apa saja yang dibuat?**
01 Dokumen Rancangan (ERD, desain database, relasi, kamus data, data sampel), 02 Dokumentasi Program, 03 Persiapan Asesor, 04 Daftar Akun, 05 Tutorial Deploy.

### B7. J.620100.025.02 — Melakukan Debugging

**Inti unit:** menemukan, menganalisis, dan memperbaiki kesalahan program. Daftar lengkap 14 bug ada di dokumen 02 Bagian 9.

| Bug | Jenis | Cara ditemukan | Perbaikan |
|---|---|---|---|
| `protected $tabl` di model Kategori | Salah ketik | Error "table kategoris not found" | `$table = 'kategori'` |
| Username factory berisi titik | Data uji tidak valid (*flaky*) | Test dijalankan berulang | Format username diganti |
| Zona waktu `UTC` | Logika / konfigurasi | Tanggal verifikasi dini hari salah hari | `Asia/Jakarta` |
| Status Pending dicek di luar transaksi | *Race condition* | Analisis alur dua persetujuan bersamaan | Baris dikunci, dicek ulang di transaksi |
| Enum role gagal di PostgreSQL | Perbedaan database | Migration diuji di PostgreSQL | Constraint dibuat ulang manual |
| Halaman "419 Page Expired" | Sesi kedaluwarsa | Laporan pengguna, diuji dengan `curl` | Error 419 ditangani dengan pesan |

**1. Apa saja jenis kesalahan program?**
*Syntax error* (penulisan salah, misalnya "Unmatched '}'"), *runtime error* (muncul saat berjalan, misalnya tabel tidak ditemukan), dan *logic error* (berjalan tetapi hasil salah, misalnya stok berkurang dua kali).

**2. Langkah debugging?**
Reproduksi masalah → baca pesan error dan *stack trace* → persempit penyebab (`dd()`, `tinker`, log) → perbaiki penyebab utamanya → uji ulang dan jalankan `php artisan test`.

**3. Alat debugging apa yang dipakai?**
Halaman error Laravel (`APP_DEBUG=true` di lokal), `storage/logs/laravel.log`, `dd()` / `dump()`, `php artisan route:list`, `php artisan tinker`, menu Logs Vercel, `curl`, dan PHPUnit.

**4. Ceritakan bug yang paling sulit.**
*Race condition* pada persetujuan barang keluar: status Pending dicek sebelum transaksi, sehingga dua persetujuan bersamaan mengurangi stok dua kali. Bug ini tidak terlihat saat dicoba sendiri. Perbaikannya: baris permintaan dikunci dengan `lockForUpdate()` dan dicek ulang di dalam transaksi (`kunciPermintaanPending()`).

**5. Pernah mengalami error 419? Apa penyebabnya?**
Pernah. Error 419 muncul bila token CSRF di formulir tidak cocok dengan sesi, biasanya karena halaman dibiarkan terbuka lebih dari 120 menit. Sekarang ditangani di `bootstrap/app.php`: pengguna dikembalikan ke halaman sebelumnya dengan pesan jelas dan isian tetap terisi. Dibuktikan oleh `SesiKedaluwarsaTest`.

**6. Pernah error 500 di website online?**
Pernah: tabel belum dibuat di database Supabase. Ditemukan dengan memeriksa status migration, lalu diselesaikan dengan `php artisan migrate --seed --env=supabase`.

**7. Kenapa `APP_DEBUG` harus `false` di server online?**
Karena halaman error detail menampilkan kode, path file, dan konfigurasi yang dapat dimanfaatkan penyerang.

### B8. J.620100.033.02 — Melaksanakan Pengujian Unit Program

**Inti unit:** merancang dan menjalankan pengujian untuk memastikan program bekerja sesuai harapan.

Bukti: **75 test (257 assertion)** PHPUnit. Rincian per file ada di dokumen 02 Bagian 10.

**1. Apa itu unit test?**
Pengujian bagian terkecil program (satu fungsi) secara terpisah, misalnya `StokService::kurangi()` tanpa membuka halaman web.

**2. Apa beda Unit test dan Feature test?**
Unit test menguji satu class / method langsung (`tests/Unit`). Feature test menguji fitur utuh seperti pengguna sungguhan: mengirim request ke URL lalu memeriksa respons dan database (`tests/Feature`).

**3. Bagaimana menjalankan test?**
`php artisan test`. Satu file saja: `php artisan test --filter=StokServiceTest`.

**4. Kenapa test tidak merusak data asli?**
Test memakai SQLite di memori (`phpunit.xml`) dan `RefreshDatabase`, serta penyimpanan file palsu `Storage::fake()`.

**5. Jelaskan satu test case lengkap.**
`test_manager_menyetujui_mengurangi_stok_dan_mencatat_verifikator`:
- Persiapan: barang stok 10, permintaan keluar 4 oleh Operator.
- Aksi: Manager mengirim `PATCH` ke route `barang-keluar.setujui`.
- Pemeriksaan: status Disetujui, `id_verifikator` = Manager, tanggal hari ini, stok 6.

**6. Apa itu assertion?**
Pernyataan yang harus benar agar test lulus, misalnya `assertSame(6, $barang->fresh()->stok)`, `assertForbidden()`, `assertSessionHasErrors('foto')`, `assertDatabaseHas(...)`, `expectException(...)`.

**7. Skenario apa saja yang diuji?**
Bukan hanya skenario berhasil, tetapi juga gagal dan batas: stok tepat habis, stok kurang, jumlah 0, foto tidak diunggah, file PDF / 3 MB, tolak tanpa alasan, verifikasi ulang, akses role yang tidak berhak (403), token kedaluwarsa (419).

**8. Bagaimana menguji unggah gambar tanpa file sungguhan?**
Dengan `Storage::fake()` dan `UploadedFile::fake()->image('bukti.jpg')`, lalu memeriksa file tersimpan (`assertExists`) atau terhapus (`assertMissing`).

**9. Apa beda black box dan white box testing?**
Black box menguji dari luar berdasarkan input–output (Feature test). White box menguji dengan mengetahui logika di dalam kode (Unit test `StokService` yang menguji setiap cabang `if`). Keduanya dipakai.

## C. Pertanyaan Umum tentang Aplikasi dan Laravel

**1. Kenapa memilih Laravel?**
Laravel sudah menyediakan routing, autentikasi, validasi, Eloquent ORM, migration, proteksi CSRF, penyimpanan file, dan PHPUnit, dengan struktur MVC yang rapi.

**2. Apa itu route, middleware, dan controller?**
Route memetakan URL ke controller. Middleware menyaring request sebelum masuk controller (`auth`, `role`). Controller memproses request.

**3. Apa itu Eloquent, migration, seeder, dan Blade?**
Eloquent: akses tabel sebagai objek PHP. Migration: kode pembuat struktur tabel. Seeder: kode pengisi data awal. Blade: template tampilan Laravel.

**4. Bagaimana proses login?**
Form mengirim username dan password → validasi → `Auth::attempt()` mencocokkan hash → bila cocok, sesi dibuat ulang dan diarahkan ke dashboard; bila tidak, kembali dengan pesan error.

**5. Kenapa stok barang keluar tidak langsung berkurang?**
Karena permintaan bisa ditolak Manager. Stok berkurang setelah disetujui agar selalu sesuai barang yang benar-benar ada.

**6. Bagaimana bila Operator salah input barang masuk?**
Admin mengoreksi lewat menu Barang Masuk dan stok menyesuaikan otomatis. Bila stok dari transaksi itu sudah terpakai, koreksi ditolak agar stok tidak negatif.

**7. Kenapa pengajuan barang keluar tidak bisa diubah / dihapus?**
Agar menjadi jejak audit. Pengajuan keliru cukup ditolak Manager dengan alasan.

**8. Bagaimana aplikasi dibuat online?**
Kode di GitHub, aplikasi di Vercel (runtime PHP), database PostgreSQL dan gambar di Supabase. Setiap `git push`, Vercel otomatis men-deploy.

**9. Lokal MySQL, online PostgreSQL, tidak masalah?**
Laravel mendukung keduanya. Semua test sudah dijalankan di PostgreSQL dan lulus. Dua perbedaan ditemukan dan diperbaiki: `LIKE` yang peka huruf besar/kecil (diganti `whereLike()`) dan cara mengubah enum di migration.

**10. Apa rencana pengembangan berikutnya?**
Peminjaman dan pengembalian barang, kondisi per unit, laporan per periode (PDF / Excel), notifikasi ke Manager, kompresi gambar otomatis, dan *audit log*.

## D. Pertanyaan Jebakan / Kritis

**1. Bila dua Manager menyetujui permintaan yang sama bersamaan?**
Hanya satu yang berhasil. Baris permintaan dikunci di dalam transaksi; Manager kedua menunggu, lalu mendapati status sudah Disetujui dan menerima pesan "sudah diverifikasi sebelumnya".

**2. Bila Operator mengakali URL, misalnya membuka `/user`?**
`CekRole` memeriksa role di server untuk setiap request → 403. Menyembunyikan tombol hanya kenyamanan; pengamanan sebenarnya di server.

**3. Bila nilai role diubah lewat *inspect element*?**
Server tetap memvalidasi role (`Rule::in(User::ROLES)`), dan Admin tidak dapat mengubah role akunnya sendiri meskipun data form diubah.

**4. Stok bisa negatif?**
Tidak. Ada validasi saat pengajuan, pengecekan ulang di `StokService::kurangi()` saat disetujui, dan transaksi yang di-*rollback* bila stok kurang.

**5. Password bisa dilihat di database?**
Tidak, hanya hash bcrypt yang tersimpan.

**6. Bila ada yang mengunggah script PHP yang diganti nama menjadi `.jpg`?**
Ditolak. Aturan `image` dan `mimes` memeriksa isi file (MIME type), bukan hanya namanya. File yang lolos disimpan dengan nama acak dan ekstensi sesuai isinya, sehingga tidak dijalankan sebagai program. Dibuktikan oleh `test_file_bukan_gambar_atau_lebih_dari_2mb_ditolak`.

**7. Bila foto diganti, apakah file lama menumpuk?**
Tidak. `GambarService::ganti()` menghapus file lama setelah file baru tersimpan; saat data dihapus, fotonya ikut dihapus.

**8. Kenapa validasi tidak cukup di tampilan (JavaScript) saja?**
Tampilan mudah diakali. Validasi di server (Form Request) adalah pengamanan utama; atribut `required` / `min` di form hanya membantu pengguna.

**9. Apa kelemahan aplikasi?**
Kondisi barang per jenis, belum ada peminjaman, laporan baru sebatas cetak browser, dan layanan gratis punya batasan (Supabase di-*pause* bila tidak dipakai seminggu).

## E. Lokasi Kode yang Perlu Dihafal

| Yang mungkin diminta | File | Method |
|---|---|---|
| Login | `app/Http/Controllers/AuthController.php` | `login()` |
| Middleware role | `app/Http/Middleware/CekRole.php` | `handle()` |
| Route & hak akses | `routes/web.php` | — |
| Kode barang otomatis | `app/Models/Barang.php` | `generateKode()`, `prefixKategori()` |
| Logika stok | `app/Services/StokService.php` | `tambah()`, `kurangi()`, `cukup()` |
| Unggah gambar | `app/Services/GambarService.php` | `simpan()`, `ganti()`, `hapus()`, `url()` |
| Verifikasi | `app/Http/Controllers/BarangKeluarController.php` | `setujui()`, `tolak()`, `kunciPermintaanPending()` |
| Validasi stok & foto | `app/Http/Requests/BarangKeluarRequest.php` | `rules()`, `after()` |
| Profil | `app/Http/Controllers/ProfilController.php` | `update()` |
| Penanganan error 419 | `bootstrap/app.php` | `withExceptions` |
| Struktur tabel | `database/migrations/` | — |
| Data sampel | `database/seeders/DatabaseSeeder.php` | `run()` |
| Dashboard | `app/Http/Controllers/DashboardController.php` | `index()`, `aktivitasTerbaru()` |
| Suasana login & salam | `app/Support/Suasana.php` | `dariJam()`, `salam()` |
| Audio & animasi login | `resources/js/suasana.js` | `pasangSuasanaLogin()`, `putar()` |
| Mode gelap | `resources/js/tema.js` | `gantiTema()` |
| Musik Bad Apple!! | `resources/js/musik.js` | `pasangMusik()` |
| Test | `tests/Unit`, `tests/Feature` | — |

## F. Daftar Istilah

| Istilah | Arti |
|---|---|
| Framework | Kerangka kerja berisi struktur dan fitur siap pakai |
| MVC | Model–View–Controller: pemisahan data, tampilan, dan logika |
| ORM / Eloquent | Akses tabel database sebagai objek PHP |
| Migration / Seeder | Kode pembuat struktur tabel / pengisi data awal |
| Middleware | Penyaring request sebelum masuk controller |
| Form Request | Class berisi aturan validasi form |
| CRUD | Create, Read, Update, Delete |
| Hash (bcrypt) | Enkripsi satu arah untuk password |
| CSRF / 419 | Serangan form palsu; error 419 bila token form tidak cocok |
| XSS | Penyisipan script berbahaya |
| SQL Injection | Penyisipan perintah SQL lewat input |
| Transaksi database | Sekumpulan perubahan yang berhasil semua atau batal semua |
| Race condition | Kesalahan saat dua proses berjalan bersamaan dan saling memengaruhi |
| Foreign key | Kolom yang merujuk primary key tabel lain |
| Multipart form | Format form yang dapat membawa file |
| MIME type | Jenis isi file, misalnya `image/jpeg` |
| Symbolic link | Jalan pintas folder (`php artisan storage:link`) |
| Object storage (S3) | Layanan penyimpanan file di internet, misalnya Supabase Storage |
| Row Level Security | Fitur PostgreSQL untuk membatasi akses data |
| Unit / Feature test | Pengujian satu fungsi / satu fitur utuh |
| Assertion | Pernyataan yang harus benar agar test lulus |
| Deploy | Memasang aplikasi ke server agar dapat diakses online |
