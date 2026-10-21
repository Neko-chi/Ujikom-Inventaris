<?php

namespace App\Models;

use App\Models\Concerns\PunyaRincianKondisi;
use App\Services\GambarService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model tabel `pengembalian`: barang pinjaman yang dikembalikan (boleh sebagian),
 * beserta kondisinya saat kembali (bisa berbeda dari saat dipinjam).
 *
 * @property int $id_kembali
 * @property int $id_keluar peminjaman yang dikembalikan
 * @property int $id_user Operator yang mencatat
 * @property int $id_lokasi lokasi tempat barang disimpan kembali
 * @property Carbon $tanggal
 * @property int $jumlah
 * @property int $jumlah_baik
 * @property int $jumlah_rusak_ringan
 * @property int $jumlah_rusak_berat
 * @property string|null $catatan
 * @property string|null $foto
 * @property-read string|null $foto_url
 */
class Pengembalian extends Model
{
    use PunyaRincianKondisi;

    protected $table = 'pengembalian';

    protected $primaryKey = 'id_kembali';

    public $timestamps = false;

    protected $fillable = [
        'id_keluar', 'id_user', 'id_lokasi', 'tanggal', 'jumlah',
        'jumlah_baik', 'jumlah_rusak_ringan', 'jumlah_rusak_berat', 'catatan', 'foto',
    ];

    protected function casts(): array
    {
        return [
            'id_keluar' => 'integer',
            'id_user' => 'integer',
            'id_lokasi' => 'integer',
            'tanggal' => 'date',
            'jumlah' => 'integer',
            'jumlah_baik' => 'integer',
            'jumlah_rusak_ringan' => 'integer',
            'jumlah_rusak_berat' => 'integer',
        ];
    }

    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn () => app(GambarService::class)->url($this->foto));
    }

    public function barangKeluar(): BelongsTo
    {
        return $this->belongsTo(BarangKeluar::class, 'id_keluar', 'id_keluar');
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
