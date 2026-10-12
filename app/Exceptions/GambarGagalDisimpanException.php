<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Dilempar ketika file gambar gagal disimpan ke penyimpanan (lokal atau Supabase Storage),
 * misalnya karena kunci akses salah, bucket tidak ada, atau koneksi terputus.
 * Ditangani di bootstrap/app.php: pengguna dikembalikan ke form dengan pesan yang jelas.
 * $penyebab berisi ringkasan teknis satu baris (ditampilkan hanya untuk Admin).
 */
class GambarGagalDisimpanException extends RuntimeException
{
    public function __construct(?Throwable $sebelumnya = null, public readonly ?string $penyebab = null)
    {
        parent::__construct(
            'Gambar gagal disimpan ke penyimpanan. Data belum tersimpan, silakan coba lagi. '
            .'Bila tetap gagal, periksa pengaturan penyimpanan gambar (Supabase Storage).',
            0,
            $sebelumnya
        );
    }
}
