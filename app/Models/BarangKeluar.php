<?php

namespace App\Models;

use App\Models\Concerns\PunyaRincianKondisi;
use App\Services\GambarService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model tabel `barang_keluar`.
 *
 * @property int $id_keluar
 * @property int $id_barang
 * @property int $id_user Operator yang mengajukan
 * @property Carbon $tanggal
 * @property string $jenis_keluar Peminjaman | Pemindahan | Pemusnahan
 * @property int $id_lokasi_asal lokasi barang diambil
 * @property int|null $id_lokasi_tujuan ruangan tujuan (khusus Pemindahan)
 * @property int $jumlah total semua kondisi
 * @property int $jumlah_baik
 * @property int $jumlah_rusak_ringan
 * @property int $jumlah_rusak_berat
 * @property string $pemohon Nama guru / staf peminjam atau penanggung jawab
 * @property string $tujuan keperluan / keterangan
 * @property Carbon|null $tanggal_kembali rencana tanggal kembali (khusus Peminjaman)
 * @property string $verifikasi Pending | Disetujui | Ditolak
 * @property int|null $id_verifikator Manager yang memverifikasi
 * @property Carbon|null $tanggal_verifikasi
 * @property string|null $catatan_verifikasi
 * @property string|null $foto path foto bukti
 * @property-read string|null $foto_url
 */
class BarangKeluar extends Model
{
    use PunyaRincianKondisi;

    public const PEMINJAMAN = 'Peminjaman';

    public const PEMINDAHAN = 'Pemindahan';

    public const PEMUSNAHAN = 'Pemusnahan';

    /** Jenis barang keluar (Kamus Data field jenis_keluar). */
    public const JENIS = [self::PEMINJAMAN, self::PEMINDAHAN, self::PEMUSNAHAN];

    /** Keterangan tiap jenis untuk form dan halaman detail. */
    public const INFO_JENIS = [
        self::PEMINJAMAN => 'Dipinjam lalu dikembalikan. Stok berkurang selama dipinjam, data tidak dihapus.',
        self::PEMINDAHAN => 'Dipindah ke ruangan lain (mis. lab). Tetap tercatat sebagai stok di lokasi tujuan.',
        self::PEMUSNAHAN => 'Dimusnahkan karena rusak berat. Stok berkurang permanen.',
    ];

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
        'jenis_keluar',
        'id_lokasi_asal',
        'id_lokasi_tujuan',
        'jumlah',
        'jumlah_baik',
        'jumlah_rusak_ringan',
        'jumlah_rusak_berat',
        'pemohon',
        'tujuan',
        'tanggal_kembali',
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
            'id_lokasi_asal' => 'integer',
            'id_lokasi_tujuan' => 'integer',
            'tanggal' => 'date',
            'tanggal_kembali' => 'date',
            'tanggal_verifikasi' => 'date',
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

    public function lokasiAsal(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi_asal', 'id_lokasi');
    }

    public function lokasiTujuan(): BelongsTo
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi_tujuan', 'id_lokasi');
    }

    /** Relasi 1:N pengembalian (khusus Peminjaman). */
    public function pengembalian(): HasMany
    {
        return $this->hasMany(Pengembalian::class, 'id_keluar', 'id_keluar');
    }

    public function isPending(): bool
    {
        return $this->verifikasi === self::PENDING;
    }

    public function isPeminjaman(): bool
    {
        return $this->jenis_keluar === self::PEMINJAMAN;
    }

    /** Jumlah yang sudah dikembalikan (pakai withSum bila tersedia agar hemat query). */
    public function jumlahKembali(): int
    {
        return (int) ($this->jumlah_kembali ?? $this->pengembalian()->sum('jumlah'));
    }

    /** Sisa barang pinjaman yang belum dikembalikan. */
    public function sisaPinjaman(): int
    {
        return $this->isPeminjaman() && $this->verifikasi === self::DISETUJUI
            ? max(0, $this->jumlah - $this->jumlahKembali())
            : 0;
    }

    /**
     * Status peminjaman untuk ditampilkan: Dipinjam / Dikembalikan Sebagian / Dikembalikan,
     * atau null bila bukan peminjaman yang disetujui.
     */
    public function statusPinjam(): ?string
    {
        if (! $this->isPeminjaman() || $this->verifikasi !== self::DISETUJUI) {
            return null;
        }
        $kembali = $this->jumlahKembali();

        return match (true) {
            $kembali >= $this->jumlah => 'Dikembalikan',
            $kembali > 0 => 'Dikembalikan Sebagian',
            default => 'Dipinjam',
        };
    }

    /** Peminjaman yang sudah lewat tanggal rencana kembali dan belum selesai. */
    public function terlambat(): bool
    {
        return $this->sisaPinjaman() > 0 && $this->tanggal_kembali && $this->tanggal_kembali->isPast() && ! $this->tanggal_kembali->isToday();
    }
}
