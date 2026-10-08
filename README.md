# Inventaris Gudang Sekolah

Aplikasi web inventaris gudang sekolah (Laravel 12) untuk Uji Kompetensi skema Pemrogram Junior.

Penjelasan lengkap untuk asesor: [DOKUMENTASI.md](DOKUMENTASI.md) (versi Word: `DOKUMENTASI.docx`).

Cara mengunggah ke GitHub dan meng-online-kan (Vercel + Supabase, tanpa kartu kredit): [TUTORIAL_DEPLOY.md](TUTORIAL_DEPLOY.md).

## Menjalankan

```bash
composer install
npm install && npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Login: `admin_ohim` / `admin123` (Admin), `manager_marco` / `@admin123` (Manager), `operator_siti` / `operator123` (Operator).

Sebelum menjalankan pertama kali, hubungkan folder gambar: `php artisan storage:link`.

## Pengujian

```bash
php artisan test
```
