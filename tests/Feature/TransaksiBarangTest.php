<?php

namespace Tests\Feature;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Lokasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * Pengujian alur transaksi:
 *  - Operator mencatat barang masuk (ke lokasi, per kondisi) dan mengajukan barang keluar
 *    (Peminjaman / Pemindahan / Pemusnahan), serta mencatat pengembalian pinjaman.
 *  - Admin mengoreksi barang masuk.
 *  - Manager memverifikasi barang keluar (boleh mengoreksi rincian kondisi).
 */
class TransaksiBarangTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $manager;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->admin()->create();
        $this->manager = User::factory()->manager()->create();
        $this->operator = User::factory()->operator()->create();
    }

    /** Data pengajuan barang keluar (tanpa foto). */
    private function dataKeluar(Barang $barang, array $kondisi, string $jenis = BarangKeluar::PEMINJAMAN, array $tambahan = []): array
    {
        return array_merge([
            'jenis_keluar' => $jenis,
            'id_barang' => $barang->id_barang,
            'id_lokasi_asal' => $this->lokasi()->id_lokasi,
            'tanggal' => '2026-10-05',
            'tanggal_kembali' => $jenis === BarangKeluar::PEMINJAMAN ? '2026-10-12' : null,
            'pemohon' => 'Budi Santoso',
            'tujuan' => 'Praktik jaringan',
        ], $kondisi, $tambahan);
    }

    /** Data form pengajuan lengkap dengan foto bukti (wajib). */
    private function formKeluar(Barang $barang, array $kondisi, string $jenis = BarangKeluar::PEMINJAMAN, array $tambahan = []): array
    {
        return $this->dataKeluar($barang, $kondisi, $jenis, $tambahan) + ['foto' => $this->gambarPalsu()];
    }

    /** Membuat permintaan Pending langsung di database. */
    private function buatPermintaanKeluar(Barang $barang, array $kondisi, string $jenis = BarangKeluar::PEMINJAMAN, array $tambahan = []): BarangKeluar
    {
        $data = $this->dataKeluar($barang, [], $jenis, $tambahan) + ['id_user' => $this->operator->id_user];

        $keluar = new BarangKeluar($data);
        $keluar->isiRincian(BarangKeluar::rincianDariInput($kondisi))->save();

        return $keluar->fresh();
    }

    private function dataMasuk(Barang $barang, array $kondisi, ?Lokasi $lokasi = null): array
    {
        return [
            'id_barang' => $barang->id_barang, 'id_lokasi' => ($lokasi ?? $this->lokasi())->id_lokasi,
            'tanggal' => '2026-09-15', 'sumber_barang' => 'Pembelian',
        ] + $kondisi;
    }

    private function buatBarangMasuk(Barang $barang, array $kondisi): BarangMasuk
    {
        $masuk = new BarangMasuk($this->dataMasuk($barang, []) + ['id_user' => $this->operator->id_user]);
        $masuk->isiRincian(BarangMasuk::rincianDariInput($kondisi))->save();

        return $masuk;
    }

    // ---------- Barang masuk ----------

    public function test_operator_mencatat_barang_masuk_per_kondisi_ke_lokasi_tertentu(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);
        $gudang2 = $this->lokasi('Gudang 2');

        // Dari 10 barang yang datang, 8 baik dan 2 rusak ringan; disimpan di Gudang 2
        $this->actingAs($this->operator)
            ->post('/barang-masuk', $this->dataMasuk($barang, $this->kondisi(8, 2), $gudang2))
            ->assertRedirect('/barang-masuk');

        $this->assertSame(15, $barang->fresh()->stok);
        $this->assertSame(8, $this->stokDi($barang, $gudang2, Barang::BAIK));
        $this->assertSame(2, $this->stokDi($barang, $gudang2, Barang::RUSAK_RINGAN));
        $this->assertSame(5, $this->stokDi($barang, $this->lokasi())); // stok gudang utama tidak berubah
        $this->assertDatabaseHas('barang_masuk', ['id_barang' => $barang->id_barang, 'id_lokasi' => $gudang2->id_lokasi, 'jumlah' => 10, 'jumlah_baik' => 8, 'jumlah_rusak_ringan' => 2]);
    }

    public function test_barang_masuk_wajib_lokasi_dan_minimal_1_unit(): void
    {
        $barang = $this->buatBarang();
        $this->actingAs($this->operator);

        $this->post('/barang-masuk', $this->dataMasuk($barang, $this->kondisi(0)))->assertSessionHasErrors('jumlah');

        $data = $this->dataMasuk($barang, $this->kondisi(3));
        unset($data['id_lokasi']);
        $this->post('/barang-masuk', $data)->assertSessionHasErrors('id_lokasi');

        $this->assertSame(0, BarangMasuk::count());
    }

    public function test_admin_mengoreksi_barang_masuk_dan_stok_menyesuaikan(): void
    {
        $barang = $this->buatBarang(['stok' => 0]);
        $this->actingAs($this->operator)->post('/barang-masuk', $this->dataMasuk($barang, $this->kondisi(10)));
        $masuk = BarangMasuk::firstOrFail();

        // Ternyata 3 di antaranya rusak ringan
        $this->actingAs($this->admin)
            ->put(route('barang-masuk.update', $masuk), $this->dataMasuk($barang, $this->kondisi(7, 3)))
            ->assertRedirect('/barang-masuk');

        $this->assertSame(10, $barang->fresh()->stok);
        $this->assertSame(7, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(3, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_RINGAN));
    }

    public function test_admin_menghapus_barang_masuk_dan_stok_dikembalikan(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);
        $this->actingAs($this->operator)->post('/barang-masuk', $this->dataMasuk($barang, $this->kondisi(4)));

        $this->actingAs($this->admin)->delete(route('barang-masuk.destroy', BarangMasuk::firstOrFail()));

        $this->assertSame(5, $barang->fresh()->stok);
        $this->assertSame(0, BarangMasuk::count());
    }

    public function test_hapus_barang_masuk_ditolak_jika_stok_sudah_terpakai(): void
    {
        $barang = $this->buatBarang(['stok' => 2]);
        $masuk = $this->buatBarangMasuk($barang, $this->kondisi(8)); // seolah 6 unit sudah keluar

        $this->actingAs($this->admin)
            ->delete(route('barang-masuk.destroy', $masuk))
            ->assertSessionHas('gagal');

        $this->assertSame(1, BarangMasuk::count());
        $this->assertSame(2, $barang->fresh()->stok);
    }

    // ---------- Pengajuan barang keluar ----------

    public function test_pengajuan_barang_keluar_berstatus_pending_dan_stok_tetap(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(4)))
            ->assertRedirect('/barang-keluar');

        $keluar = BarangKeluar::firstOrFail();
        $this->assertSame(BarangKeluar::PENDING, $keluar->verifikasi);
        $this->assertSame(BarangKeluar::PEMINJAMAN, $keluar->jenis_keluar);
        $this->assertSame(4, $keluar->jumlah);
        $this->assertSame(10, $barang->fresh()->stok);
    }

    public function test_pengajuan_wajib_mengisi_pemohon(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $data = $this->formKeluar($barang, $this->kondisi(1));
        unset($data['pemohon']);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $data)
            ->assertSessionHasErrors('pemohon');
    }

    public function test_jumlah_per_kondisi_tidak_boleh_melebihi_stok_di_lokasi_asal(): void
    {
        // Stok gudang utama: 3 baik, 2 rusak ringan
        $barang = $this->buatBarang([], 'Alat Tulis', [3, 2, 0]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(3, 5)))
            ->assertSessionHasErrors('jumlah_rusak_ringan');

        $this->assertSame(0, BarangKeluar::count());
    }

    public function test_peminjaman_wajib_rencana_kembali_dan_pemindahan_wajib_lokasi_tujuan(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $this->actingAs($this->operator);

        $this->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(1), BarangKeluar::PEMINJAMAN, ['tanggal_kembali' => null]))
            ->assertSessionHasErrors('tanggal_kembali');

        $this->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(1), BarangKeluar::PEMINDAHAN))
            ->assertSessionHasErrors('id_lokasi_tujuan');

        $this->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(1), BarangKeluar::PEMINDAHAN, ['id_lokasi_tujuan' => $this->lokasi()->id_lokasi]))
            ->assertSessionHasErrors('id_lokasi_tujuan'); // tujuan sama dengan asal

        $this->assertSame(0, BarangKeluar::count());
    }

    public function test_pemusnahan_hanya_untuk_barang_rusak_berat(): void
    {
        $barang = $this->buatBarang([], 'Alat Tulis', [5, 0, 2]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(1, 0, 2), BarangKeluar::PEMUSNAHAN))
            ->assertSessionHasErrors('jumlah');

        $this->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(0, 0, 2), BarangKeluar::PEMUSNAHAN))
            ->assertRedirect('/barang-keluar');
        $this->assertSame(1, BarangKeluar::count());
    }

    // ---------- Verifikasi oleh Manager ----------

    public function test_peminjaman_disetujui_mengurangi_stok_dan_mencatat_verifikator(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4));

        $this->actingAs($this->manager)
            ->patch(route('barang-keluar.setujui', $keluar))
            ->assertSessionHas('sukses');

        $keluar->refresh();
        $this->assertSame(BarangKeluar::DISETUJUI, $keluar->verifikasi);
        $this->assertSame($this->manager->id_user, $keluar->id_verifikator);
        $this->assertTrue($keluar->tanggal_verifikasi->isToday());
        $this->assertSame(6, $barang->fresh()->stok);
        $this->assertSame('Dipinjam', $keluar->statusPinjam());
        $this->assertSame(4, $keluar->sisaPinjaman());
        $this->assertDatabaseHas('barang_keluar', ['id_keluar' => $keluar->id_keluar]); // data tidak dihapus
    }

    public function test_pemindahan_disetujui_memindahkan_stok_ke_ruangan_tanpa_mengurangi_total(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $lab = $this->lokasi('Lab TKJ 3', Lokasi::RUANGAN);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4), BarangKeluar::PEMINDAHAN, ['id_lokasi_tujuan' => $lab->id_lokasi]);

        $this->actingAs($this->manager)->patch(route('barang-keluar.setujui', $keluar));

        $this->assertSame(10, $barang->fresh()->stok);
        $this->assertSame(6, $this->stokDi($barang, $this->lokasi()));
        $this->assertSame(4, $this->stokDi($barang, $lab));
    }

    public function test_pemusnahan_disetujui_mengurangi_stok_rusak_berat_permanen(): void
    {
        $barang = $this->buatBarang([], 'Alat Tulis', [5, 0, 2]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(0, 0, 2), BarangKeluar::PEMUSNAHAN);

        $this->actingAs($this->manager)->patch(route('barang-keluar.setujui', $keluar));

        $this->assertSame(5, $barang->fresh()->stok);
        $this->assertSame(0, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_BERAT));
        $this->assertSame(5, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
    }

    public function test_manager_dapat_mengoreksi_rincian_kondisi_dan_koreksi_tercatat(): void
    {
        // 20 barang: Operator menulis semua baik, ternyata 5 baik, 10 rusak ringan, 5 rusak berat
        $barang = $this->buatBarang([], 'Alat Tulis', [20, 10, 5]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(20));

        $this->actingAs($this->manager)
            ->patch(route('barang-keluar.setujui', $keluar), $this->kondisi(5, 10, 5) + ['catatan_verifikasi' => 'Sudah dicek fisik.'])
            ->assertSessionHas('sukses');

        $keluar->refresh();
        $this->assertSame([Barang::BAIK => 5, Barang::RUSAK_RINGAN => 10, Barang::RUSAK_BERAT => 5], $keluar->rincian());
        $this->assertStringContainsString('Rincian dikoreksi Manager', $keluar->catatan_verifikasi);
        $this->assertStringContainsString('Sudah dicek fisik.', $keluar->catatan_verifikasi);
        $this->assertSame(15, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(0, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_RINGAN));
        $this->assertSame(0, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_BERAT));
    }

    public function test_koreksi_manager_tidak_boleh_melebihi_stok(): void
    {
        $barang = $this->buatBarang([], 'Alat Tulis', [10, 1, 0]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4));

        $this->actingAs($this->manager)
            ->patch(route('barang-keluar.setujui', $keluar), $this->kondisi(2, 3))
            ->assertSessionHasErrors('jumlah_rusak_ringan');

        $this->assertSame(BarangKeluar::PENDING, $keluar->fresh()->verifikasi);
        $this->assertSame(11, $barang->fresh()->stok);
    }

    public function test_manager_menolak_wajib_alasan_dan_stok_tidak_berubah(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4));
        $this->actingAs($this->manager);

        $this->patch(route('barang-keluar.tolak', $keluar))->assertSessionHasErrors('catatan_verifikasi');
        $this->assertSame(BarangKeluar::PENDING, $keluar->fresh()->verifikasi);

        $this->patch(route('barang-keluar.tolak', $keluar), ['catatan_verifikasi' => 'Barang masih dipakai.']);

        $keluar->refresh();
        $this->assertSame(BarangKeluar::DITOLAK, $keluar->verifikasi);
        $this->assertSame('Barang masih dipakai.', $keluar->catatan_verifikasi);
        $this->assertSame($this->manager->id_user, $keluar->id_verifikator);
        $this->assertSame(10, $barang->fresh()->stok);
    }

    public function test_persetujuan_gagal_jika_stok_sudah_tidak_cukup(): void
    {
        $barang = $this->buatBarang(['stok' => 3]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(100));

        $this->actingAs($this->manager)
            ->patch(route('barang-keluar.setujui', $keluar))
            ->assertSessionHas('gagal');

        $keluar->refresh();
        $this->assertSame(BarangKeluar::PENDING, $keluar->verifikasi);
        $this->assertNull($keluar->id_verifikator);
        $this->assertSame(3, $barang->fresh()->stok);
    }

    public function test_permintaan_yang_sudah_diverifikasi_tidak_bisa_diverifikasi_ulang(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4));

        $this->actingAs($this->manager);
        $this->patch(route('barang-keluar.setujui', $keluar));
        $this->patch(route('barang-keluar.setujui', $keluar))->assertSessionHas('gagal');

        $this->assertSame(6, $barang->fresh()->stok); // hanya berkurang sekali
    }

    public function test_detail_barang_keluar_menampilkan_pengaju_dan_verifikator(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4));
        $this->actingAs($this->manager)->patch(route('barang-keluar.setujui', $keluar));

        $this->actingAs($this->operator)
            ->get(route('barang-keluar.show', $keluar))
            ->assertOk()
            ->assertSee($this->operator->nama_user)
            ->assertSee($this->manager->nama_user)
            ->assertSee('Disetujui')
            ->assertSee('Catat Pengembalian');
    }

    // ---------- Pengembalian ----------

    public function test_pengembalian_sebagian_dengan_kondisi_berbeda_menambah_stok(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4));
        $this->actingAs($this->manager)->patch(route('barang-keluar.setujui', $keluar));

        // Kembali 3 dari 4: 2 masih baik, 1 sudah rusak ringan
        $this->actingAs($this->operator)
            ->post(route('pengembalian.store', $keluar), ['tanggal' => '2026-10-10', 'id_lokasi' => $this->lokasi()->id_lokasi] + $this->kondisi(2, 1))
            ->assertSessionHas('sukses');

        $keluar->refresh();
        $this->assertSame(1, $keluar->sisaPinjaman());
        $this->assertSame('Dikembalikan Sebagian', $keluar->statusPinjam());
        $this->assertSame(9, $barang->fresh()->stok);
        $this->assertSame(8, $this->stokDi($barang, $this->lokasi(), Barang::BAIK));
        $this->assertSame(1, $this->stokDi($barang, $this->lokasi(), Barang::RUSAK_RINGAN));

        // Sisa 1 dikembalikan: status selesai
        $this->post(route('pengembalian.store', $keluar), ['tanggal' => '2026-10-11', 'id_lokasi' => $this->lokasi()->id_lokasi] + $this->kondisi(1));
        $this->assertSame('Dikembalikan', $keluar->fresh()->statusPinjam());
        $this->assertSame(10, $barang->fresh()->stok);
    }

    public function test_pengembalian_tidak_boleh_melebihi_sisa_pinjaman(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4));
        $this->actingAs($this->manager)->patch(route('barang-keluar.setujui', $keluar));

        $this->actingAs($this->operator)
            ->post(route('pengembalian.store', $keluar), ['tanggal' => '2026-10-10', 'id_lokasi' => $this->lokasi()->id_lokasi] + $this->kondisi(5))
            ->assertSessionHasErrors('jumlah');

        $this->assertSame(6, $barang->fresh()->stok);
    }

    public function test_pengembalian_hanya_untuk_peminjaman_yang_disetujui(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $keluar = $this->buatPermintaanKeluar($barang, $this->kondisi(4)); // masih Pending

        $this->actingAs($this->operator)
            ->post(route('pengembalian.store', $keluar), ['tanggal' => '2026-10-10', 'id_lokasi' => $this->lokasi()->id_lokasi] + $this->kondisi(1))
            ->assertSessionHasErrors('jumlah');

        $this->assertSame(10, $barang->fresh()->stok);
    }

    // ---------- Unggah foto bukti ----------

    public function test_pengajuan_barang_keluar_wajib_menyertakan_foto(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->actingAs($this->operator)
            ->post('/barang-keluar', $this->dataKeluar($barang, $this->kondisi(2)))
            ->assertSessionHasErrors('foto');

        $this->assertSame(0, BarangKeluar::count());
    }

    public function test_foto_barang_keluar_tersimpan_dan_dapat_ditampilkan(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);

        $this->actingAs($this->operator)->post('/barang-keluar', $this->formKeluar($barang, $this->kondisi(2)));

        $keluar = BarangKeluar::firstOrFail();
        $this->assertStringStartsWith('barang-keluar/', $keluar->foto);
        $this->disk()->assertExists($keluar->foto);

        $this->actingAs($this->manager)
            ->get(route('barang-keluar.show', $keluar))
            ->assertOk()
            ->assertSee($keluar->foto_url);
    }

    public function test_file_bukan_gambar_atau_lebih_dari_2mb_ditolak(): void
    {
        $barang = $this->buatBarang(['stok' => 10]);
        $this->actingAs($this->operator);

        $data = $this->dataKeluar($barang, $this->kondisi(1)) + ['foto' => UploadedFile::fake()->create('dokumen.pdf', 50, 'application/pdf')];
        $this->post('/barang-keluar', $data)->assertSessionHasErrors('foto');

        $data = $this->dataKeluar($barang, $this->kondisi(1)) + ['foto' => $this->gambarPalsu('besar.jpg', 3000)];
        $this->post('/barang-keluar', $data)->assertSessionHasErrors('foto');

        $this->assertSame(0, BarangKeluar::count());
    }

    public function test_foto_barang_masuk_opsional_dan_ikut_terhapus(): void
    {
        $barang = $this->buatBarang(['stok' => 5]);
        $dataMasuk = $this->dataMasuk($barang, $this->kondisi(3));

        // Tanpa foto tetap berhasil
        $this->actingAs($this->operator)->post('/barang-masuk', $dataMasuk)->assertRedirect('/barang-masuk');
        $this->assertNull(BarangMasuk::firstOrFail()->foto);

        // Dengan foto: file tersimpan
        $this->post('/barang-masuk', $dataMasuk + ['foto' => $this->gambarPalsu('nota.png')]);
        $masuk = BarangMasuk::whereNotNull('foto')->firstOrFail();
        $this->disk()->assertExists($masuk->foto);

        // Admin menghapus data: file foto ikut dihapus
        $this->actingAs($this->admin)->delete(route('barang-masuk.destroy', $masuk));
        $this->disk()->assertMissing($masuk->foto);
    }

    public function test_foto_baru_dihapus_kembali_bila_koreksi_gagal(): void
    {
        $barang = $this->buatBarang(['stok' => 2]);
        $masuk = $this->buatBarangMasuk($barang, $this->kondisi(8));

        // Koreksi membalik 8 unit lama padahal stok tinggal 2: koreksi gagal.
        $this->actingAs($this->admin)
            ->put(route('barang-masuk.update', $masuk), $this->dataMasuk($barang, $this->kondisi(1)) + ['foto' => $this->gambarPalsu()])
            ->assertSessionHas('gagal');

        $this->assertNull($masuk->fresh()->foto);
        $this->assertSame([], $this->disk()->allFiles('barang-masuk'));
    }
}
