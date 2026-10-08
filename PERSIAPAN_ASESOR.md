# Persiapan Tanya Jawab Asesor

| Keterangan | Isi |
|---|---|
| Peserta | Renaissance Ricarda Sugiarto Putra |
| Skema | Pemrogram Junior (Junior Coder) |
| Aplikasi | Inventaris Gudang Sekolah (Laravel 12) — 3 role: Admin, Operator, Manager |
| Pasangan dokumen | `DOKUMENTASI.md` (penjelasan teknis lengkap) dan `TUTORIAL_DEPLOY.md` (cara online-kan) |

Dokumen ini berisi penjelasan yang bisa langsung diucapkan saat presentasi, pertanyaan yang kemungkinan diajukan asesor untuk tiap unit kompetensi, dan contoh jawabannya. Setiap jawaban menyebut **lokasi bukti di kode**, supaya kamu bisa langsung membuka file tersebut di depan asesor.

**Cara menjawab yang baik:**

1. Jawab inti pertanyaannya dulu dalam satu kalimat.
2. Tunjukkan buktinya: buka file dan baris kodenya, atau peragakan di aplikasi.
3. Jelaskan alasannya ("kenapa begitu").
4. Kalau tidak tahu, jujur saja, lalu jelaskan cara kamu akan mencari jawabannya (dokumentasi Laravel, log error, debugging). Asesor menilai cara berpikir, bukan hafalan.

---

## Bagian A — Presentasi Pembuka (± 2 menit)

Contoh yang bisa diucapkan:

> "Aplikasi saya bernama **Inventaris Gudang Sekolah**, dibuat dengan framework **Laravel 12** dan database MySQL untuk lokal serta PostgreSQL (Supabase) untuk versi online.
>
> Masalah yang diselesaikan: pencatatan barang gudang sekolah sering manual, stok tidak jelas, dan barang bisa keluar tanpa persetujuan.
>
> Aplikasi punya tiga role. **Admin** mengelola sistem: pengguna, kategori, dan data barang. **Operator** (petugas gudang) mendaftarkan barang, mencatat barang masuk, dan mengajukan barang keluar. **Manager** (pengawas) memverifikasi, yaitu menyetujui atau menolak, permintaan barang keluar, dan hanya bisa melihat data. Ketiga peran sengaja dipisah (*separation of duties*), supaya tidak ada orang yang menyetujui permintaannya sendiri.
>
> Aplikasi juga mendukung **multimedia**: foto profil, gambar barang, foto bukti barang masuk (opsional), dan foto bukti barang keluar (wajib), yang bisa dilihat Manager sebelum memberi persetujuan.
>
> Aturan utamanya: stok **bertambah** saat barang masuk dicatat, dan baru **berkurang** setelah Manager menyetujui barang keluar. Setiap verifikasi mencatat siapa Manager-nya, tanggalnya, dan alasannya.
>
> Database berisi 5 tabel: `user`, `kategori`, `barang`, `barang_masuk`, `barang_keluar`. Program diuji dengan **62 test PHPUnit** yang semuanya lulus."

### Alur demo singkat

1. Login sebagai **operator_siti** (`operator123`). Tunjukkan bahwa menu Kategori dan Pengguna tidak ada.
2. **Profil Saya** → unggah foto profil, foto langsung tampil di menu samping.
3. **Data Barang** → Tambah barang beserta gambarnya (kode otomatis, misalnya AT-002; ada pratinjau gambar).
4. **Barang Masuk** → catat 5 unit (boleh dengan foto nota), lalu tunjukkan stok bertambah.
5. **Barang Keluar** → ajukan tanpa foto (ditolak), lalu ajukan dengan foto bukti (Pending, stok belum berubah).
6. Logout, login sebagai **manager_marco** (`@admin123`). Ada tanda jumlah Pending di menu; tidak ada tombol tambah barang.
7. Buka permintaan → lihat foto bukti → coba **Tolak** tanpa alasan (ditolak), lalu **Setujui** (stok berkurang, verifikator tercatat).
8. Login sebagai **admin_ohim** (`admin123`) → menu **Pengguna** menampilkan tiga role.
9. Terminal: `php artisan test` → 62 test lulus.

---

## Bagian B — Unit Kompetensi dan Pertanyaannya

### B1. J.620100.004.02 — Menggunakan Struktur Data

**Inti unit:** memilih dan memakai struktur data yang tepat (array, array asosiatif, objek, koleksi) untuk menyimpan dan mengolah data.

**Bukti di program:**

| Struktur data | Lokasi | Kegunaan |
|---|---|---|
| Array (list) konstanta | `app/Models/Barang.php:24` `STATUS_BARANG`, `app/Models/BarangKeluar.php:34` `VERIFIKASI`, `app/Models/User.php:26` `ROLES` | Daftar nilai yang sah sesuai kamus data; dipakai untuk validasi dan pilihan di form |
| Array asosiatif (key ⇒ value) | `app/Models/Barang.php:27` `PREFIX_KATEGORI` | Memetakan nama kategori ke prefix kode, misalnya `'Alat Tulis' => 'AT'` |
| Array asosiatif bersarang | `app/Models/User.php` `INFO_ROLE` | Setiap role memetakan ke array berisi ikon, warna, dan keterangan; dipakai di halaman Pengguna dan form role |
| Collection Laravel | `app/Models/Barang.php:104-113` `generateKode()` | `pluck` → `map` → `max` untuk mencari nomor kode terbesar |
| Penggabungan & pengurutan koleksi | `app/Http/Controllers/DashboardController.php:58-90` | `concat` barang masuk + keluar, lalu `sortByDesc` dan `take` untuk "Aktivitas Terbaru" |
| Objek (model) & relasi | `app/Models/*.php` | Satu baris tabel = satu objek; relasi `hasMany` / `belongsTo` |
| Tipe data kolom database | `database/migrations/` | `integer`, `string(n)`/VARCHAR, `date`, `enum` |

**Pertanyaan yang mungkin muncul:**

**1. Struktur data apa saja yang kamu pakai?**
Array biasa untuk daftar nilai tetap (status barang, verifikasi, role), array asosiatif untuk pemetaan kategori ke prefix kode, Collection Laravel untuk mengolah hasil query, dan objek model untuk mewakili tiap baris tabel.

