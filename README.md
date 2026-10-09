# Inventaris Gudang Sekolah

Aplikasi web inventaris gudang sekolah (Laravel 12) untuk Uji Kompetensi skema Pemrogram Junior (Junior Coder).

Tiga role: **Admin** (pengelola sistem), **Operator** (petugas gudang), **Manager** (pemberi persetujuan barang keluar). Mendukung unggah gambar: foto profil, gambar barang, dan foto bukti transaksi.

## Dokumen

Semua dokumen ada di folder [`dokumen/`](dokumen) dalam format Markdown (`.md`) dan Word (`.docx`):

| No | Dokumen | Isi |
|---|---|---|
| 01 | [Dokumen Rancangan](dokumen/01_DOKUMEN_RANCANGAN.md) | ERD, Desain Database, Database Relation, Kamus Data, Data Sampel |
| 02 | [Dokumentasi Program](dokumen/02_DOKUMENTASI_PROGRAM.md) | Fitur, hak akses, struktur program, penjelasan kode, keamanan, debugging, pengujian |
| 03 | [Persiapan Asesor](dokumen/03_PERSIAPAN_ASESOR.md) | Naskah presentasi, alur demo, tanya jawab per unit kompetensi |
| 04 | [Daftar Akun](dokumen/04_DAFTAR_AKUN.md) | Akun login, password, dan hak akses |
| 05 | [Tutorial Deploy](dokumen/05_TUTORIAL_DEPLOY.md) | GitHub + Vercel + Supabase |

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

Hasil: 66 test lulus (221 assertion).
