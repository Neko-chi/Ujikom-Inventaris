<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

/**
 * Dilempar ketika file gambar gagal disimpan ke penyimpanan (lokal atau Supabase Storage),
 * misalnya karena kunci akses salah, bucket tidak ada, atau koneksi terputus.
 * Ditangani di bootstrap/app.php: pengguna dikembalikan ke form dengan pesan yang jelas.
 * Penyebab teknisnya dicatat ke log oleh App\Services\GambarService.
 */
class GambarGagalDisimpanException extends RuntimeException
{
    public function __construct(?Throwable $sebelumnya = null)
    {
        parent::__construct(
            'Gambar gagal disimpan ke penyimpanan. Data belum tersimpan, silakan coba lagi. '
            .'Bila tetap gagal, periksa pengaturan penyimpanan gambar (Supabase Storage).',
            0,
            $sebelumnya
        );
    }
}
