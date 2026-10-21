<?php

namespace App\Models;

use App\Models\Concerns\PunyaRincianKondisi;
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
 * @property int $id_lokasi lokasi tempat barang disimpan
 * @property int $jumlah total semua kondisi
 * @property int $jumlah_baik
 * @property int $jumlah_rusak_ringan
 * @property int $jumlah_rusak_berat
 * @property string $sumber_barang
 * @property string|null $foto path foto bukti
 * @property-read string|null $foto_url
 */
class BarangMasuk extends Model
{
    use PunyaRincianKondisi;

    protected $table = 'barang_masuk';

    protected $primaryKey = 'id_masuk';

    public $timestamps = false;

    protected $fillable = [
        'id_barang',
        'id_user',
        'tanggal',
        'id_lokasi',
        'jumlah',
        'jumlah_baik',
        'jumlah_rusak_ringan',
        'jumlah_rusak_berat',
        'sumber_barang',
        'foto',
    ];

    protected function casts(): array
    {
        return [
            'id_barang' => 'integer',
            'id_user' => 'integer',
            'id_lokasi' => 'integer',
            'tanggal' => 'date',
            'jumlah' => 'integer',
            'jumlah_baik' => 'integer',
            'jumlah_rusak_ringan' => 'integer',
            'jumlah_rusak_berat' => 'integer',
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

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi', 'id_lokasi');
    }
}