**2. Apa beda array biasa dan array asosiatif? Tunjukkan contohnya.**
Array biasa diakses dengan indeks angka, misalnya `STATUS_BARANG[0]` bernilai `'Baik'`. Array asosiatif diakses dengan kunci teks, misalnya `PREFIX_KATEGORI['Furnitur']` bernilai `'FR'`. Lihat `Barang.php:24` dan `Barang.php:27`.

**3. Kenapa daftar status disimpan sebagai konstanta, bukan ditulis langsung di banyak tempat?**
Agar ada satu sumber kebenaran. Daftar yang sama dipakai di validasi (`Rule::in(Barang::STATUS_BARANG)`) dan di pilihan form (`@foreach (Barang::STATUS_BARANG ...)`). Kalau ada status baru, cukup ubah di satu tempat.

**4. Jelaskan cara kerja pembuatan kode barang otomatis.**
`generateKode()` di `Barang.php:104`:
1. Ambil prefix kategori, misalnya `AO`.
2. `pluck('kode_barang')` mengambil semua kode berawalan `AO-` menjadi koleksi, misalnya `[AO-001, AO-003, AO-007]`.
3. `map` memotong bagian angkanya lalu mengubahnya menjadi integer: `[1, 3, 7]`.
4. `max()` mengambil nilai terbesar, yaitu 7. Kalau koleksi kosong, hasilnya `null`, dan operator `?? 0` menggantinya dengan 0.
5. `sprintf('%s-%03d', 'AO', 8)` menghasilkan `AO-008` (angka dibuat 3 digit).

**5. Kenapa memakai Collection, bukan perulangan `for` biasa?**
Hasilnya sama, tapi Collection lebih ringkas dan mudah dibaca karena setiap langkah (ambil, ubah, cari maksimum) punya nama yang jelas. Dengan `for`, kita perlu variabel penampung dan pengecekan manual.

**6. Kenapa kolom `verifikasi` dan `role` bertipe ENUM?**
Supaya database hanya menerima nilai yang ada di kamus data (`Pending/Disetujui/Ditolak`, `Admin/Operator/Manager`). Pengamanannya berlapis: validasi di aplikasi dan batasan di database.

**7. Kenapa `id_barang` integer, tapi ada juga `kode_barang`?**
Primary key angka lebih efisien untuk relasi dan indeks. `kode_barang` (VARCHAR, UNIQUE) adalah kode yang mudah dibaca manusia. Dengan begitu, kode bisa berubah (misalnya saat kategori diganti) tanpa merusak relasi.

**8. Apa itu relasi 1:N di program kamu?**
Satu kategori memiliki banyak barang (`Kategori::barang()` = `hasMany`), dan satu barang dimiliki satu kategori (`Barang::kategori()` = `belongsTo`). Begitu juga satu barang punya banyak transaksi masuk/keluar, dan satu user punya banyak transaksi.

---

### B2. J.620100.009.01 — Menggunakan Spesifikasi Program

**Inti unit:** memahami spesifikasi (kebutuhan, rancangan) dan menerapkannya menjadi program yang sesuai.

**Bukti di program:**
- Spesifikasi tertulis: `DOKUMENTASI.md` Bagian 3 (hak akses per role, aturan bisnis, alur barang keluar, batasan sistem).
- Rancangan database (ERD, desain tabel, kamus data, data sampel) diterapkan di `database/migrations/` dan `database/seeders/DatabaseSeeder.php`.
- Hak akses diterapkan di `routes/web.php` dan middleware `app/Http/Middleware/CekRole.php`.
- Aturan bisnis diterapkan di Form Request (`app/Http/Requests/`) dan `app/Services/StokService.php`.

**Pertanyaan yang mungkin muncul:**

**1. Apa kebutuhan utama aplikasi ini?**
Mencatat data barang dan kategori, mencatat barang masuk (stok bertambah), mengajukan barang keluar dengan foto bukti yang harus disetujui Manager (stok berkurang setelah disetujui), mengelola pengguna, mengunggah foto profil dan gambar barang, serta menampilkan ringkasan stok dan riwayat transaksi.

**2. Apa input, proses, dan output pada fitur barang keluar?**
- *Input:* barang, tanggal, jumlah, pemohon, tujuan, kondisi.
- *Proses:* validasi (wajib diisi, jumlah ≥ 1, tidak melebihi stok), simpan dengan status Pending; foto disimpan dengan nama acak; saat Manager menyetujui, stok dicek ulang lalu dikurangi dalam satu transaksi.
- *Output:* data permintaan beserta status verifikasi, stok terbaru, dan pesan sukses atau gagal.

**3. Bagaimana spesifikasi hak akses diterapkan di kode?**
Di `routes/web.php`, route dikelompokkan dengan middleware `role:Admin`, `role:Operator`, `role:Manager`, atau `role:Admin,Operator`. Middleware `CekRole` (`CekRole.php:17-26`) mengecek role user; kalau tidak sesuai, muncul error 403. Tombol yang tidak berhak juga disembunyikan di tampilan.

**4. Program kamu berbeda dari dokumen rancangan awal. Kenapa?**
Saat implementasi saya mengevaluasi rancangan dan menemukan beberapa masalah: password VARCHAR(50) tidak muat untuk hash (60 karakter), data sampel merujuk barang yang tidak ada, dan peran Manager tidak tercatat saat verifikasi. Rancangan direvisi, dan semua perubahan beserta alasannya tercatat di `DOKUMENTASI.md` Bagian 5 "Revisi Dokumen Rancangan". Spesifikasi boleh direvisi, asal perubahannya terdokumentasi dan disetujui.

**5. Kenapa ada tiga role? Apa bedanya?**
Supaya setiap tugas punya penanggung jawab sendiri. Admin = pengelola sistem (pengguna, kategori, data barang, koreksi). Operator = pencatat transaksi. Manager = pengawas yang memberi persetujuan dan hanya bisa melihat. Pada rancangan awal hanya ada Admin dan Manager, dan persetujuan Manager tidak tercatat di database. Sekarang ada kolom `id_verifikator`, `tanggal_verifikasi`, dan `catatan_verifikasi`.

