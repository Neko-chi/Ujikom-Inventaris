<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\Lokasi;
use App\Models\PemeriksaanKondisi;
use App\Models\StokBarang;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Pengujian pemeriksaan kondisi: menyesuaikan kondisi di data dengan kondisi fisik barang.
 * Operator mengajukan, Manager menyetujui / menolak. Total stok tidak berubah.
 */
class PemeriksaanKondisiTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->manager = User::factory()->manager()->create();
        $this->operator = User::factory()->operator()->create();
    }

    /** Data form pengajuan (tanpa foto). */
    private function dataPemeriksaan(Barang $barang, int $jumlah, string $asal = Barang::BAIK, string $baru = Barang::RUSAK_RINGAN): array
    {
        return [
            'id_barang' => $barang->id_barang,
            'id_lokasi' => $this->lokasi()->id_lokasi,
            'tanggal' => '2026-11-17',
            'kondisi_asal' => $asal,
            'kondisi_baru' => $baru,
            'jumlah' => $jumlah,
            'alasan' => 'Kaki meja goyang saat dicek',
        ];
    }

    /** Membuat pengajuan Pending langsung di database. */
    private function buatPemeriksaan(Barang $barang, int $jumlah, string $asal = Barang::BAIK, string $baru = Barang::RUSAK_RINGAN): PemeriksaanKondisi
    {
        return PemeriksaanKondisi::create($this->dataPemeriksaan($barang, $jumlah, $asal, $baru) + ['id_user' => $this->operator->id_user])->fresh();
    }

    public function test_operator_mengajukan_pemeriksaan_berstatus_pending_dan_stok_tetap(): void
    {
        $barang = $this->buatBarang(['nama_barang' => 'Meja Guru', 'stok' => 20]);

        $this->actingAs($this->operator)
            ->post('/pemeriksaan-kondisi', $this->dataPemeriksaan($barang, 10) + ['foto' => $this->gambarPalsu()])
            ->assertRedirect('/pemeriksaan-kondisi');

        $p = PemeriksaanKondisi::first();
        $this->assertSame(BarangKeluar::PENDING, $p->verifikasi);
        $this->assertSame($this->operator->id_user, $p->id_user);
        $this->disk()->assertExists($p->foto);
        $this->assertSame(20, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(0, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_RINGAN));
    }

    public function test_pengajuan_wajib_foto_alasan_dan_kondisi_harus_berbeda(): void
    {
        $barang = $this->buatBarang(['stok' => 20]);

        $this->actingAs($this->operator)
            ->post('/pemeriksaan-kondisi', ['alasan' => ''] + $this->dataPemeriksaan($barang, 5, Barang::BAIK, Barang::BAIK))
            ->assertSessionHasErrors(['foto', 'kondisi_baru']);

        $this->post('/pemeriksaan-kondisi', array_merge($this->dataPemeriksaan($barang, 5), ['alasan' => '', 'foto' => $this->gambarPalsu()]))
            ->assertSessionHasErrors('alasan');

        $this->assertSame(0, PemeriksaanKondisi::count());
    }

    public function test_jumlah_tidak_boleh_melebihi_stok_kondisi_asal_di_lokasi(): void
    {
        $barang = $this->buatBarang([], 'Furnitur', [20, 0, 0]);

        $this->actingAs($this->operator)
            ->post('/pemeriksaan-kondisi', $this->dataPemeriksaan($barang, 21) + ['foto' => $this->gambarPalsu()])
            ->assertSessionHasErrors('jumlah');

        // Kondisi asal Rusak Ringan belum ada stoknya
        $this->post('/pemeriksaan-kondisi', $this->dataPemeriksaan($barang, 1, Barang::RUSAK_RINGAN, Barang::BAIK) + ['foto' => $this->gambarPalsu()])
            ->assertSessionHasErrors('jumlah');

        $this->assertSame(0, PemeriksaanKondisi::count());
    }

    public function test_disetujui_memindahkan_jumlah_antar_kondisi_tanpa_mengubah_total(): void
    {
        $barang = $this->buatBarang(['stok' => 20]);
        $p = $this->buatPemeriksaan($barang, 10);

        $this->actingAs($this->manager)
            ->patch(route('pemeriksaan-kondisi.setujui', $p), ['catatan_verifikasi' => 'Sudah dicek langsung'])
            ->assertSessionHas('sukses');

        $p->refresh();
        $this->assertSame(BarangKeluar::DISETUJUI, $p->verifikasi);
        $this->assertSame($this->manager->id_user, $p->id_verifikator);
        $this->assertTrue($p->tanggal_verifikasi->isToday());
        $this->assertSame(10, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(10, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_RINGAN));
        $this->assertSame(20, $barang->fresh()->stok);
    }

    public function test_ditolak_wajib_alasan_dan_stok_tidak_berubah(): void
    {
        $barang = $this->buatBarang(['stok' => 20]);
        $p = $this->buatPemeriksaan($barang, 10);

        $this->actingAs($this->manager)
            ->patch(route('pemeriksaan-kondisi.tolak', $p), ['catatan_verifikasi' => ''])
            ->assertSessionHasErrors('catatan_verifikasi');

        $this->patch(route('pemeriksaan-kondisi.tolak', $p), ['catatan_verifikasi' => 'Setelah dicek, meja masih baik'])
            ->assertSessionHas('sukses');

        $this->assertSame(BarangKeluar::DITOLAK, $p->fresh()->verifikasi);
        $this->assertSame(20, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
    }

    public function test_persetujuan_gagal_jika_stok_kondisi_asal_sudah_tidak_cukup(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);
        $p = $this->buatPemeriksaan($barang, 5);
        // Sebelum disetujui, stok Baik berkurang (mis. barang keluar lain disetujui)
        StokBarang::where(['id_barang' => $barang->id_barang, 'kondisi' => Barang::BAIK])->update(['jumlah' => 3]);

        $this->actingAs($this->manager)
            ->patch(route('pemeriksaan-kondisi.setujui', $p))
            ->assertSessionHas('gagal');

        $this->assertSame(BarangKeluar::PENDING, $p->fresh()->verifikasi);
        $this->assertSame(3, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
    }

    public function test_pemeriksaan_yang_sudah_diverifikasi_tidak_bisa_diverifikasi_ulang(): void
    {
        $barang = $this->buatBarang(['stok' => 20]);
        $p = $this->buatPemeriksaan($barang, 5);

        $this->actingAs($this->manager)->patch(route('pemeriksaan-kondisi.setujui', $p));
        $this->patch(route('pemeriksaan-kondisi.setujui', $p))->assertSessionHas('gagal');

        $this->assertSame(15, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(5, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_RINGAN));
    }

    public function test_hak_akses_operator_mengajukan_dan_hanya_manager_memverifikasi(): void
    {
        $barang = $this->buatBarang(['stok' => 20]);
        $p = $this->buatPemeriksaan($barang, 5);
        $admin = User::factory()->admin()->create();

        foreach ([$admin, $this->manager] as $user) {
            $this->actingAs($user)->get('/pemeriksaan-kondisi/create')->assertForbidden();
        }
        foreach ([$admin, $this->operator] as $user) {
            $this->actingAs($user)->patch(route('pemeriksaan-kondisi.setujui', $p))->assertForbidden();
            $this->actingAs($user)->patch(route('pemeriksaan-kondisi.tolak', $p), ['catatan_verifikasi' => 'x'])->assertForbidden();
        }
        foreach ([$admin, $this->operator, $this->manager] as $user) {
            $this->actingAs($user)->get('/pemeriksaan-kondisi')->assertOk()->assertSee($barang->nama_barang);
            $this->actingAs($user)->get(route('pemeriksaan-kondisi.show', $p))->assertOk();
        }
        $this->assertSame(BarangKeluar::PENDING, $p->fresh()->verifikasi);
    }

    // ---------- Dua kasus yang menjadi alasan fitur ini dibuat ----------

    public function test_setelah_pemeriksaan_operator_dapat_mengeluarkan_barang_baik_dan_rusak_ringan(): void
    {
        // Di data 20 meja Baik, ternyata 10 rusak ringan
        $barang = $this->buatBarang(['nama_barang' => 'Meja Guru', 'satuan' => 'buah', 'stok' => 20], 'Furnitur');
        $formKeluar = [
            'jenis_keluar' => BarangKeluar::PEMINDAHAN, 'id_barang' => $barang->id_barang,
            'id_lokasi_asal' => $this->lokasi()->id_lokasi, 'id_lokasi_tujuan' => $this->lokasi('Lab TKJ 3', Lokasi::RUANGAN)->id_lokasi,
            'tanggal' => '2026-11-17', 'pemohon' => 'Budi', 'tujuan' => 'Meja lab',
        ] + $this->kondisi(10, 10);

        // Sebelum pemeriksaan: ditolak karena stok Rusak Ringan di data 0
        $this->actingAs($this->operator)
            ->post('/barang-keluar', $formKeluar + ['foto' => $this->gambarPalsu()])
            ->assertSessionHasErrors('jumlah_rusak_ringan');

        $p = $this->buatPemeriksaan($barang, 10);
        $this->actingAs($this->manager)->patch(route('pemeriksaan-kondisi.setujui', $p));

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $formKeluar + ['foto' => $this->gambarPalsu()])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/barang-keluar');
    }

    public function test_barang_di_data_baik_yang_ternyata_rusak_berat_dapat_dimusnahkan(): void
    {
        $barang = $this->buatBarang(['stok' => 8]);
        $p = $this->buatPemeriksaan($barang, 2, Barang::BAIK, Barang::RUSAK_BERAT);
        $this->actingAs($this->manager)->patch(route('pemeriksaan-kondisi.setujui', $p));

        $this->actingAs($this->operator)->post('/barang-keluar', [
            'jenis_keluar' => BarangKeluar::PEMUSNAHAN, 'id_barang' => $barang->id_barang,
            'id_lokasi_asal' => $this->lokasi()->id_lokasi, 'tanggal' => '2026-11-17',
            'pemohon' => 'Tim Sarpras', 'tujuan' => 'Patah, tidak dapat diperbaiki', 'foto' => $this->gambarPalsu(),
        ] + $this->kondisi(0, 0, 2))->assertSessionHasNoErrors();

        $this->assertSame(1, BarangKeluar::where('jenis_keluar', BarangKeluar::PEMUSNAHAN)->count());
    }

    public function test_pengguna_lokasi_dan_barang_yang_punya_pemeriksaan_tidak_dapat_dihapus(): void
    {
        $barang = $this->buatBarang(['stok' => 20]);
        $p = $this->buatPemeriksaan($barang, 5);
        $this->actingAs($this->manager)->patch(route('pemeriksaan-kondisi.tolak', $p), ['catatan_verifikasi' => 'Masih baik']);

        $this->assertTrue($this->operator->punyaTransaksi());
        $this->assertTrue($this->manager->punyaTransaksi());
        $this->assertTrue($this->lokasi()->sudahDipakai());

        $this->actingAs(User::factory()->admin()->create())
            ->delete(route('barang.destroy', $barang))
            ->assertSessionHas('gagal');
        $this->assertDatabaseHas('barang', ['id_barang' => $barang->id_barang]);
    }
}
