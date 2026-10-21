<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model tabel `stok_barang`: jumlah sebuah barang di satu lokasi dengan satu kondisi.
 * Contoh: Kursi Siswa · Gudang 2 · Rusak Ringan · 7 buah.
 * Hanya diubah lewat App\Services\StokService.
 *
 * @property int $id_stok
 * @property int $id_barang
 * @property int $id_lokasi
 * @property string $kondisi Baik | Rusak Ringan | Rusak Berat
 * @property int $jumlah
 */
class StokBarang extends Model
{
    protected $table = 'stok_barang';

    protected $primaryKey = 'id_stok';

    public $timestamps = false;

    protected $fillable = ['id_barang', 'id_lokasi', 'kondisi', 'jumlah'];

    protected function casts(): array
    {
        return [
            'id_barang' => 'integer',
            'id_lokasi' => 'integer',
            'jumlah' => 'integer',
        ];
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi', 'id_lokasi');
    }
}