**6. Kenapa Manager tidak boleh menambah atau mengeluarkan barang?**
Karena Manager adalah pemberi persetujuan. Kalau Manager juga bisa mencatat, dia bisa mengajukan lalu menyetujui permintaannya sendiri. Pembatasan ini diterapkan di route (`role:Manager` hanya untuk setujui/tolak) dan dibuktikan oleh `HakAksesTest`.

**6. Apa batasan sistem ini?**
Kondisi barang dicatat per jenis barang, bukan per unit; fitur peminjaman dan pengembalian belum ada. Keduanya ditulis di `DOKUMENTASI.md` Bagian 3.4 sebagai bahan pengembangan berikutnya.

---

### B3. J.620100.010.01 — Menerapkan Perintah Eksekusi Bahasa Pemrograman Berbasis Teks, Grafik, dan Multimedia

**Inti unit:** menjalankan program melalui perintah teks (terminal/CLI) dan menampilkan hasilnya secara grafis (GUI), termasuk unsur multimedia.

**Bukti di program:**

| Jenis | Contoh |
|---|---|
| Perintah teks (CLI) | `php artisan migrate:fresh --seed`, `php artisan serve`, `php artisan test`, `php artisan route:list`, `php artisan tinker`, `npm run build`, `composer install`, `vendor/bin/pint`, `git push` |
| Antarmuka grafis (GUI) | Halaman web dengan Blade + Tailwind CSS: dashboard, tabel, form, grafik batang stok per kategori |
| Unsur multimedia | **Unggah gambar**: foto profil, gambar barang, foto bukti barang masuk & keluar (`app/Services/GambarService.php`, komponen `<x-unggah-gambar>` dengan pratinjau); ikon SVG, font *Plus Jakarta Sans*, grafik di dashboard, fitur cetak |

**Pertanyaan yang mungkin muncul:**

**1. Bagaimana cara menjalankan aplikasi ini dari awal?**
Nyalakan MySQL di XAMPP, lalu `php artisan migrate:fresh --seed` untuk membuat tabel dan mengisi data, `npm run build` untuk membuild tampilan, dan `php artisan serve` untuk menjalankan server. Buka `http://localhost:8000`.

**2. Apa fungsi `php artisan`?**
Artisan adalah alat perintah bawaan Laravel untuk menjalankan tugas: membuat tabel (`migrate`), mengisi data (`db:seed`), menjalankan server (`serve`), menjalankan test (`test`), melihat daftar route (`route:list`), dan lain-lain.

**3. Apa beda `migrate`, `migrate:fresh`, dan `db:seed`?**
`migrate` menjalankan migration yang belum pernah dijalankan (aman, data tetap). `migrate:fresh` menghapus semua tabel lalu membuat ulang (data hilang). `db:seed` mengisi data awal dari seeder. `--seed` menggabungkan pembuatan tabel dan pengisian data.

**4. Apa fungsi `npm run build`?**
Menjalankan Vite untuk memproses Tailwind CSS dan JavaScript menjadi file yang siap dipakai di `public/build`. Hasilnya dipanggil dari tampilan dengan `@vite(...)`.

**5. Bagian mana yang termasuk grafik/multimedia?**
Fitur **unggah gambar** (foto profil, gambar barang, foto bukti transaksi) beserta pratinjaunya, grafik batang stok per kategori dan komposisi status permintaan di dashboard, ikon SVG di seluruh menu dan tombol, serta tampilan cetak (`window.print()`).

**6. Bagaimana alur unggah gambar bekerja?**
1. Form memakai `enctype="multipart/form-data"` supaya file ikut terkirim. Saat file dipilih, JavaScript menampilkan pratinjau (`URL.createObjectURL`).
2. Form Request memvalidasi file: harus gambar (`image`), format `jpg/jpeg/png/webp`, maksimal 2 MB (`GambarService::ATURAN`).
3. `GambarService::simpan()` menyimpan file dengan nama acak ke folder sesuai jenisnya (`profil`, `barang`, `barang-masuk`, `barang-keluar`), lalu mengembalikan lokasinya.
4. Lokasi file disimpan di kolom database (`foto` / `gambar`). Saat ditampilkan, model memberi alamat lengkapnya lewat `foto_url` / `gambar_url`.

**7. Kenapa gambar tidak disimpan langsung di database?**
Database hanya menyimpan lokasi file (teks pendek), sedangkan file gambarnya disimpan di folder penyimpanan. Database jadi kecil dan cepat, backup lebih ringan, dan gambar bisa dikirim langsung ke browser tanpa membebani database.

**8. Apa fungsi `php artisan storage:link`?**
Membuat jalan pintas (*symbolic link*) dari `public/storage` ke `storage/app/public`, supaya gambar yang disimpan di folder storage bisa dibuka dari browser lewat alamat `/storage/...`.

**9. Kenapa versi online memakai Supabase Storage?**
Di Vercel, folder aplikasi hanya bisa dibaca (*read-only*), dan `/tmp` dikosongkan sewaktu-waktu, sehingga file unggahan akan hilang. Supabase Storage menyimpan file secara permanen. Cukup ubah `UPLOAD_DISK` dari `public` ke `s3`, tanpa mengubah kode, karena semua penyimpanan gambar lewat `GambarService`.

**6. Kenapa ikon tidak diambil dari internet (CDN)?**
Supaya aplikasi tetap tampil sempurna tanpa koneksi internet, misalnya saat ujian. Ikon disimpan sebagai SVG di dalam kode, dan font dibundel lewat Vite.

---

### B4. J.620100.016.01 — Menulis Kode dengan Prinsip Sesuai Guidelines dan Best Practices

**Inti unit:** menulis kode yang rapi, konsisten, aman, dan mudah dirawat sesuai standar.

**Bukti di program:**

