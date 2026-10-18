# Inventaris Gudang Sekolah

Aplikasi web inventaris gudang sekolah (Laravel 12) untuk Uji Kompetensi skema Pemrogram Junior (Junior Coder).

Tiga role: **Admin** (pengelola sistem), **Operator** (petugas gudang), **Manager** (pemberi persetujuan barang keluar). Multimedia: unggah gambar (foto profil, gambar barang, foto bukti transaksi), halaman login bergaya siluet dengan animasi dan suara suasana sesuai waktu (pagi, siang, sore, malam), musik Bad Apple!! (MP3), serta tema monokrom dengan mode terang / gelap. Menu dibuka tanpa memuat ulang halaman (Turbo), form tambah / ubah / detail tampil sebagai popup.

## Dokumen

Semua dokumen ada di folder [`dokumen/`](dokumen) dalam format Markdown (`.md`) dan Word (`.docx`):

| No | Dokumen | Isi |
|---|---|---|
| 01 | [Dokumen Rancangan](dokumen/01_DOKUMEN_RANCANGAN.md) | ERD, Desain Database, Database Relation, Kamus Data, Data Sampel |
| 02 | [Dokumentasi Program](dokumen/02_DOKUMENTASI_PROGRAM.md) | Fitur, hak akses, struktur program, penjelasan kode, keamanan, debugging, pengujian |
| 03 | [Persiapan Asesor](dokumen/03_PERSIAPAN_ASESOR.md) | Naskah presentasi, alur demo, tanya jawab per unit kompetensi |
| 04 | [Daftar Akun](dokumen/04_DAFTAR_AKUN.md) | Akun login, password, dan hak akses |
| 05 | [Tutorial Deploy](dokumen/05_TUTORIAL_DEPLOY.md) | GitHub + Vercel + Supabase |
| 06 | [Peta Kode](dokumen/06_PETA_KODE.md) | Lokasi setiap fungsi dan array beserta kegunaannya |

## Versi Utama

| Alat | Versi |
|---|---|
| PHP | 8.2.12 (XAMPP 8.2.12) |
| Laravel | 12.69.2 |
| Database | MariaDB 10.4.32 (lokal), PostgreSQL 17.11 (Supabase) |
| Composer / Node.js / npm | 2.10.3 / 26.7.0 / 11.19.0 |
| Vite / Tailwind CSS / Turbo | 7.3.6 / 4.3.3 / 8.0.23 |
| PHPUnit | 11.5.56 |
| Editor | Antigravity IDE 2.5.5 |

Daftar lengkap ada di dokumen 02 Bagian 2.1.

## Menjalankan di Laptop

```bash
composer install
npm install && npm run build
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Buka `http://localhost:8000`. Akun login:

| Role | Username | Password |
|---|---|---|
| Admin | `admin_ohim` | `admin123` |
| Manager | `manager_marco` | `@admin123` |
| Operator | `operator_siti` | `operator123` |

## Pengujian

```bash
php artisan test
```

Hasil: 80 test lulus (275 assertion).

## Lisensi

Kode program ini dirilis dengan lisensi **[MIT](LICENSE)** © 2026 Renaissance Ricarda Sugiarto Putra. Kode boleh dipakai, diubah, dan dibagikan, asal mencantumkan nama pembuat dan teks lisensinya.

### Aset Pihak Ketiga

Aset berikut **bukan** milik pembuat dan **tidak** termasuk lisensi MIT. Aset ini disertakan hanya untuk keperluan pendidikan (uji kompetensi), bukan komersial:

| Aset | Pemilik / lisensi |
|---|---|
| `public/audio/bad-apple.mp3` | Lagu *Bad Apple!!* — Alstroemeria Records, komposisi asli ZUN (Team Shanghai Alice). Hak cipta pada pemiliknya |
| Siluet di `public/images/` (`siluet.png`, `latar-karakter.png`, `penyihir.png`, `karakter-sidebar.png`) | Diambil dari video bayangan *Bad Apple!!* ([youtu.be/FtutLA63Cp8](https://youtu.be/FtutLA63Cp8)), karakter Touhou Project (ZUN). Karya penggemar; hak cipta pada pemiliknya |
| Font Plus Jakarta Sans | SIL Open Font License 1.1 |
| Ikon di `resources/views/components/icon.blade.php` | Diadaptasi dari [Heroicons](https://heroicons.com) — MIT, Tailwind Labs |
| Laravel dan pustaka Composer / npm lainnya | Lisensi masing-masing pustaka |

Suara suasana `public/audio/pagi.wav`, `siang.wav`, `sore.wav`, dan `malam.wav` dibuat sendiri secara sintetis, sehingga termasuk lisensi MIT.

Bila Anda pemilik hak cipta salah satu aset di atas dan ingin aset tersebut dihapus, silakan buka *issue* di repository ini.
