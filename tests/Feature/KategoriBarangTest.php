<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian CRUD kategori dan barang oleh Admin.
 */
class KategoriBarangTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    public function test_admin_dapat_menambah_kategori(): void
    {
        $this->post('/kategori', ['nama_kategori' => 'Alat Tulis'])
            ->assertRedirect('/kategori');

        $this->assertDatabaseHas('kategori', ['nama_kategori' => 'Alat Tulis']);
    }

    public function test_nama_kategori_wajib_unik_dan_maksimal_20_karakter(): void
    {
        Kategori::create(['nama_kategori' => 'Furnitur']);

        $this->post('/kategori', ['nama_kategori' => 'Furnitur'])->assertSessionHasErrors('nama_kategori');
        $this->post('/kategori', ['nama_kategori' => str_repeat('a', 21)])->assertSessionHasErrors('nama_kategori');
    }

    public function test_kategori_yang_dipakai_barang_tidak_dapat_dihapus(): void
    {
        $barang = $this->buatBarang();

        $this->delete(route('kategori.destroy', $barang->id_kategori))
            ->assertSessionHas('gagal');

        $this->assertDatabaseHas('kategori', ['id_kategori' => $barang->id_kategori]);
    }

    public function test_tambah_barang_membuat_kode_otomatis_dan_stok_awal_per_lokasi(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);
        $gudang = $this->lokasi();

        // Stok awal 6: 5 baik, 1 rusak ringan, disimpan di Gudang Utama
        $this->post('/barang', [
            'id_kategori' => $kategori->id_kategori,
            'nama_barang' => 'Proyektor Epson',
            'satuan' => 'unit',
            'id_lokasi' => $gudang->id_lokasi,
        ] + $this->kondisi(5, 1))->assertRedirect('/barang');

        $barang = Barang::where('kode_barang', 'EK-001')->firstOrFail();
        $this->assertSame(6, $barang->stok);
        $this->assertSame(5, $this->stokDi($barang, $gudang, Barang::BAIK));
        $this->assertSame(1, $this->stokDi($barang, $gudang, Barang::RUSAK_RINGAN));
    }

    public function test_tambah_barang_tanpa_stok_awal_diperbolehkan(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);

        $this->post('/barang', [
            'id_kategori' => $kategori->id_kategori, 'nama_barang' => 'Printer', 'satuan' => 'unit',
        ])->assertRedirect('/barang');

        $this->assertDatabaseHas('barang', ['nama_barang' => 'Printer', 'stok' => 0]);
    }

    public function test_ubah_barang_tidak_mengubah_stok(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->put(route('barang.update', $barang), [
            'id_kategori' => $barang->id_kategori,
            'nama_barang' => 'Pulpen Hitam',
            'stok' => 999,
            'satuan' => 'pack',
        ] + $this->kondisi(500))->assertRedirect('/barang');

        $barang->refresh();
        $this->assertSame('Pulpen Hitam', $barang->nama_barang);
        $this->assertSame(10, $barang->stok);
    }

    public function test_stok_awal_wajib_memilih_lokasi(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Furnitur']);

        $this->post('/barang', [
            'id_kategori' => $kategori->id_kategori,
            'nama_barang' => 'Meja',
            'satuan' => 'buah',
        ] + $this->kondisi(3))->assertSessionHasErrors('id_lokasi');

        $this->assertSame(0, Barang::count());
    }

    public function test_pencarian_barang(): void
    {
        $this->buatBarang(['nama_barang' => 'Pulpen']);
        $this->buatBarang(['nama_barang' => 'Penghapus']);

        $this->get('/barang?cari=pulp')
            ->assertSee('Pulpen')
            ->assertDontSee('Penghapus');
    }

    public function test_gambar_barang_dapat_diunggah_diganti_dan_dihapus(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);
        $data = [
            'id_kategori' => $kategori->id_kategori, 'nama_barang' => 'Proyektor Epson', 'satuan' => 'unit',
        ];

        // Unggah saat tambah barang
        $this->post('/barang', $data + ['gambar' => $this->gambarPalsu('proyektor.jpg')]);
        $barang = Barang::firstOrFail();
        $gambarPertama = $barang->gambar;
        $this->disk()->assertExists($gambarPertama);
        $this->get(route('barang.show', $barang))->assertSee($barang->gambar_url);

        // Ganti gambar: file lama dihapus
        $this->put(route('barang.update', $barang), $data + ['gambar' => $this->gambarPalsu('baru.jpg')]);
        $this->disk()->assertMissing($gambarPertama);
        $this->disk()->assertExists($barang->fresh()->gambar);

        // Hapus gambar lewat centang "hapus gambar"
        $this->put(route('barang.update', $barang), $data + ['hapus_gambar' => '1']);
        $this->assertNull($barang->fresh()->gambar);
        $this->assertSame([], $this->disk()->allFiles('barang'));
    }
}
