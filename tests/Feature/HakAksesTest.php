<?php

namespace Tests\Feature;

use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian pembatasan hak akses berdasarkan role (middleware CekRole).
 */
class HakAksesTest extends TestCase
{
    use RefreshDatabase;

    private function buatPermintaanPending(User $operator): BarangKeluar
    {
        return BarangKeluar::create([
            'id_barang' => $this->buatBarang()->id_barang,
            'id_user' => $operator->id_user,
            'tanggal' => '2026-10-08',
            'jumlah' => 1,
            'pemohon' => 'Budi',
            'tujuan' => 'Lab TKJ 3',
            'status_barang' => 'Baik',
        ]);
    }

    public function test_semua_role_dapat_membuka_halaman_daftar_dan_profil(): void
    {
        foreach ([User::factory()->admin()->create(), User::factory()->operator()->create(), User::factory()->manager()->create()] as $user) {
            $this->actingAs($user);
            $this->get('/dashboard')->assertOk();
            $this->get('/barang')->assertOk();
            $this->get('/barang-masuk')->assertOk();
            $this->get('/barang-keluar')->assertOk();
            $this->get('/profil')->assertOk();
        }
    }

    public function test_operator_tidak_dapat_mengakses_fitur_admin(): void
    {
        $this->actingAs(User::factory()->operator()->create());
        $barang = $this->buatBarang();

        $this->get('/user')->assertForbidden();
        $this->get('/kategori')->assertForbidden();
        $this->get(route('barang.edit', $barang))->assertForbidden();
        $this->delete(route('barang.destroy', $barang))->assertForbidden();
    }

    public function test_operator_tidak_dapat_mengoreksi_barang_masuk(): void
    {
        $operator = User::factory()->operator()->create();
        $masuk = BarangMasuk::create([
            'id_barang' => $this->buatBarang()->id_barang, 'id_user' => $operator->id_user, 'tanggal' => '2026-10-01',
            'jumlah' => 5, 'sumber_barang' => 'Pembelian', 'status_barang' => 'Baik',
        ]);

        $this->actingAs($operator);
        $this->get(route('barang-masuk.edit', $masuk))->assertForbidden();
        $this->delete(route('barang-masuk.destroy', $masuk))->assertForbidden();
    }

    public function test_admin_dan_manager_tidak_dapat_mencatat_transaksi(): void
    {
        // Pemisahan tugas: hanya Operator yang mencatat transaksi.
        foreach ([User::factory()->admin()->create(), User::factory()->manager()->create()] as $user) {
            $this->actingAs($user);
            $this->get('/barang-masuk/create')->assertForbidden();
            $this->get('/barang-keluar/create')->assertForbidden();
        }
    }

    public function test_manager_hanya_dapat_melihat_dan_tidak_dapat_menambah_atau_mengubah_data(): void
    {
        $this->actingAs(User::factory()->manager()->create());
        $barang = $this->buatBarang();

        $this->get(route('barang.show', $barang))->assertOk();
        $this->get('/barang/create')->assertForbidden();
        $this->post('/barang', [])->assertForbidden();
        $this->get(route('barang.edit', $barang))->assertForbidden();
        $this->get('/kategori')->assertForbidden();
        $this->get('/user')->assertForbidden();
    }

    public function test_hanya_manager_yang_dapat_memverifikasi_barang_keluar(): void
    {
        $operator = User::factory()->operator()->create();
        $keluar = $this->buatPermintaanPending($operator);

        foreach ([$operator, User::factory()->admin()->create()] as $user) {
            $this->actingAs($user)->patch(route('barang-keluar.setujui', $keluar))->assertForbidden();
            $this->actingAs($user)->patch(route('barang-keluar.tolak', $keluar), ['catatan_verifikasi' => 'x'])->assertForbidden();
        }

        $this->assertSame(BarangKeluar::PENDING, $keluar->fresh()->verifikasi);

        $this->actingAs(User::factory()->manager()->create())
            ->patch(route('barang-keluar.setujui', $keluar))
            ->assertSessionHas('sukses');

        $this->assertSame(BarangKeluar::DISETUJUI, $keluar->fresh()->verifikasi);
    }
}
