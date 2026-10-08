<?php

namespace App\Models;

use App\Services\GambarService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model tabel `barang_keluar`.
 *
 * @property int $id_keluar
 * @property int $id_barang
 * @property int $id_user Operator yang mengajukan
 * @property Carbon $tanggal
 * @property int $jumlah
 * @property string $pemohon Nama guru / staf yang meminta barang
 * @property string $tujuan
 * @property string $status_barang
 * @property string $verifikasi Pending | Disetujui | Ditolak
 * @property int|null $id_verifikator Manager yang memverifikasi
 * @property Carbon|null $tanggal_verifikasi
 * @property string|null $catatan_verifikasi
 * @property string|null $foto path foto bukti
 * @property-read string|null $foto_url
 */
class BarangKeluar extends Model
{
    public const PENDING = 'Pending';

    public const DISETUJUI = 'Disetujui';

    public const DITOLAK = 'Ditolak';

    /** Pilihan verifikasi (Kamus Data field verifikasi). */
    public const VERIFIKASI = [self::PENDING, self::DISETUJUI, self::DITOLAK];

    protected $table = 'barang_keluar';

    protected $primaryKey = 'id_keluar';

    public $timestamps = false;

    protected $fillable = [
        'id_barang',
        'id_user',
        'tanggal',
        'jumlah',
        'pemohon',
        'tujuan',
        'status_barang',
        'foto',
        'verifikasi',
        'id_verifikator',
        'tanggal_verifikasi',
        'catatan_verifikasi',
    ];

    protected $attributes = [
        'verifikasi' => self::PENDING,
    ];

    protected function casts(): array
    {
        return [
            'id_barang' => 'integer',
            'id_user' => 'integer',
            'id_verifikator' => 'integer',
            'tanggal' => 'date',
            'tanggal_verifikasi' => 'date',
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

    /** Operator yang mengajukan (relasi "mengelola" pada ERD). */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_user', 'id_user');
    }

    /** Manager yang memverifikasi (relasi "memverifikasi" pada ERD). */
    public function verifikator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_verifikator', 'id_user');
    }

    public function isPending(): bool
    {
        return $this->verifikasi === self::PENDING;
    }
}
