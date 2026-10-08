<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Pengujian fitur profil (semua role) dan unggah foto profil.
 */
class ProfilTest extends TestCase
{
    use RefreshDatabase;

    public function test_pengguna_dapat_mengubah_nama_dan_mengunggah_foto_profil(): void
    {
        $user = User::factory()->manager()->create();

        $this->actingAs($user)->put('/profil', [
            'nama_user' => 'Marco Ivanos S.Pd',
            'foto' => $this->gambarPalsu('saya.jpg'),
        ])->assertRedirect('/profil');

        $user->refresh();
        $this->assertSame('Marco Ivanos S.Pd', $user->nama_user);
        $this->assertStringStartsWith('profil/', $user->foto);
        $this->disk()->assertExists($user->foto);
    }

    public function test_mengganti_foto_menghapus_foto_lama(): void
    {
        $user = User::factory()->operator()->create();
        $this->actingAs($user)->put('/profil', ['nama_user' => $user->nama_user, 'foto' => $this->gambarPalsu('lama.jpg')]);
        $fotoLama = $user->fresh()->foto;

        $this->put('/profil', ['nama_user' => $user->nama_user, 'foto' => $this->gambarPalsu('baru.jpg')]);

        $this->disk()->assertMissing($fotoLama);
        $this->disk()->assertExists($user->fresh()->foto);
    }

    public function test_foto_profil_dapat_dihapus(): void
    {
        $user = User::factory()->admin()->create();
        $this->actingAs($user)->put('/profil', ['nama_user' => $user->nama_user, 'foto' => $this->gambarPalsu()]);
        $foto = $user->fresh()->foto;

        $this->put('/profil', ['nama_user' => $user->nama_user, 'hapus_foto' => '1']);

        $this->assertNull($user->fresh()->foto);
        $this->disk()->assertMissing($foto);
    }

    public function test_ganti_password_wajib_password_lama_yang_benar(): void
    {
        $user = User::factory()->operator()->create(['password' => 'lama12345']);
        $this->actingAs($user);

        $this->put('/profil', [
            'nama_user' => $user->nama_user, 'password_lama' => 'salah',
            'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertSessionHasErrors('password_lama');
        $this->assertTrue(Hash::check('lama12345', $user->fresh()->password));

        $this->put('/profil', [
            'nama_user' => $user->nama_user, 'password_lama' => 'lama12345',
            'password' => 'baru12345', 'password_confirmation' => 'baru12345',
        ])->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('baru12345', $user->fresh()->password));
    }

    public function test_profil_tidak_dapat_mengubah_role_atau_username(): void
    {
        $user = User::factory()->operator()->create(['username' => 'operator_siti']);

        $this->actingAs($user)->put('/profil', [
            'nama_user' => 'Siti', 'role' => 'Admin', 'username' => 'admin_palsu',
        ]);

        $user->refresh();
        $this->assertSame('Operator', $user->role);
        $this->assertSame('operator_siti', $user->username);
    }
}