| Praktik | Penerapan |
|---|---|
| Standar penulisan PSR-12 | Diperiksa dan dirapikan otomatis dengan **Laravel Pint** (`vendor/bin/pint`) |
| Pola MVC | Model (`app/Models`), View (`resources/views`), Controller (`app/Http/Controllers`) |
| Validasi terpisah | Form Request di `app/Http/Requests/` |
| Satu tanggung jawab | Semua perubahan stok hanya lewat `app/Services/StokService.php` |
| Penamaan konsisten | Bahasa Indonesia, camelCase untuk method (`generateKode`), PascalCase untuk class, snake_case untuk kolom |
| Keamanan: password | Di-hash bcrypt (`User.php:53` cast `'hashed'`) |
| Keamanan: CSRF | `@csrf` di setiap form |
| Keamanan: XSS | Output Blade `{{ }}` otomatis di-escape; `@js()` untuk teks di JavaScript |
| Keamanan: SQL injection | Query lewat Eloquent / Query Builder (parameter binding) |
| Keamanan: hak akses | Middleware `CekRole` + pemisahan tugas Admin/Operator/Manager |
| Keamanan: unggah file | Validasi jenis isi file (`image`, `mimes`) dan ukuran (maks. 2 MB); file disimpan dengan nama acak; file lama dihapus saat diganti |
| Keamanan: database online | Row Level Security di Supabase (migration `enable_row_level_security`) |
| Konsistensi data | `DB::transaction()` + `lockForUpdate()` |
| Konfigurasi | Data rahasia di `.env`, tidak di-commit ke Git |
| Version control | Git + GitHub |

**Pertanyaan yang mungkin muncul:**

**1. Guidelines apa yang kamu ikuti?**
Standar PSR-12 untuk gaya penulisan PHP, yang dicek otomatis dengan Laravel Pint, serta konvensi Laravel (struktur folder MVC, penamaan, Form Request, Eloquent).

**2. Apa itu MVC dan bagaimana penerapannya?**
*Model* mengurus data dan aturan tabel (misalnya `Barang.php`). *View* menampilkan halaman (`resources/views/barang/index.blade.php`). *Controller* menerima permintaan, memanggil model, lalu mengirim data ke view (`BarangController.php`). Alurnya: Route → Middleware → Controller → Model → View.

**3. Bagaimana password disimpan?**
Tidak disimpan dalam teks asli, tapi di-hash dengan bcrypt melalui cast `'password' => 'hashed'` (`User.php:53`). Saat login, `Auth::attempt()` mencocokkan password dengan hash-nya. Hash tidak bisa dikembalikan ke password asli.

**4. Bagaimana mencegah SQL injection?**
Semua query memakai Eloquent / Query Builder yang otomatis memakai *parameter binding*. Input pengguna tidak pernah disambung langsung ke teks SQL.

**5. Apa itu CSRF dan bagaimana mencegahnya?**
CSRF adalah serangan yang membuat browser korban mengirim form ke aplikasi tanpa disadari. Laravel memberi token `@csrf` di setiap form, dan permintaan tanpa token yang cocok ditolak (error 419).

**6. Apa itu XSS dan bagaimana mencegahnya?**
XSS adalah penyisipan script berbahaya lewat input. Blade `{{ $data }}` otomatis meng-escape karakter HTML. Untuk teks di dalam JavaScript (pesan konfirmasi hapus) dipakai `@js()` supaya tanda kutip tidak merusak kode.

**7. Kenapa ada `StokService`, tidak langsung di controller?**
Prinsip *single responsibility* dan DRY. Stok diubah dari beberapa tempat (barang masuk, koreksi, hapus, persetujuan barang keluar). Aturan "stok tidak boleh negatif" cukup ditulis sekali di `StokService::kurangi()`, dan service ini mudah diuji tersendiri (`tests/Unit/StokServiceTest.php`).

**8. Apa itu `DB::transaction()` dan kenapa dipakai?**
Transaksi memastikan beberapa perubahan database berhasil semua atau batal semua. Contoh di `BarangKeluarController::setujui()`: pengurangan stok dan perubahan status Disetujui harus terjadi bersamaan. Kalau stok ternyata kurang dan muncul error, semuanya di-*rollback*.

**9. Apa fungsi `lockForUpdate()`?**
Mengunci baris data selama transaksi (`SELECT ... FOR UPDATE`), sehingga proses lain yang ingin mengubah baris yang sama harus menunggu. Ini mencegah stok kacau saat ada dua proses bersamaan.

**10. Kenapa data rahasia di `.env`?**
Supaya password database dan `APP_KEY` tidak tertulis di kode dan tidak ikut ter-upload ke GitHub (`.env` ada di `.gitignore`). Setiap server punya `.env`/environment variable sendiri.

---

### B5. J.620100.017.02 — Mengimplementasikan Pemrograman Terstruktur

**Inti unit:** menyusun program dengan struktur urut (*sequence*), percabangan (*selection*), perulangan (*iteration*), serta fungsi/prosedur dengan parameter dan nilai kembali.

**Bukti di program:**

| Struktur | Contoh | Lokasi |
|---|---|---|
| Urut (sequence) | Validasi → simpan → kurangi stok → redirect | `BarangKeluarController::setujui()` |
| Percabangan `if` | Stok cukup atau tidak | `StokService.php:39` |
| Percabangan bertingkat `if/elseif/else` | Salam pagi/siang/sore/malam | `DashboardController::salam()` (baris 95-106) |
| Percabangan `match` | Warna badge sesuai status | `resources/views/components/badge.blade.php` |
| Perulangan `foreach` | Mengisi data barang di seeder | `DatabaseSeeder.php` |
| Perulangan di tampilan | `@foreach` / `@forelse` daftar tabel | `resources/views/*/index.blade.php` |
| Fungsi dengan parameter & return | `cukup(Barang $barang, int $jumlah): bool` | `StokService.php:49` |
| Prosedur (tanpa nilai kembali) | `pastikanJumlahValid(int $jumlah): void` | `StokService.php:54` |
| Penanganan error | `try { ... } catch (StokTidakCukupException $e)` | `BarangKeluarController::setujui()` |

**Pertanyaan yang mungkin muncul:**

**1. Apa itu pemrograman terstruktur?**
Cara menyusun program dari tiga struktur dasar (urut, percabangan, perulangan) dan memecahnya menjadi fungsi-fungsi kecil dengan satu tugas, supaya mudah dibaca, diuji, dan diperbaiki.

**2. Tunjukkan contoh percabangan.**
`DashboardController::salam()`:
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

**3. Tunjukkan contoh perulangan.**
Di seeder, `foreach ($daftarBarang as [$kode, $idKategori, $nama, $stok, $satuan, $lokasi])` membuat 11 data barang dari satu array. Di tampilan, `@forelse ($barang as $b)` menampilkan setiap baris tabel, dan bagian `@empty` muncul kalau data kosong.

