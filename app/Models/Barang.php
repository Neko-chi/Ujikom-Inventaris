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
 * @property int $stok total unit milik sekolah di semua lokasi (tidak termasuk yang sedang dipinjam)
 * @property string $satuan
 * @property string|null $gambar path gambar barang
 * @property-read string|null $gambar_url
 */
class Barang extends Model
{
    public const BAIK = 'Baik';

    public const RUSAK_RINGAN = 'Rusak Ringan';

    public const RUSAK_BERAT = 'Rusak Berat';

    /** Pilihan kondisi barang (Kamus Data field kondisi). */
    public const KONDISI = [self::BAIK, self::RUSAK_RINGAN, self::RUSAK_BERAT];

    /** Kolom jumlah per kondisi pada tabel transaksi (barang_masuk, barang_keluar, pengembalian). */
    public const KOLOM_KONDISI = [
        self::BAIK => 'jumlah_baik',
        self::RUSAK_RINGAN => 'jumlah_rusak_ringan',
        self::RUSAK_BERAT => 'jumlah_rusak_berat',
    ];

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

    /** Relasi 1:N stok per lokasi dan kondisi. */
    public function stokBarang(): HasMany
    {
        return $this->hasMany(StokBarang::class, 'id_barang', 'id_barang');
    }

    /**
     * Jumlah per kondisi di semua lokasi, contoh ['Baik' => 8, 'Rusak Ringan' => 2, 'Rusak Berat' => 0].
     *
     * @return array<string, int>
     */
    public function stokPerKondisi(): array
    {
        $hasil = array_fill_keys(self::KONDISI, 0);
        foreach ($this->stokBarang as $s) {
            $hasil[$s->kondisi] = ($hasil[$s->kondisi] ?? 0) + $s->jumlah;
        }

        return $hasil;
    }

    /**
     * Stok per lokasi lalu per kondisi, hanya yang jumlahnya lebih dari 0.
     * Contoh: ['Gudang Utama' => ['Baik' => 5, ...], 'Lab TKJ 3' => [...]].
     *
     * @return array<string, array<string, int>>
     */
    public function stokPerLokasi(): array
    {
        $hasil = [];
        foreach ($this->stokBarang->load('lokasi')->where('jumlah', '>', 0)->sortBy('lokasi.nama_lokasi') as $s) {
            $hasil[$s->lokasi->nama_lokasi] ??= array_fill_keys(self::KONDISI, 0);
            $hasil[$s->lokasi->nama_lokasi][$s->kondisi] = $s->jumlah;
        }

        return $hasil;
    }

    /** Jumlah unit yang sedang dipinjam (peminjaman disetujui yang belum dikembalikan seluruhnya). */
    public function jumlahDipinjam(): int
    {
        return $this->barangKeluar()
            ->where('jenis_keluar', BarangKeluar::PEMINJAMAN)
            ->where('verifikasi', BarangKeluar::DISETUJUI)
            ->withSum('pengembalian as jumlah_kembali', 'jumlah')
            ->get()
            ->sum(fn (BarangKeluar $k) => $k->jumlah - (int) $k->jumlah_kembali);
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
