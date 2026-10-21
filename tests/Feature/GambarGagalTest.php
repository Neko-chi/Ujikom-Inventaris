<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Pengujian saat penyimpanan gambar (seperti Supabase Storage) gagal / tidak dapat dihubungi.
 * Sebelumnya kondisi ini menghasilkan "500 Server Error"; sekarang pengguna dikembalikan
 * ke form dengan pesan yang jelas dan tidak ada data yang tersimpan setengah jadi.
 */
class GambarGagalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disk S3 palsu yang mengarah ke alamat yang menolak koneksi
        config([
            'filesystems.disks.s3_gagal' => [
                'driver' => 's3',
                'key' => 'kunci-palsu',
                'secret' => 'rahasia-palsu',
                'region' => 'ap-southeast-1',
                'bucket' => 'gambar',
                'endpoint' => 'http://127.0.0.1:1',
                'use_path_style_endpoint' => true,
                'throw' => true,
                // Langsung gagal tanpa percobaan ulang agar test cepat
                'retries' => 0,
                'http' => ['connect_timeout' => 1, 'timeout' => 2],
            ],
            'filesystems.upload_disk' => 's3_gagal',
        ]);
    }

    public function test_foto_profil_gagal_disimpan_tidak_menyebabkan_error_500(): void
    {
        $user = User::factory()->create(['nama_user' => 'Nama Lama']);

        $this->actingAs($user)
            ->from('/profil')
            ->put('/profil', ['nama_user' => 'Nama Baru', 'foto' => $this->gambarPalsu('profil.png')])
            ->assertRedirect('/profil')
            ->assertSessionHas('gagal');

        $user->refresh();
        $this->assertSame('Nama Lama', $user->nama_user);
        $this->assertNull($user->foto);
    }

    public function test_pesan_di_layar_tidak_berisi_detail_teknis(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->put('/profil', ['nama_user' => 'Admin', 'foto' => $this->gambarPalsu('profil.png')])
            ->assertSessionHas('gagal', fn (string $pesan) => ! str_contains($pesan, 's3_gagal'));
    }

    public function test_penyebab_gagal_dicatat_dalam_satu_baris_pendek(): void
    {
        $pesan = [];
        Log::listen(function (MessageLogged $log) use (&$pesan) {
            $pesan[] = $log->message;
        });

        $this->actingAs(User::factory()->create())
            ->put('/profil', ['nama_user' => 'Nama', 'foto' => $this->gambarPalsu('profil.png')]);

        $baris = collect($pesan)->first(fn (string $m) => str_starts_with($m, 'Gambar gagal disimpan: disk "s3_gagal"'));

        $this->assertNotNull($baris);
        $this->assertStringNotContainsString("\n", $baris);
        $this->assertLessThan(1000, strlen($baris));
    }

    public function test_gambar_barang_gagal_disimpan_tidak_menyebabkan_error_500(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Alat Tulis']);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/barang/create')
            ->post('/barang', [
                'id_kategori' => $kategori->id_kategori,
                'nama_barang' => 'Pulpen',
                'satuan' => 'pack',
                'gambar' => $this->gambarPalsu('pulpen.jpg'),
            ])
            ->assertRedirect('/barang/create')
            ->assertSessionHas('gagal')
            ->assertSessionHasInput('nama_barang', 'Pulpen');

        $this->assertDatabaseCount('barang', 0);
    }

    public function test_foto_barang_keluar_gagal_disimpan_tidak_menyebabkan_error_500(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->actingAs(User::factory()->operator()->create())
            ->from('/barang-keluar/create')
            ->post('/barang-keluar', [
                'jenis_keluar' => BarangKeluar::PEMINJAMAN,
                'id_barang' => $barang->id_barang,
                'id_lokasi_asal' => $this->lokasi()->id_lokasi,
                'tanggal' => now()->toDateString(),
                'tanggal_kembali' => now()->addWeek()->toDateString(),
                'pemohon' => 'Budi',
                'tujuan' => 'Kelas 10',
                'foto' => $this->gambarPalsu('bukti.jpg'),
            ] + $this->kondisi(2))
            ->assertRedirect('/barang-keluar/create')
            ->assertSessionHas('gagal');

        $this->assertSame(0, BarangKeluar::count());
    }
}
