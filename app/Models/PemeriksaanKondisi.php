<?php

namespace App\Models;

use App\Services\GambarService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model tabel `pemeriksaan_kondisi`: penyesuaian kondisi barang di data dengan kondisi fisiknya.
 * Contoh: 10 Meja Guru di Gudang Utama tercatat Baik, ternyata Rusak Ringan.
 * Total stok tidak berubah; jumlah dipindah dari kondisi_asal ke kondisi_baru di lokasi yang sama.
 *
 * @property int $id_pemeriksaan
 * @property int $id_barang
 * @property int $id_lokasi lokasi tempat barang diperiksa
 * @property int $id_user Operator yang mengajukan
 * @property Carbon $tanggal
 * @property string $kondisi_asal kondisi yang tercatat di data
 * @property string $kondisi_baru kondisi fisik sebenarnya
 * @property int $jumlah
 * @property string $alasan
 * @property string|null $foto path foto bukti
 * @property string $verifikasi Pending | Disetujui | Ditolak
 * @property int|null $id_verifikator Manager yang memverifikasi
 * @property Carbon|null $tanggal_verifikasi
 * @property string|null $catatan_verifikasi
 * @property-read string|null $foto_url
 */
class PemeriksaanKondisi extends Model
{
    protected $table = 'pemeriksaan_kondisi';

    protected $primaryKey = 'id_pemeriksaan';

    public $timestamps = false;

    protected $fillable = [
        'id_barang',
        'id_lokasi',
        'id_user',
        'tanggal',
        'kondisi_asal',
        'kondisi_baru',
        'jumlah',
        'alasan',
        'foto',
        'verifikasi',
        'id_verifikator',
        'tanggal_verifikasi',
        'catatan_verifikasi',
    ];

    protected $attributes = [
        'verifikasi' => BarangKeluar::PENDING,
    ];

    protected function casts(): array
    {
        return [
            'id_barang' => 'integer',
            'id_lokasi' => 'integer',
            'id_user' => 'integer',
            'id_verifikator' => 'integer',
            'tanggal' => 'date',
            'tanggal_verifikasi' => 'date',
            'jumlah' => 'integer',
        ];
    }

    /** Alamat foto bukti, dipakai sebagai $pemeriksaan->foto_url. */
    protected function fotoUrl(): Attribute
    {
        return Attribute::get(fn () => app(GambarService::class)->url($this->foto));
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(Barang::class, 'id_barang', 'id_barang');
    }

    public function lokasi(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi', 'id_lokasi');
    }

    /** Operator yang mengajukan. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /** Manager yang memverifikasi. */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_verifikator', 'id_user');
    }

    public function isPending(): bool
    {
        return $this->verifikasi === BarangKeluar::PENDING;
    }
}
