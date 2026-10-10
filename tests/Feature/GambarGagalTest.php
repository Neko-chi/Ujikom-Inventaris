<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

    public function test_gambar_barang_gagal_disimpan_tidak_menyebabkan_error_500(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Alat Tulis']);

        $this->actingAs(User::factory()->admin()->create())
            ->from('/barang/create')
            ->post('/barang', [
                'id_kategori' => $kategori->id_kategori,
                'nama_barang' => 'Pulpen',
                'stok' => 5,
                'satuan' => 'pack',
                'lokasi' => 'sarpras',
                'status_barang' => 'Baik',
                'gambar' => $this->gambarPalsu('pulpen.jpg'),
            ])
            ->assertRedirect('/barang/create')
            ->assertSessionHas('gagal')
            ->assertSessionHasInput('nama_barang', 'Pulpen');

        $this->assertDatabaseCount('barang', 0);
    }

    public function test_foto_barang_keluar_gagal_disimpan_tidak_menyebabkan_error_500(): void
    {
        $kategori = Kategori::create(['nama_kategori' => 'Alat Tulis']);
        $barang = Barang::create([
            'kode_barang' => 'AT-001', 'id_kategori' => $kategori->id_kategori, 'nama_barang' => 'Pulpen',
            'stok' => 10, 'satuan' => 'pack', 'lokasi' => 'sarpras', 'status_barang' => 'Baik',
        ]);

        $this->actingAs(User::factory()->operator()->create())
            ->from('/barang-keluar/create')
            ->post('/barang-keluar', [
                'id_barang' => $barang->id_barang,
                'tanggal' => now()->toDateString(),
                'jumlah' => 2,
                'pemohon' => 'Budi',
                'tujuan' => 'Kelas 10',
                'status_barang' => 'Baik',
                'foto' => $this->gambarPalsu('bukti.jpg'),
            ])
            ->assertRedirect('/barang-keluar/create')
            ->assertSessionHas('gagal');

        $this->assertSame(0, BarangKeluar::count());
    }
}
