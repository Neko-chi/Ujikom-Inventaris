<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian tampilan popup (Turbo Frame "modal") dan panel profil kanan (Turbo Frame "laci").
 */
class PopupTest extends TestCase
{
    use RefreshDatabase;

    public function test_form_tambah_barang_dikirim_sebagai_isi_popup(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/barang/create', ['Turbo-Frame' => 'modal'])
            ->assertOk()
            ->assertSee('<turbo-frame id="modal">', false)
            ->assertSee('Tambah Barang')
            ->assertDontSee('id="sidebar"', false);
    }

    public function test_tanpa_turbo_frame_halaman_tetap_utuh(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $this->get('/barang/create')
            ->assertOk()
            ->assertSee('id="sidebar"', false)
            ->assertDontSee('<turbo-frame id="modal">', false);
    }

    public function test_halaman_daftar_tidak_dikirim_sebagai_popup(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        // Halaman daftar tidak punya isi popup sehingga browser menutup popup dan membuka halaman penuh
        $this->get('/barang', ['Turbo-Frame' => 'modal'])
            ->assertOk()
            ->assertDontSee('<turbo-frame id="modal">', false);
    }

    public function test_profil_dibuka_di_panel_kanan(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/profil', ['Turbo-Frame' => 'laci'])
            ->assertOk()
            ->assertSee('<turbo-frame id="laci">', false)
            ->assertSee('Profil Saya');
    }

    public function test_simpan_profil_dari_panel_kembali_ke_halaman_asal(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->put('/profil', ['nama_user' => 'Nama Baru'], [
                'Turbo-Frame' => 'laci',
                'X-Halaman-Asal' => url('/barang'),
            ])
            ->assertRedirect(url('/barang'));

        // Alamat dari situs lain ditolak, kembali ke halaman profil
        $this->put('/profil', ['nama_user' => 'Nama Baru'], [
            'Turbo-Frame' => 'laci',
            'X-Halaman-Asal' => 'https://situs-lain.test/jebakan',
        ])->assertRedirect('/profil');
    }
}
