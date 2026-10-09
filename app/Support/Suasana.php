<?php

namespace App\Support;

/**
 * Menentukan suasana waktu (pagi, siang, sore, malam) dari jam.
 * Dipakai untuk salam di dashboard serta animasi dan suara di halaman login.
 */
class Suasana
{
    /**
     * Daftar suasana. Kunci "mulai" adalah jam mulai (0-23) dan dicek berurutan;
     * jam sebelum 04.00 termasuk malam. Array ini juga dikirim ke JavaScript
     * halaman login (resources/js/suasana.js) dalam bentuk JSON.
     */
    public const DAFTAR = [
        'pagi' => [
            'mulai' => 4,
            'salam' => 'Selamat pagi',
            'kalimat' => 'Awali hari dengan mencatat barang yang datang.',
            'audio' => 'audio/pagi.wav',
        ],
        'siang' => [
            'mulai' => 11,
            'salam' => 'Selamat siang',
            'kalimat' => 'Gudang sibuk? Semua keluar-masuk tetap tercatat.',
            'audio' => 'audio/siang.wav',
        ],
        'sore' => [
            'mulai' => 15,
            'salam' => 'Selamat sore',
            'kalimat' => 'Cek stok sebentar sebelum gudang dikunci.',
            'audio' => 'audio/sore.wav',
        ],
        'malam' => [
            'mulai' => 18,
            'salam' => 'Selamat malam',
            'kalimat' => 'Gudang sudah tutup, datanya tetap aman.',
            'audio' => 'audio/malam.wav',
        ],
    ];

    /**
     * Kunci suasana untuk jam 0-23, misalnya 7 => 'pagi', 21 => 'malam'.
     */
    public static function dariJam(int $jam): string
    {
        $hasil = 'malam';
        foreach (self::DAFTAR as $kunci => $data) {
            if ($jam >= $data['mulai']) {
                $hasil = $kunci;
            }
        }

        return $hasil;
    }

    /**
     * Salam untuk jam tertentu, misalnya 'Selamat pagi'.
     */
    public static function salam(int $jam): string
    {
        return self::DAFTAR[self::dariJam($jam)]['salam'];
    }

    /**
     * Data semua suasana untuk JavaScript, lengkap dengan alamat file audio.
     */
    public static function untukBrowser(): array
    {
        return array_map(fn (array $data) => [...$data, 'audio' => asset($data['audio'])], self::DAFTAR);
    }
}
