<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Model tabel `lokasi`: tempat penyimpanan barang (gudang) atau ruangan pemakaian (lab, kelas).
 *
 * @property int $id_lokasi
 * @property string $nama_lokasi
 * @property string $jenis Gudang | Ruangan
 * @property string|null $keterangan
 */
class Lokasi extends Model
{
    public const GUDANG = 'Gudang';

    public const RUANGAN = 'Ruangan';

    /** Pilihan jenis lokasi (Kamus Data field jenis). */
    public const JENIS = [self::GUDANG, self::RUANGAN];

    protected $table = 'lokasi';

    protected $primaryKey = 'id_lokasi';

    public $timestamps = false;

    protected $fillable = ['nama_lokasi', 'jenis', 'keterangan'];

    /** Relasi 1:N "menyimpan" stok barang. */
    public function stokBarang(): HasMany
    {
        return $this->hasMany(StokBarang::class, 'id_lokasi', 'id_lokasi');
    }

    /** Lokasi yang sudah dipakai stok atau transaksi tidak boleh dihapus. */
    public function sudahDipakai(): bool
    {
        return $this->stokBarang()->where('jumlah', '>', 0)->exists()
            || BarangMasuk::where('id_lokasi', $this->id_lokasi)->exists()
            || BarangKeluar::where('id_lokasi_asal', $this->id_lokasi)->orWhere('id_lokasi_tujuan', $this->id_lokasi)->exists()
            || Pengembalian::where('id_lokasi', $this->id_lokasi)->exists();
    }
}