**4. Apa beda fungsi dan prosedur? Beri contoh.**
Fungsi mengembalikan nilai: `cukup()` mengembalikan `true`/`false`. Prosedur menjalankan aksi tanpa mengembalikan nilai: `pastikanJumlahValid()` bertipe `void` dan hanya melempar error kalau jumlah ≤ 0.

**5. Jelaskan alur `StokService::kurangi()` baris per baris.**
```php
public function kurangi(Barang $barang, int $jumlah): Barang
{
    $this->pastikanJumlahValid($jumlah);          // 1. jumlah harus > 0

    if (! $this->cukup($barang, $jumlah)) {       // 2. cek stok cukup
        throw new StokTidakCukupException($barang, $jumlah);   // 3. kalau tidak, hentikan dengan error
    }

    $barang->stok -= $jumlah;                     // 4. kurangi stok
    $barang->save();                              // 5. simpan ke database

    return $barang;                               // 6. kembalikan data terbaru
}
```

**6. Apa fungsi `try ... catch`?**
Menangkap error supaya program tidak berhenti dengan halaman error. Di `setujui()`, kalau stok tidak cukup, `StokTidakCukupException` ditangkap lalu pengguna melihat pesan "Stok ... tidak mencukupi".

---

### B6. J.620100.023.02 — Membuat Dokumen Kode Program

**Inti unit:** mendokumentasikan kode (komentar, penjelasan fungsi) dan membuat dokumen pendukung program.

**Bukti di program:**
- **PHPDoc** di setiap class model (`@property` untuk setiap kolom) dan method penting (`@throws`), misalnya `app/Models/Barang.php:9-19` dan `app/Services/StokService.php:32-34`.
- **Komentar penjelasan** pada logika yang tidak langsung jelas, misalnya alasan penguncian di `BarangKeluarController::kunciPermintaanPending()`.
- **Komentar Blade** `{{-- ... --}}` di view, misalnya penjelasan komponen di `components/badge.blade.php`.
- **Dokumen:** `README.md` (ringkas), `DOKUMENTASI.md`/`.docx` (spesifikasi, desain database, penjelasan kode, debugging, pengujian), `TUTORIAL_DEPLOY.md` (cara online-kan), dan dokumen ini.

**Pertanyaan yang mungkin muncul:**

**1. Bagaimana kamu mendokumentasikan kode?**
Dengan komentar PHPDoc di atas class dan method yang menjelaskan fungsinya, parameter, dan error yang mungkin dilempar. Ditambah dokumen terpisah yang menjelaskan cara menjalankan, struktur program, dan keputusan desain.

**2. Apa itu PHPDoc? Beri contoh.**
Format komentar standar PHP yang diawali `/**`. Contoh di `StokService.php`:
```php
/**
 * @throws StokTidakCukupException jika stok kurang dari jumlah yang diminta
 */
public function kurangi(Barang $barang, int $jumlah): Barang
```
Editor seperti VS Code membaca PHPDoc untuk memberi petunjuk saat menulis kode.

**3. Kapan perlu menulis komentar dan kapan tidak?**
Komentar dibutuhkan untuk menjelaskan *kenapa* sesuatu dilakukan, misalnya kenapa baris dikunci atau kenapa cache diarahkan ke `/tmp`. Kode yang sudah jelas dari namanya tidak perlu dikomentari; nama method seperti `generateKode()` dan `stokMenipis()` sudah menjelaskan dirinya sendiri.

**4. Apa isi dokumentasi program kamu?**
Deskripsi, cara menjalankan, spesifikasi dan hak akses, desain database beserta revisinya, struktur MVC, pemetaan unit kompetensi, penjelasan kode penting, catatan debugging, hasil pengujian, dan skenario demo.

---

### B7. J.620100.025.02 — Melakukan Debugging

**Inti unit:** menemukan, menganalisis, dan memperbaiki kesalahan program.

**Bukti di program:** `DOKUMENTASI.md` Bagian 9 berisi 12 bug yang ditemukan beserta perbaikannya. Beberapa yang menarik untuk diceritakan:

| Bug | Jenis kesalahan | Cara menemukan | Perbaikan |
|---|---|---|---|
| `protected $tabl` di model Kategori | Salah ketik (*typo*) | Error "table kategoris not found" | Diganti `$table = 'kategori'` |
| `jumblah` di `$fillable` BarangMasuk | Salah ketik | Kolom jumlah tidak tersimpan, query gagal | Disamakan dengan nama kolom |
| Username factory berisi titik | Data uji tidak valid (*flaky test*) | Test kadang gagal kadang lulus; dijalankan berulang | Format username factory diganti |
| Zona waktu `UTC` | Kesalahan logika/konfigurasi | Tanggal verifikasi dini hari tercatat hari kemarin | Diganti `Asia/Jakarta` |
| Status Pending dicek di luar transaksi | *Race condition* | Analisis alur saat dua orang menyetujui bersamaan | Baris dikunci dan dicek ulang di dalam transaksi |
| Mengubah enum role gagal di PostgreSQL | Perbedaan perilaku database | Migration diuji di PostgreSQL dari skema lama: `syntax error at or near "check"` | Constraint role dibuat ulang manual khusus PostgreSQL |
| Pencarian `LIKE` di PostgreSQL | Perbedaan perilaku database | Mencari "pulpen" tidak menemukan "Pulpen" | Diganti `whereLike()` |

**Pertanyaan yang mungkin muncul:**

**1. Apa saja jenis kesalahan program?**
- *Syntax error*: penulisan salah, misalnya kurung kurawal tidak pas ("Unmatched '}'" di `routes/web.php` pada versi awal).
- *Runtime error*: muncul saat program berjalan, misalnya tabel tidak ditemukan atau class tidak ada.
- *Logic error*: program berjalan tapi hasilnya salah, misalnya tanggal salah karena zona waktu atau stok berkurang dua kali.

**2. Langkah-langkah debugging yang kamu lakukan?**
1. Mereproduksi masalah (cari langkah yang membuat error muncul).
2. Membaca pesan error dan *stack trace* (file dan baris penyebab).
3. Mempersempit penyebab: cek data dengan `dd()` / `dump()`, `php artisan tinker`, atau log.
4. Memperbaiki penyebab utamanya, bukan gejalanya.
5. Menguji ulang dan menjalankan `php artisan test` supaya fitur lain tidak ikut rusak.

