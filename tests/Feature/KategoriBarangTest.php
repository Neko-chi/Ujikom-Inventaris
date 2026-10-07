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

    public function test_tambah_barang_membuat_kode_otomatis(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Elektronik']);

        $this->post('/barang', [
            'id_kategori' => $kategori->id_kategori,
            'nama_barang' => 'Proyektor Epson',
            'stok' => 6,
            'satuan' => 'unit',
            'lokasi' => 'gudang utama',
            'status_barang' => 'Baik',
        ])->assertRedirect('/barang');

        $this->assertDatabaseHas('barang', ['kode_barang' => 'EK-001', 'nama_barang' => 'Proyektor Epson', 'stok' => 6]);
    }

    public function test_ubah_barang_tidak_mengubah_stok(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->put(route('barang.update', $barang), [
            'id_kategori' => $barang->id_kategori,
            'nama_barang' => 'Pulpen Hitam',
            'stok' => 999,
            'satuan' => 'pack',
            'lokasi' => 'sarpras',
            'status_barang' => 'Rusak Ringan',
        ])->assertRedirect('/barang');

        $barang->refresh();
        $this->assertSame('Pulpen Hitam', $barang->nama_barang);
        $this->assertSame(10, $barang->stok);
    }

    public function test_status_barang_harus_sesuai_kamus_data(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Furnitur']);

        $this->post('/barang', [
            'id_kategori' => $kategori->id_kategori,
            'nama_barang' => 'Meja',
            'stok' => 1,
            'satuan' => 'buah',
            'lokasi' => 'gudang',
            'status_barang' => 'Hilang',
        ])->assertSessionHasErrors('status_barang');

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
}
