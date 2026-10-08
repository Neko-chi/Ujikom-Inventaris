<?php

/**
 * Pintu masuk aplikasi saat di-hosting di Vercel (runtime vercel-php).
 *
 * Penyimpanan file di Vercel bersifat read-only kecuali folder /tmp, sehingga
 * file cache Laravel (konfigurasi, route, view hasil kompilasi) diarahkan ke /tmp.
 * Sesi disimpan di cookie terenkripsi, log dikirim ke stderr (tampil di menu Logs Vercel),
 * dan gambar unggahan disimpan di Supabase Storage (UPLOAD_DISK=s3).
 */
$pengaturanVercel = [
    'APP_CONFIG_CACHE' => '/tmp/config.php',
    'APP_EVENTS_CACHE' => '/tmp/events.php',
    'APP_PACKAGES_CACHE' => '/tmp/packages.php',
    'APP_ROUTES_CACHE' => '/tmp/routes.php',
    'APP_SERVICES_CACHE' => '/tmp/services.php',
    'VIEW_COMPILED_PATH' => '/tmp/views',
];

// Nilai bawaan yang tetap bisa ditimpa lewat Environment Variables di dashboard Vercel.
$bawaan = [
    'SESSION_DRIVER' => 'cookie',
    'CACHE_STORE' => 'array',
    'LOG_CHANNEL' => 'stderr',
    // Gambar unggahan disimpan di Supabase Storage (S3), karena folder di Vercel tidak permanen.
    'UPLOAD_DISK' => 's3',
];

foreach ($pengaturanVercel + $bawaan as $nama => $nilai) {
    if (isset($bawaan[$nama]) && getenv($nama) !== false) {
        continue;
    }

    putenv("{$nama}={$nilai}");
    $_ENV[$nama] = $nilai;
    $_SERVER[$nama] = $nilai;
}

if (! is_dir('/tmp/views')) {
    mkdir('/tmp/views', 0755, true);
}

require __DIR__.'/../public/index.php';
