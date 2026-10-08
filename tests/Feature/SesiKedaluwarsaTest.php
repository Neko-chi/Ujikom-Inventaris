<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Pengujian penanganan error 419 "Page Expired" (token CSRF kedaluwarsa).
 *
 * Pemeriksaan CSRF otomatis dimatikan Laravel saat unit test, sehingga kondisi token
 * kedaluwarsa disimulasikan dengan route uji yang melempar TokenMismatchException.
 */
class SesiKedaluwarsaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::middleware('web')->post('/uji-token-kedaluwarsa', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });
    }

    public function test_token_kedaluwarsa_dikembalikan_ke_halaman_sebelumnya_dengan_pesan(): void
    {
        $this->actingAs(User::factory()->operator()->create())
            ->from('/barang-keluar/create')
            ->post('/uji-token-kedaluwarsa', ['pemohon' => 'Rina', 'tujuan' => 'Ruang TU'])
            ->assertRedirect('/barang-keluar/create')
            ->assertSessionHas('gagal')
            ->assertSessionHasErrors('sesi')
            ->assertSessionHasInput('pemohon', 'Rina');
    }

    public function test_password_tidak_ikut_disimpan_sebagai_isian_lama(): void
    {
        $this->from('/login')
            ->post('/uji-token-kedaluwarsa', ['username' => 'operator_siti', 'password' => 'rahasia123'])
            ->assertRedirect('/login')
            ->assertSessionHasInput('username', 'operator_siti')
            ->assertSessionMissing('_old_input.password');
    }

    public function test_permintaan_json_tetap_mendapat_status_419(): void
    {
        $this->postJson('/uji-token-kedaluwarsa')->assertStatus(419);
    }
}
