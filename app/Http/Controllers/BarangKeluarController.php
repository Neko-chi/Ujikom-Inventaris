<?php

namespace App\Http\Controllers;

use App\Exceptions\StokTidakCukupException;
use App\Http\Requests\BarangKeluarRequest;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\Lokasi;
use App\Models\StokBarang;
use App\Services\GambarService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

/**
 * Transaksi barang keluar dengan tiga jenis:
 *  - Peminjaman : stok berkurang selama dipinjam, data tidak dihapus, dicatat pengembaliannya.
 *  - Pemindahan : barang dipindah ke ruangan lain (mis. lab), tetap tercatat sebagai stok di sana.
 *  - Pemusnahan : barang Rusak Berat dimusnahkan, stok berkurang permanen.
 *
 * Alur (pemisahan tugas):
 *  1. Operator mengajukan + rincian kondisi + foto bukti (wajib) -> Pending, stok belum berubah.
 *  2. Manager memeriksa rincian kondisi (boleh mengoreksi jumlahnya), lalu
 *     Disetujui (stok berubah) atau Ditolak (wajib alasan).
 * Siapa dan kapan verifikasi dilakukan dicatat di id_verifikator & tanggal_verifikasi.
 * Data pengajuan tidak dapat diubah/dihapus agar menjadi jejak audit.
 */
class BarangKeluarController extends Controller
{
    public function __construct(
        private StokService $stok,
        private GambarService $gambar,
    ) {}

    public function index(Request $request): View
    {
        $verifikasi = $request->query('verifikasi');
        $jenis = $request->query('jenis');

        $barangKeluar = BarangKeluar::with(['barang', 'user', 'verifikator', 'lokasiAsal', 'lokasiTujuan'])
            ->withSum('pengembalian as jumlah_kembali', 'jumlah')
            ->when(in_array($verifikasi, BarangKeluar::VERIFIKASI, true), fn ($q) => $q->where('verifikasi', $verifikasi))
            // Tab "Dipinjam": peminjaman disetujui yang belum dikembalikan seluruhnya
            ->when($verifikasi === 'Dipinjam', fn ($q) => $q->where('jenis_keluar', BarangKeluar::PEMINJAMAN)
                ->where('verifikasi', BarangKeluar::DISETUJUI)
                ->whereRaw('jumlah > (SELECT COALESCE(SUM(p.jumlah), 0) FROM pengembalian p WHERE p.id_keluar = barang_keluar.id_keluar)'))
            ->when(in_array($jenis, BarangKeluar::JENIS, true), fn ($q) => $q->where('jenis_keluar', $jenis))
            ->latest('tanggal')
            ->latest('id_keluar')
            ->paginate(10)
            ->withQueryString();

        return view('barang-keluar.index', compact('barangKeluar', 'verifikasi', 'jenis'));
    }

    public function show(BarangKeluar $barangKeluar): View
    {
        $barangKeluar->load(['barang.kategori', 'user', 'verifikator', 'lokasiAsal', 'lokasiTujuan', 'pengembalian.user', 'pengembalian.lokasi']);

        // Stok per kondisi di lokasi asal saat ini (untuk Manager sebelum menyetujui)
        $stokAsal = [];
        foreach (Barang::KONDISI as $kondisi) {
            $stokAsal[$kondisi] = $this->stok->tersedia($barangKeluar->id_barang, $barangKeluar->id_lokasi_asal, $kondisi);
        }

        return view('barang-keluar.show', [
            'barangKeluar' => $barangKeluar,
            'stokAsal' => $stokAsal,
            'lokasi' => Lokasi::orderBy('jenis')->orderBy('nama_lokasi')->get(),
        ]);
    }

    public function create(): View
    {
        // Peta stok untuk form: {id_barang: {id_lokasi: {kondisi: jumlah}}}
        $petaStok = [];
        foreach (StokBarang::where('jumlah', '>', 0)->get() as $s) {
            $petaStok[$s->id_barang][$s->id_lokasi][$s->kondisi] = $s->jumlah;
        }

        return view('barang-keluar.form', [
            'barangKeluar' => new BarangKeluar(['tanggal' => now(), 'jenis_keluar' => BarangKeluar::PEMINJAMAN]),
            'barang' => Barang::where('stok', '>', 0)->orderBy('kode_barang')->get(),
            'lokasi' => Lokasi::orderBy('jenis')->orderBy('nama_lokasi')->get(),
            'petaStok' => $petaStok,
        ]);
    }

    public function store(BarangKeluarRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['jenis_keluar', 'id_barang', 'id_lokasi_asal', 'tanggal', 'pemohon', 'tujuan']);
        $data['id_lokasi_tujuan'] = $data['jenis_keluar'] === BarangKeluar::PEMINDAHAN ? $request->validated('id_lokasi_tujuan') : null;
        $data['tanggal_kembali'] = $data['jenis_keluar'] === BarangKeluar::PEMINJAMAN ? $request->validated('tanggal_kembali') : null;
        $data['id_user'] = $request->user()->id_user;
        $data['verifikasi'] = BarangKeluar::PENDING;
        $data['foto'] = $this->gambar->simpan($request->file('foto'), 'barang-keluar');

        try {
            (new BarangKeluar($data))->isiRincian(BarangKeluar::rincianDariInput($request->validated()))->save();
        } catch (Throwable $e) {
            $this->gambar->hapus($data['foto']);
            throw $e;
        }

