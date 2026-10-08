<?php

namespace App\Models;

use App\Services\GambarService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
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
 * @property string|null $foto path foto bukti
 * @property-read string|null $foto_url
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
        'foto',
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

    /** Alamat foto bukti, dipakai sebagai $transaksi->foto_url. */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn () => app(GambarService::class)->url($this->foto));
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
