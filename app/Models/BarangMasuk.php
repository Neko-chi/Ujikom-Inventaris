<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model tabel `barang_masuk`.
 *
 * @property int $id_masuk
 * @property int $id_barang
 * @property int $id_user
 * @property Carbon $tanggal
 * @property int $jumlah
 * @property string $sumber_barang
 * @property string $status_barang
 */
class BarangMasuk extends Model
{
    protected $table = 'barang_masuk';

    protected $primaryKey = 'id_masuk';

    public $timestamps = false;

    protected $fillable = [
        'id_barang',
        'id_user',
        'tanggal',
        'jumlah',
        'sumber_barang',
        'status_barang',
    ];

    protected function casts(): array
    {
        return [
            'id_barang' => 'integer',
            'id_user' => 'integer',
            'tanggal' => 'date',
            'jumlah' => 'integer',
        ];
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }
}