        return redirect()->route('barang-keluar.index')
            ->with('sukses', 'Pengajuan '.strtolower($data['jenis_keluar']).' berhasil dikirim dan menunggu verifikasi Manager.');
    }

    /**
     * Manager menyetujui. Rincian kondisi boleh dikoreksi (mis. Operator menulis 10 Baik,
     * ternyata 5 di antaranya Rusak Ringan); koreksi dicatat otomatis di catatan verifikasi.
     */
    public function setujui(Request $request, BarangKeluar $barangKeluar): RedirectResponse
    {
        $aturan = ['catatan_verifikasi' => ['nullable', 'string', 'max:255']];
        foreach (Barang::KOLOM_KONDISI as $kolom) {
            $aturan[$kolom] = ['nullable', 'integer', 'min:0'];
        }
        $request->validate($aturan);

        try {
            $hasil = DB::transaction(function () use ($request, $barangKeluar) {
                $permintaan = $this->kunciPermintaanPending($barangKeluar);
                if ($permintaan === null) {
                    return 'sudah';
                }

                $catatan = $request->input('catatan_verifikasi');
                $rincianBaru = $request->hasAny(array_values(Barang::KOLOM_KONDISI))
                    ? BarangKeluar::rincianDariInput($request->all())
                    : $permintaan->rincian();

                if ($rincianBaru !== $permintaan->rincian()) {
                    $error = BarangKeluarRequest::periksaRincian($permintaan->jenis_keluar, $permintaan->barang, $permintaan->id_lokasi_asal, $rincianBaru);
                    if ($error) {
                        throw ValidationException::withMessages($error);
                    }
                    $catatan = trim('Rincian dikoreksi Manager dari ('.$permintaan->ringkasKondisi().') menjadi ('
                        .(clone $permintaan)->isiRincian($rincianBaru)->ringkasKondisi().'). '.$catatan);
                    $permintaan->isiRincian($rincianBaru);
                }

                $this->terapkanStok($permintaan);
                $this->simpanVerifikasi($request, $permintaan, BarangKeluar::DISETUJUI, $catatan);

                return 'ok';
            });
        } catch (StokTidakCukupException $e) {
            return back()->with('gagal', $e->getMessage());
        }

        if ($hasil === 'sudah') {
            return back()->with('gagal', 'Permintaan ini sudah diverifikasi sebelumnya.');
        }

        return redirect()->route('barang-keluar.show', $barangKeluar)
            ->with('sukses', 'Permintaan disetujui, stok barang telah diperbarui.');
    }

    /**
     * Manager menolak permintaan: stok tidak berubah, alasan wajib diisi.
     */
    public function tolak(Request $request, BarangKeluar $barangKeluar): RedirectResponse
    {
        $request->validate(
            ['catatan_verifikasi' => ['required', 'string', 'max:255']],
            ['catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.']
        );

        $berhasil = DB::transaction(function () use ($request, $barangKeluar) {
            $permintaan = $this->kunciPermintaanPending($barangKeluar);
            if ($permintaan === null) {
                return false;
            }

            $this->simpanVerifikasi($request, $permintaan, BarangKeluar::DITOLAK, $request->input('catatan_verifikasi'));

            return true;
        });

        if (! $berhasil) {
            return back()->with('gagal', 'Permintaan ini sudah diverifikasi sebelumnya.');
        }

        return redirect()->route('barang-keluar.show', $barangKeluar)
            ->with('sukses', 'Permintaan barang keluar ditolak.');
    }

    /**
     * Perubahan stok sesuai jenis barang keluar.
     *
     * @throws StokTidakCukupException
     */
    private function terapkanStok(BarangKeluar $permintaan): void
    {
        $barang = Barang::lockForUpdate()->findOrFail($permintaan->id_barang);
        $this->stok->kurangiRincian($barang, $permintaan->id_lokasi_asal, $permintaan->rincian());

        // Pemindahan: barang tetap milik sekolah, kini tercatat di ruangan tujuan
        if ($permintaan->jenis_keluar === BarangKeluar::PEMINDAHAN) {
            $this->stok->tambahRincian($barang, $permintaan->id_lokasi_tujuan, $permintaan->rincian());
        }
    }

    /**
     * Mengunci baris permintaan (SELECT ... FOR UPDATE) lalu memastikan statusnya masih Pending.
     * Harus dipanggil di dalam DB::transaction(). Jika dua Manager memverifikasi bersamaan,
     * Manager kedua menunggu sampai transaksi pertama selesai, lalu mendapati status
     * sudah bukan Pending sehingga stok tidak berubah dua kali.
     */
    private function kunciPermintaanPending(BarangKeluar $barangKeluar): ?BarangKeluar
    {
        $permintaan = BarangKeluar::lockForUpdate()->findOrFail($barangKeluar->id_keluar);

        return $permintaan->isPending() ? $permintaan : null;
    }

    /**
     * Mencatat hasil verifikasi beserta Manager yang melakukannya.
     */
    private function simpanVerifikasi(Request $request, BarangKeluar $barangKeluar, string $hasil, ?string $catatan): void
    {
        $barangKeluar->fill([
            'verifikasi' => $hasil,
            'id_verifikator' => $request->user()->id_user,
            'tanggal_verifikasi' => now()->toDateString(),
            'catatan_verifikasi' => $catatan ? mb_substr($catatan, 0, 255) : null,
        ])->save();
    }
}