**3. Alat debugging apa yang kamu pakai?**
Halaman error Laravel (`APP_DEBUG=true`), file `storage/logs/laravel.log`, `dd()` / `dump()`, `php artisan route:list`, `php artisan tinker`, menu Logs di hosting, dan PHPUnit.

**4. Ceritakan satu bug yang paling sulit.**
*Race condition* pada persetujuan barang keluar. Awalnya status Pending dicek sebelum transaksi dimulai. Kalau dua Manager menekan Setujui hampir bersamaan, keduanya lolos pengecekan, dan stok berkurang dua kali. Bug seperti ini tidak terlihat saat dicoba sendiri, karena hanya muncul saat dua proses berjalan bersamaan. Perbaikannya: baris permintaan dikunci dengan `lockForUpdate()` dan statusnya dicek ulang di dalam transaksi (`kunciPermintaanPending()`). Manager kedua akan menunggu, lalu mendapati status sudah bukan Pending.

**5. Kenapa `APP_DEBUG` harus `false` di server online?**
Karena halaman error detail menampilkan isi kode, path file, dan konfigurasi yang bisa dimanfaatkan penyerang. Di server online error dicatat ke log saja.

**6. Apa yang kamu lakukan saat website online error 500?**
Membuka log di hosting untuk melihat pesan error aslinya. Pengalaman nyata saat deploy: error 500 muncul karena tabel belum dibuat di database Supabase. Setelah `php artisan migrate --seed --env=supabase` dijalankan, masalahnya selesai.

---

### B8. J.620100.033.02 — Melaksanakan Pengujian Unit Program

**Inti unit:** merancang dan menjalankan pengujian untuk memastikan setiap bagian program bekerja sesuai harapan.

**Bukti di program:** 62 test (203 assertion) dengan **PHPUnit**, semuanya lulus di SQLite (lokal) dan PostgreSQL (database online).

| File | Jenis | Jumlah | Yang diuji |
|---|---|---|---|
| `tests/Unit/StokServiceTest.php` | Unit | 7 | Tambah/kurangi stok, stok tidak cukup, jumlah nol |
| `tests/Unit/KodeBarangTest.php` | Unit | 5 | Prefix dan nomor kode barang otomatis |
| `tests/Feature/AuthTest.php` | Fitur | 6 | Login, logout, password ter-hash |
| `tests/Feature/HakAksesTest.php` | Fitur | 6 | Pembatasan role Admin/Operator/Manager |
| `tests/Feature/ProfilTest.php` | Fitur | 5 | Ubah profil, unggah/ganti/hapus foto, ganti password |
| `tests/Feature/UserTest.php` | Fitur | 7 | Kelola pengguna dan pengamanannya |
| `tests/Feature/KategoriBarangTest.php` | Fitur | 8 | CRUD kategori & barang, validasi, pencarian, gambar barang |
| `tests/Feature/TransaksiBarangTest.php` | Fitur | 18 | Barang masuk, barang keluar, verifikasi, foto bukti |

**Pertanyaan yang mungkin muncul:**

**1. Apa itu unit test?**
Pengujian bagian terkecil program (satu fungsi/method) secara terpisah untuk memastikan hasilnya sesuai harapan. Contohnya menguji `StokService::kurangi()` saja, tanpa membuka halaman web.

**2. Apa beda Unit test dan Feature test di Laravel?**
Unit test menguji satu class/method secara langsung (`tests/Unit`). Feature test menguji satu fitur utuh seperti pengguna sungguhan: mengirim request ke URL, melewati middleware, validasi, dan controller, lalu memeriksa respon dan isi database (`tests/Feature`).

**3. Bagaimana cara menjalankan test?**
`php artisan test`. Untuk satu file saja: `php artisan test --filter=StokServiceTest`.

**4. Kenapa test tidak merusak data di database asli?**
`phpunit.xml` mengatur database test ke SQLite di memori (`:memory:`), dan setiap test memakai `RefreshDatabase` supaya selalu mulai dari database kosong.

**5. Jelaskan satu test case lengkap.**
`test_admin_menyetujui_mengurangi_stok_dan_mencatat_verifikator` di `TransaksiBarangTest.php`:
- *Persiapan (Arrange):* buat barang stok 10 dan permintaan keluar 4 oleh Operator.
- *Aksi (Act):* Manager mengirim `PATCH` ke route `barang-keluar.setujui`.
- *Pemeriksaan (Assert):* status menjadi Disetujui, `id_verifikator` = Manager, tanggal verifikasi hari ini, dan stok menjadi 6.

**Bagaimana menguji unggah gambar tanpa file sungguhan?**
Dengan `Storage::fake()` (penyimpanan palsu di memori) dan `UploadedFile::fake()->image('bukti.jpg')` (gambar palsu). Test lalu memeriksa file tersimpan (`assertExists`) atau terhapus (`assertMissing`). Contoh: `test_foto_baru_dihapus_kembali_bila_koreksi_gagal` memastikan file baru ikut dihapus bila transaksi database gagal.

**6. Apa itu assertion? Sebutkan contohnya.**
Pernyataan yang harus benar agar test lulus. Contoh: `assertSame(6, $barang->fresh()->stok)`, `assertForbidden()` (respon 403), `assertSessionHasErrors('jumlah')` (ada error validasi), `assertDatabaseHas(...)`, dan `expectException(StokTidakCukupException::class)`.

**7. Skenario apa saja yang kamu uji? Apakah hanya skenario berhasil?**
Tidak hanya skenario berhasil (*positive case*), tapi juga skenario gagal (*negative case*) dan batas (*edge case*), misalnya:
- Stok tepat habis (3 dikurangi 3) diperbolehkan.
- Stok kurang (2 dikurangi 5) memunculkan exception dan stok tidak berubah.
- Jumlah 0 ditolak.
- Menolak tanpa alasan ditolak validasi.
- Permintaan yang sudah diverifikasi tidak bisa diverifikasi ulang.
- Operator membuka halaman Admin → 403; Admin menekan Setujui → 403 (hanya Manager).
- Mengajukan barang keluar tanpa foto, atau dengan file PDF / gambar 3 MB → ditolak validasi.

