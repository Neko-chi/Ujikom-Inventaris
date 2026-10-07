# Inventaris Gudang Sekolah

Aplikasi web inventaris gudang sekolah (Laravel 12) untuk Uji Kompetensi skema Pemrogram Junior.

Penjelasan lengkap untuk asesor: [DOKUMENTASI.md](DOKUMENTASI.md) (versi Word: `DOKUMENTASI.docx`).

Cara mengunggah ke GitHub dan meng-online-kan (Render + Supabase): [TUTORIAL_DEPLOY.md](TUTORIAL_DEPLOY.md).

## Menjalankan

```bash
composer install
npm install && npm run build
php artisan migrate:fresh --seed
php artisan serve
```

Login: `admin_marco` / `@admin123` (Admin), `operator_ohim` / `operator123` (Operator).

## Pengujian

```bash
php artisan test
```
