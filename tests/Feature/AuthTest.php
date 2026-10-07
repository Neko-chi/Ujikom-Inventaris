<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian fitur login, logout, dan proteksi halaman.
 */
class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_dapat_dibuka(): void
    {
        $this->get('/login')->assertOk()->assertSee('Gudang Sekolah');
    }

    public function test_login_berhasil_dengan_username_dan_password_benar(): void
    {
        User::factory()->create(['username' => 'admin_ohim', 'password' => 'admin123']);

        $this->post('/login', ['username' => 'admin_ohim', 'password' => 'admin123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticated();
    }

    public function test_login_gagal_dengan_password_salah(): void
    {
        User::factory()->create(['username' => 'admin_ohim', 'password' => 'admin123']);

        $this->from('/login')
            ->post('/login', ['username' => 'admin_ohim', 'password' => 'salah'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_password_tersimpan_dalam_bentuk_hash(): void
    {
        $user = User::factory()->create(['password' => 'admin123']);

        $this->assertNotSame('admin123', $user->getAttributes()['password']);
        $this->assertStringStartsWith('$2y$', $user->getAttributes()['password']);
    }

    public function test_tamu_diarahkan_ke_login_saat_membuka_dashboard(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_logout_mengakhiri_sesi(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/logout')
            ->assertRedirect('/login');

        $this->assertGuest();
    }
}