**8. Apa beda black box dan white box testing? Kamu memakai yang mana?**
*Black box* menguji dari luar berdasarkan input dan output tanpa melihat kode, seperti Feature test yang mengirim form lalu memeriksa hasilnya. *White box* menguji dengan mengetahui logika di dalam kode, seperti Unit test `StokService` yang sengaja menguji setiap cabang `if`. Saya memakai keduanya.

**9. Pernah ada test yang gagal? Apa yang kamu lakukan?**
Pernah. Test "ubah pengguna" kadang gagal kadang lulus. Setelah dijalankan berulang dan diperiksa, penyebabnya adalah username acak dari Faker yang kadang berisi titik, padahal validasi hanya mengizinkan huruf, angka, `-`, dan `_`. Format username di factory diperbaiki, lalu test dijalankan tiga kali berturut-turut dan selalu lulus.

---

## Bagian C — Pertanyaan Umum tentang Aplikasi & Laravel

**1. Kenapa memilih Laravel?**
Laravel sudah menyediakan fitur yang dibutuhkan aplikasi ini: routing, autentikasi, validasi, Eloquent ORM, migration, proteksi CSRF, dan PHPUnit. Strukturnya MVC sehingga kode rapi, dan dokumentasinya lengkap.

**2. Apa itu route, middleware, dan controller?**
*Route* memetakan URL ke controller (`routes/web.php`). *Middleware* adalah penyaring sebelum request sampai ke controller, misalnya `auth` (harus login) dan `role` (harus role tertentu). *Controller* berisi logika yang memproses request.

**3. Apa itu Eloquent?**
ORM (*Object Relational Mapping*) di Laravel. Tabel diwakili class model, dan baris data diwakili objek. Contohnya `Barang::where('stok', '<=', 5)->get()`, tanpa menulis SQL manual.

**4. Apa itu migration dan seeder?**
*Migration* adalah kode PHP untuk membuat dan mengubah struktur tabel (`database/migrations`), seperti version control untuk database. *Seeder* mengisi data awal (`database/seeders/DatabaseSeeder.php`), yaitu data sampel dari dokumen.

**5. Apa itu Blade?**
Template engine Laravel untuk membuat tampilan. Mendukung layout (`@extends`, `@section`), perulangan (`@foreach`), percabangan (`@if`), dan komponen (`<x-badge>`), serta otomatis meng-escape output.

**6. Bagaimana proses login bekerja?**
Form mengirim username dan password → `AuthController::login()` memvalidasi isian → `Auth::attempt()` mencari user dan mencocokkan hash password → kalau cocok, session dibuat ulang (`regenerate()`, mencegah *session fixation*) dan diarahkan ke dashboard; kalau tidak, kembali dengan pesan error.

**7. Kenapa stok barang keluar tidak langsung berkurang saat diajukan?**
Karena permintaan bisa saja ditolak Manager. Stok hanya berkurang setelah disetujui, supaya angka stok selalu mencerminkan barang yang benar-benar ada di gudang.

**8. Bagaimana kalau Operator salah input barang masuk?**
Operator hanya bisa menambah data. Koreksi dilakukan Admin lewat menu Barang Masuk (Ubah/Hapus), dan stok menyesuaikan otomatis. Kalau stok dari transaksi itu sudah terpakai, koreksi ditolak supaya stok tidak menjadi negatif.

**9. Kenapa pengajuan barang keluar tidak bisa diubah atau dihapus?**
Supaya menjadi jejak audit. Pengajuan yang keliru cukup ditolak Manager dengan alasan, sehingga riwayatnya tetap tercatat.

**10. Bagaimana mencegah sistem kehilangan semua Admin?**
Admin tidak bisa menghapus akunnya sendiri atau mengubah role-nya sendiri. Pilihan role dikunci di form, dan server tetap menolak bila data dimanipulasi (`UserController.php` method `update` dan `destroy`).

**11. Kenapa data barang yang sudah punya transaksi tidak bisa dihapus?**
Supaya riwayat transaksi tetap utuh. Di database, foreign key memakai `ON DELETE RESTRICT`, dan controller juga mengecek lalu menampilkan pesan yang jelas.

**12. Bagaimana aplikasi ini dibuat online?**
Kode disimpan di GitHub, aplikasi dijalankan di Vercel (runtime PHP), dan database memakai PostgreSQL di Supabase. Setiap `git push`, Vercel otomatis men-deploy versi terbaru. Detailnya ada di `TUTORIAL_DEPLOY.md`.

**13. Kenapa lokal pakai MySQL tapi online pakai PostgreSQL? Tidak masalah?**
Laravel mendukung keduanya; cukup mengganti pengaturan koneksi. Untuk memastikannya, semua 62 test sudah dijalankan di PostgreSQL dan lulus. Dua perbedaan yang ditemukan dan diperbaiki: `LIKE` di PostgreSQL membedakan huruf besar/kecil (diganti `whereLike()`), dan cara mengubah pilihan enum berbeda (ditangani khusus di migration).

**14. Apa yang ingin kamu kembangkan selanjutnya?**
Fitur peminjaman dan pengembalian barang, pencatatan kondisi per unit barang, laporan per periode dalam bentuk PDF/Excel, notifikasi ke Manager saat ada permintaan baru, kompresi otomatis gambar yang diunggah, dan riwayat perubahan data (*audit log*).

---

## Bagian D — Pertanyaan Jebakan / Kritis

**1. "Kalau dua Manager menyetujui permintaan yang sama secara bersamaan, apa yang terjadi?"**
Hanya satu yang berhasil. Baris permintaan dikunci dengan `lockForUpdate()` di dalam transaksi, sehingga Manager kedua menunggu sampai transaksi pertama selesai. Setelah itu statusnya sudah Disetujui, dan Manager kedua mendapat pesan "sudah diverifikasi sebelumnya". Lihat `BarangKeluarController::kunciPermintaanPending()`.

**2. "Bagaimana kalau Operator mengakali URL, misalnya langsung membuka /user?"**
Middleware `CekRole` memeriksa role di server untuk setiap request, sehingga hasilnya error 403. Menyembunyikan tombol di tampilan hanya untuk kenyamanan; pengamanan sebenarnya ada di server. Ini dibuktikan oleh `HakAksesTest`.

**3. "Bagaimana kalau seseorang mengubah nilai role lewat inspect element?"**
Server tetap memvalidasi: role harus Admin/Operator/Manager (`Rule::in`), dan Admin tidak bisa mengubah role akunnya sendiri meskipun data form diubah.

