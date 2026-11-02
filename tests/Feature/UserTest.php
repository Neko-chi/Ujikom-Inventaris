<?php

namespace Tests\Feature;

use App\Models\BarangMasuk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pengujian fitur kelola pengguna oleh Admin.
 */
class UserTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->actingAs($this->admin);
    }

    public function test_admin_dapat_menambah_operator(): void
    {
        $this->post('/user', [
            'nama_user' => 'Siti Aminah',
            'username' => 'operator_siti',
            'password' => 'rahasia123',
            'password_confirmation' => 'rahasia123',
            'role' => 'Operator',
        ])->assertRedirect('/user');

        $user = User::where('username', 'operator_siti')->first();
        $this->assertSame('Operator', $user->role);
        $this->assertTrue(Hash::check('rahasia123', $user->password));
    }

    public function test_username_harus_unik_dan_role_harus_valid(): void
    {
        User::factory()->create(['username' => 'operator_ohim']);

        $this->post('/user', [
            'nama_user' => 'X', 'username' => 'operator_ohim',
            'password' => 'rahasia123', 'password_confirmation' => 'rahasia123', 'role' => 'Kepala Sekolah',
        ])->assertSessionHasErrors(['username', 'role']);
    }

    public function test_admin_tidak_dapat_mengubah_data_atau_password_pengguna_lain(): void
    {
        $operator = User::factory()->operator()->create(['nama_user' => 'Nama Lama', 'password' => 'lama12345']);

        // Halaman ubah dan proses ubah tidak tersedia.
        $this->get("/user/{$operator->id_user}/edit")->assertNotFound();
        $this->put("/user/{$operator->id_user}", [
            'nama_user' => 'Nama Baru', 'username' => $operator->username, 'role' => 'Operator',
            'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertStatus(405);

        $operator->refresh();
        $this->assertSame('Nama Lama', $operator->nama_user);
        $this->assertTrue(Hash::check('lama12345', $operator->password));
    }

    public function test_daftar_pengguna_hanya_menampilkan_tombol_hapus_untuk_pengguna_lain(): void
    {
        User::factory()->operator()->create();

        $this->get(route('user.index'))
            ->assertOk()
            ->assertSee('title="Hapus"', false)
            ->assertSee('title="Ubah lewat Profil"', false)
            ->assertDontSee('title="Ubah"', false);
    }

    public function test_admin_tidak_dapat_menghapus_akun_sendiri(): void
    {
        $this->delete(route('user.destroy', $this->admin))->assertSessionHas('gagal');

        $this->assertModelExists($this->admin);
    }

    public function test_pengguna_yang_memiliki_transaksi_tidak_dapat_dihapus(): void
    {
        $operator = User::factory()->operator()->create();
        BarangMasuk::create([
            'id_barang' => $this->buatBarang()->id_barang, 'id_user' => $operator->id_user, 'tanggal' => '2026-10-01',
            'jumlah' => 1, 'sumber_barang' => 'Pembelian', 'status_barang' => 'Baik',
        ]);

        $this->delete(route('user.destroy', $operator))->assertSessionHas('gagal');
        $this->assertModelExists($operator);
    }

    public function test_pengguna_tanpa_transaksi_dapat_dihapus(): void
    {
        $operator = User::factory()->operator()->create();

        $this->delete(route('user.destroy', $operator))->assertRedirect('/user');
        $this->assertModelMissing($operator);
    }
}
