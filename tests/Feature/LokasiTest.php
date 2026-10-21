<?php

namespace Tests\Feature;

use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian data master lokasi (gudang / ruangan) yang dikelola Admin.
 */
class LokasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dapat_menambah_dan_mengubah_lokasi(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->post('/lokasi', ['nama_lokasi' => 'Gudang 2', 'jenis' => Lokasi::GUDANG, 'keterangan' => 'Gedung B'])
            ->assertRedirect('/lokasi');
        $lokasi = Lokasi::where('nama_lokasi', 'Gudang 2')->firstOrFail();

        $this->put(route('lokasi.update', $lokasi), ['nama_lokasi' => 'Gudang Belakang', 'jenis' => Lokasi::GUDANG])
            ->assertRedirect('/lokasi');
        $this->assertSame('Gudang Belakang', $lokasi->fresh()->nama_lokasi);
    }

    public function test_nama_lokasi_unik_dan_jenis_harus_valid(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->lokasi('Gudang Utama');

        $this->post('/lokasi', ['nama_lokasi' => 'Gudang Utama', 'jenis' => Lokasi::GUDANG])->assertSessionHasErrors('nama_lokasi');
        $this->post('/lokasi', ['nama_lokasi' => 'Atap', 'jenis' => 'Lantai'])->assertSessionHasErrors('jenis');
    }

    public function test_lokasi_berisi_barang_tidak_dapat_dihapus(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->buatBarang(['stok' => 5]); // tersimpan di Gudang Utama
        $kosong = $this->lokasi('Lab JB 3', Lokasi::RUANGAN);

        $this->delete(route('lokasi.destroy', $this->lokasi()))->assertSessionHas('gagal');
        $this->assertDatabaseHas('lokasi', ['nama_lokasi' => 'Gudang Utama']);

        $this->delete(route('lokasi.destroy', $kosong))->assertSessionHas('sukses');
        $this->assertDatabaseMissing('lokasi', ['nama_lokasi' => 'Lab JB 3']);
    }

    public function test_halaman_lokasi_dan_filter_barang_per_lokasi(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $this->buatBarang(['nama_barang' => 'Pulpen', 'stok' => 5]);
        $lab = $this->lokasi('Lab TKJ 3', Lokasi::RUANGAN);

        $this->get('/lokasi')->assertOk()->assertSee('Gudang Utama')->assertSee('Lab TKJ 3');
        $this->get('/barang?lokasi='.$this->lokasi()->id_lokasi)->assertSee('Pulpen');
        $this->get('/barang?lokasi='.$lab->id_lokasi)->assertDontSee('Pulpen');
    }

    public function test_hanya_admin_yang_dapat_mengelola_lokasi(): void
    {
        foreach ([User::factory()->operator()->create(), User::factory()->manager()->create()] as $user) {
            $this->actingAs($user)->get('/lokasi')->assertForbidden();
            $this->actingAs($user)->post('/lokasi', ['nama_lokasi' => 'X', 'jenis' => Lokasi::GUDANG])->assertForbidden();
        }
    }
}
