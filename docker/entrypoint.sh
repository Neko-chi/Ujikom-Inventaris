#!/bin/sh
# Dijalankan setiap kali container dinyalakan di server hosting.
set -e

# Render menentukan port lewat variabel PORT (default 10000).
PORT="${PORT:-10000}"
sed -i "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/<VirtualHost \*:[0-9]*>/<VirtualHost *:${PORT}>/" /etc/apache2/sites-available/000-default.conf

# Simpan konfigurasi, route, dan view dalam cache agar aplikasi lebih cepat.
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Buat / perbarui tabel database bila RUN_MIGRATIONS=true (data tidak dihapus).
if [ "${RUN_MIGRATIONS}" = "true" ]; then
    php artisan migrate --force
fi

exec apache2-foreground
