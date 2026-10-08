<?php

namespace App\Models;

use App\Services\GambarService;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model tabel `barang`.
 *
 * @property int $id_barang
 * @property string $kode_barang contoh: AT-001
 * @property int $id_kategori
 * @property string $nama_barang
 * @property int $stok
 * @property string $satuan
 * @property string $lokasi
 * @property string $status_barang Baik | Rusak Ringan | Rusak Berat
 * @property string|null $gambar path gambar barang
 * @property-read string|null $gambar_url
 */
class Barang extends Model
{
    /** Pilihan kondisi barang (Kamus Data field status_barang). */
    public const STATUS_BARANG = ['Baik', 'Rusak Ringan', 'Rusak Berat'];

    /** Prefix kode barang per kategori (Kamus Data field kode prefix). */
    public const PREFIX_KATEGORI = [
        'Alat Tulis' => 'AT',
        'Furnitur' => 'FR',
        'Kebersihan' => 'KN',
        'Alat Olahraga' => 'AO',
        'Elektronik' => 'EK',
    ];

    /** Batas stok yang dianggap "menipis" di dashboard. */
    public const BATAS_STOK_MENIPIS = 5;

    protected $table = 'barang';

    protected $primaryKey = 'id_barang';

    public $timestamps = false;

    protected $fillable = [
        'kode_barang',
        'id_kategori',
        'nama_barang',
        'stok',
        'satuan',
        'lokasi',
        'status_barang',
        'gambar',
    ];

    protected function casts(): array
    {
        return [
            'id_kategori' => 'integer',
            'stok' => 'integer',
        ];
    }

    /** Alamat gambar barang, dipakai sebagai $barang->gambar_url. */
    protected function gambarUrl(): Attribute
    {
        return Attribute::get(fn () => app(GambarService::class)->url($this->gambar));
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(Kategori::class, 'id_kategori', 'id_kategori');
    }

    public function barangMasuk(): HasMany
    {
        return $this->hasMany(BarangMasuk::class, 'id_barang', 'id_barang');
    }

    public function barangKeluar(): HasMany
    {
        return $this->hasMany(BarangKeluar::class, 'id_barang', 'id_barang');
    }

    /**
     * Menentukan prefix kode barang dari nama kategori.
     * Kategori yang terdaftar di kamus data memakai prefix baku,
     * kategori baru memakai huruf awal dua kata pertama
     * (atau dua huruf pertama jika hanya satu kata).
     */
    public static function prefixKategori(string $namaKategori): string
    {
        if (array_key_exists($namaKategori, self::PREFIX_KATEGORI)) {
            return self::PREFIX_KATEGORI[$namaKategori];
        }

        $kata = preg_split('/\s+/', trim($namaKategori));

        if (count($kata) >= 2) {
            $prefix = $kata[0][0].$kata[1][0];
        } else {
            $prefix = substr($kata[0], 0, 2);
        }

        return strtoupper($prefix);
    }

    /**
     * Membuat kode barang berikutnya untuk sebuah kategori.
     * Contoh: jika sudah ada AT-001 dan AT-004, hasilnya AT-005.
     */
    public static function generateKode(Kategori $kategori): string
    {
        $prefix = self::prefixKategori($kategori->nama_kategori);

        $nomorTerakhir = self::where('kode_barang', 'like', $prefix.'-%')
            ->pluck('kode_barang')
            ->map(fn (string $kode) => (int) substr($kode, strlen($prefix) + 1))
            ->max() ?? 0;

        return sprintf('%s-%03d', $prefix, $nomorTerakhir + 1);
    }

    public function stokMenipis(): bool
    {
        return $this->stok <= self::BATAS_STOK_MENIPIS;
    }
}
