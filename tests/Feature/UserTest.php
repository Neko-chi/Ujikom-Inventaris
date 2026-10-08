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

    public function test_ubah_pengguna_tanpa_password_tidak_mengganti_password(): void
    {
        $user = User::factory()->operator()->create(['password' => 'lama12345']);

        $this->put(route('user.update', $user), [
            'nama_user' => 'Nama Baru', 'username' => $user->username, 'password' => '', 'role' => 'Operator',
        ])->assertRedirect('/user');

        $user->refresh();
        $this->assertSame('Nama Baru', $user->nama_user);
        $this->assertTrue(Hash::check('lama12345', $user->password));
    }

    public function test_admin_tidak_dapat_mengubah_role_dan_menghapus_akun_sendiri(): void
    {
        $this->put(route('user.update', $this->admin), [
            'nama_user' => $this->admin->nama_user, 'username' => $this->admin->username, 'role' => 'Operator',
        ])->assertSessionHas('gagal');

        $this->delete(route('user.destroy', $this->admin))->assertSessionHas('gagal');

        $this->assertSame('Admin', $this->admin->fresh()->role);
    }

    public function test_pilihan_role_terkunci_saat_admin_mengubah_akun_sendiri(): void
    {
        $this->get(route('user.edit', $this->admin))
            ->assertOk()
            ->assertSee('Role akun Anda sendiri tidak dapat diubah.');

        // Akun orang lain tetap bisa diubah role-nya.
        $operator = User::factory()->operator()->create();
        $this->get(route('user.edit', $operator))
            ->assertOk()
            ->assertDontSee('Role akun Anda sendiri tidak dapat diubah.');
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