**4. "Stok bisa negatif tidak?"**
Tidak. Ada tiga lapis pengamanan: validasi saat pengajuan (jumlah tidak boleh melebihi stok), pengecekan ulang di `StokService::kurangi()` saat disetujui, dan transaksi yang di-*rollback* bila stok kurang.

**5. "Password admin bisa dilihat di database?"**
Tidak, yang tersimpan hanya hash bcrypt (contoh awalannya `$2y$12$...`). Hash tidak bisa dikembalikan ke password asli; saat login, password yang diketik di-hash lalu dibandingkan.

**6. "Kenapa tidak semua pengecekan cukup di tampilan (JavaScript) saja?"**
Tampilan bisa diakali dengan mudah. Validasi di sisi server (Form Request) adalah pengamanan utama, sedangkan atribut seperti `required` dan `min="1"` di form hanya membantu pengguna.

**7. "Apa kelemahan aplikasi kamu?"**
Jawab jujur dengan batasan sistem: kondisi barang per jenis (bukan per unit), belum ada fitur peminjaman/pengembalian, laporan baru sebatas cetak dari browser, dan hosting gratis memiliki batasan (database Supabase gratis di-*pause* bila tidak dipakai seminggu).

**8. "Bagaimana kalau ada yang mengunggah file berbahaya, misalnya script PHP yang diganti nama menjadi .jpg?"**
Ditolak. Aturan `image` dan `mimes` memeriksa **isi file** (MIME type) dengan ekstensi `fileinfo`, bukan hanya nama berkasnya. File yang lolos disimpan dengan **nama acak** dan ekstensi yang ditentukan dari jenis isinya, di folder penyimpanan, sehingga tidak dijalankan sebagai program. Ukuran juga dibatasi 2 MB. Test `test_file_bukan_gambar_atau_lebih_dari_2mb_ditolak` membuktikan PDF dan gambar 3 MB ditolak.

**9. "Kalau foto diganti, apakah file lamanya menumpuk?"**
Tidak. `GambarService::ganti()` menyimpan file baru lalu menghapus file lama. Saat data dihapus, fotonya ikut dihapus. Pada koreksi barang masuk, kalau transaksi database gagal, file yang baru diunggah dihapus kembali dan file lama tetap utuh.

---

## Bagian E — Kode yang Sebaiknya Dihafal Lokasinya

| Yang mungkin diminta | File | Baris / method |
|---|---|---|
| Login | `app/Http/Controllers/AuthController.php` | `login()` |
| Middleware role | `app/Http/Middleware/CekRole.php` | `handle()` |
| Daftar route & hak akses | `routes/web.php` | seluruh file |
| Kode barang otomatis | `app/Models/Barang.php` | `generateKode()` |
| Logika stok | `app/Services/StokService.php` | `tambah()`, `kurangi()`, `cukup()` |
| Verifikasi barang keluar | `app/Http/Controllers/BarangKeluarController.php` | `setujui()`, `tolak()`, `kunciPermintaanPending()` |
| Validasi stok saat pengajuan | `app/Http/Requests/BarangKeluarRequest.php` | `after()` |
| Struktur tabel | `database/migrations/` | `2026_09_25_000001` s.d. `000006` |
| Data sampel | `database/seeders/DatabaseSeeder.php` | `run()` |
| Dashboard (koleksi & percabangan) | `app/Http/Controllers/DashboardController.php` | `aktivitasTerbaru()`, `salam()` |
| Test stok | `tests/Unit/StokServiceTest.php` | seluruh file |
| Unggah gambar | `app/Services/GambarService.php` | `simpan()`, `ganti()`, `hapus()`, `url()` |
| Profil pengguna | `app/Http/Controllers/ProfilController.php` | `update()` |
| Komponen unggah + pratinjau | `resources/views/components/unggah-gambar.blade.php`, `resources/js/app.js` | seluruh file / bagian "Pratinjau gambar" |
| Migration role Manager & foto | `database/migrations/2026_10_08_000001_tambah_role_manager_dan_foto.php` | `up()`, `ubahPilihanRole()` |

Tips: di VS Code tekan **Ctrl+P** lalu ketik nama file untuk membukanya cepat, dan **Ctrl+G** untuk lompat ke nomor baris.

---

## Bagian F — Daftar Istilah

| Istilah | Arti singkat |
|---|---|
| Framework | Kerangka kerja berisi struktur dan fitur siap pakai untuk membangun aplikasi |
| MVC | Model–View–Controller, pola pemisahan data, tampilan, dan logika |
| ORM / Eloquent | Cara mengakses tabel database sebagai objek PHP |
| Migration | Kode untuk membuat/mengubah struktur tabel |
| Seeder | Kode untuk mengisi data awal |
| Middleware | Penyaring request sebelum masuk controller |
| Form Request | Class khusus berisi aturan validasi form |
| CRUD | Create, Read, Update, Delete |
| Hash (bcrypt) | Enkripsi satu arah untuk password |
| CSRF | Serangan pengiriman form palsu; dicegah dengan token `@csrf` |
| XSS | Penyisipan script berbahaya; dicegah dengan escaping output |
| SQL Injection | Penyisipan perintah SQL lewat input; dicegah dengan parameter binding |
| Transaksi database | Sekumpulan perubahan yang berhasil semua atau batal semua |
| Race condition | Kesalahan yang muncul saat dua proses berjalan bersamaan dan saling memengaruhi |
| Foreign key | Kolom yang merujuk primary key tabel lain (relasi) |
| Row Level Security | Fitur PostgreSQL untuk membatasi akses baris data |
| Unit test / Feature test | Pengujian satu fungsi / pengujian satu fitur utuh |
| Assertion | Pernyataan yang harus benar agar test lulus |
| Deploy | Memasang aplikasi ke server agar bisa diakses online |
| Multipart form | Format pengiriman form yang dapat membawa file (`enctype="multipart/form-data"`) |
| MIME type | Jenis isi file, misalnya `image/jpeg`; dipakai untuk memastikan file benar-benar gambar |
| Symbolic link | Jalan pintas folder; dibuat oleh `php artisan storage:link` |
| Object storage (S3) | Layanan penyimpanan file di internet, misalnya Supabase Storage |
