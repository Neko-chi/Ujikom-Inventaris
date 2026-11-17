<?php

namespace App\Http\Controllers;

use App\Exceptions\StokTidakCukupException;
use App\Http\Requests\PemeriksaanKondisiRequest;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\Lokasi;
use App\Models\PemeriksaanKondisi;
use App\Models\StokBarang;
use App\Services\GambarService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Pemeriksaan kondisi: menyesuaikan kondisi barang di data dengan kondisi fisiknya.
 * Contoh: 20 Meja Guru tercatat Baik, ternyata 10 Rusak Ringan; atau barang yang tercatat Baik
 * ternyata Rusak Berat sehingga perlu dimusnahkan.
 *
 * Alur (pemisahan tugas, sama seperti barang keluar):
 *  1. Operator mengajukan perubahan + alasan + foto bukti (wajib) -> Pending, stok belum berubah.
 *  2. Manager memeriksa, lalu Disetujui (kondisi berubah, total tetap) atau Ditolak (wajib alasan).
 * Data pengajuan tidak dapat diubah/dihapus agar menjadi jejak audit.
 */
class PemeriksaanKondisiController extends Controller
{
    public function __construct(
        private StokService $stok,
        private GambarService $gambar,
    ) {}

    public function index(Request $request): View
    {
        $verifikasi = $request->query('verifikasi');

        $pemeriksaan = PemeriksaanKondisi::with(['barang', 'lokasi', 'user', 'verifikator'])
            ->when(in_array($verifikasi, BarangKeluar::VERIFIKASI, true), fn ($q) => $q->where('verifikasi', $verifikasi))
            ->latest('tanggal')
            ->latest('id_pemeriksaan')
            ->paginate(10)
            ->withQueryString();

        return view('pemeriksaan-kondisi.index', compact('pemeriksaan', 'verifikasi'));
    }

    public function show(PemeriksaanKondisi $pemeriksaanKondisi): View
    {
        $pemeriksaanKondisi->load(['barang', 'lokasi', 'user', 'verifikator']);

        return view('pemeriksaan-kondisi.show', [
            'pemeriksaan' => $pemeriksaanKondisi,
            // Stok kondisi asal saat ini, untuk Manager sebelum menyetujui
            'tersedia' => $this->stok->tersedia($pemeriksaanKondisi->id_barang, $pemeriksaanKondisi->id_lokasi, $pemeriksaanKondisi->kondisi_asal),
        ]);
    }

    public function create(): View
    {
        // Peta stok untuk form: {id_barang: {id_lokasi: {kondisi: jumlah}}}
        $petaStok = [];
        foreach (StokBarang::where('jumlah', '>', 0)->get() as $s) {
            $petaStok[$s->id_barang][$s->id_lokasi][$s->kondisi] = $s->jumlah;
        }

        return view('pemeriksaan-kondisi.form', [
            'barang' => Barang::where('stok', '>', 0)->orderBy('kode_barang')->get(),
            'lokasi' => Lokasi::orderBy('jenis')->orderBy('nama_lokasi')->get(),
            'petaStok' => $petaStok,
        ]);
    }

    public function store(PemeriksaanKondisiRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['id_barang', 'id_lokasi', 'tanggal', 'kondisi_asal', 'kondisi_baru', 'jumlah', 'alasan']);
        $data['id_user'] = $request->user()->id_user;
        $data['verifikasi'] = BarangKeluar::PENDING;
        $data['foto'] = $this->gambar->simpan($request->file('foto'), 'pemeriksaan-kondisi');

        try {
            PemeriksaanKondisi::create($data);
        } catch (Throwable $e) {
            $this->gambar->hapus($data['foto']);
            throw $e;
        }

        return redirect()->route('pemeriksaan-kondisi.index')
            ->with('sukses', 'Pemeriksaan kondisi berhasil diajukan dan menunggu verifikasi Manager.');
    }

    /** Manager menyetujui: jumlah pindah dari kondisi asal ke kondisi baru, total stok tetap. */
    public function setujui(Request $request, PemeriksaanKondisi $pemeriksaanKondisi): RedirectResponse
    {
        $request->validate(['catatan_verifikasi' => ['nullable', 'string', 'max:255']]);

        try {
            $berhasil = DB::transaction(function () use ($request, $pemeriksaanKondisi) {
                $p = $this->kunciPending($pemeriksaanKondisi);
                if ($p === null) {
                    return false;
                }

                $barang = Barang::lockForUpdate()->findOrFail($p->id_barang);
                $this->stok->ubahKondisi($barang, $p->id_lokasi, $p->kondisi_asal, $p->kondisi_baru, $p->jumlah);
                $this->simpanVerifikasi($request, $p, BarangKeluar::DISETUJUI);

                return true;
            });
        } catch (StokTidakCukupException $e) {
            return back()->with('gagal', $e->getMessage());
        }

        if (! $berhasil) {
            return back()->with('gagal', 'Pemeriksaan ini sudah diverifikasi sebelumnya.');
        }

        return redirect()->route('pemeriksaan-kondisi.show', $pemeriksaanKondisi)
            ->with('sukses', 'Pemeriksaan disetujui, kondisi stok telah diperbarui.');
    }

    /** Manager menolak: stok tidak berubah, alasan wajib diisi. */
    public function tolak(Request $request, PemeriksaanKondisi $pemeriksaanKondisi): RedirectResponse
    {
        $request->validate(
            ['catatan_verifikasi' => ['required', 'string', 'max:255']],
            ['catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.']
        );

        $berhasil = DB::transaction(function () use ($request, $pemeriksaanKondisi) {
            $p = $this->kunciPending($pemeriksaanKondisi);
            if ($p === null) {
                return false;
            }
            $this->simpanVerifikasi($request, $p, BarangKeluar::DITOLAK);

            return true;
        });

        if (! $berhasil) {
            return back()->with('gagal', 'Pemeriksaan ini sudah diverifikasi sebelumnya.');
        }

        return redirect()->route('pemeriksaan-kondisi.show', $pemeriksaanKondisi)
            ->with('sukses', 'Pemeriksaan kondisi ditolak.');
    }

    /**
     * Mengunci baris (SELECT ... FOR UPDATE) lalu memastikan statusnya masih Pending,
     * agar dua Manager yang memverifikasi bersamaan tidak mengubah stok dua kali.
     */
    private function kunciPending(PemeriksaanKondisi $pemeriksaan): ?PemeriksaanKondisi
    {
        $p = PemeriksaanKondisi::lockForUpdate()->findOrFail($pemeriksaan->id_pemeriksaan);

        return $p->isPending() ? $p : null;
    }

    private function simpanVerifikasi(Request $request, PemeriksaanKondisi $pemeriksaan, string $hasil): void
    {
        $pemeriksaan->fill([
            'verifikasi' => $hasil,
            'id_verifikator' => $request->user()->id_user,
            'tanggal_verifikasi' => now()->toDateString(),
            'catatan_verifikasi' => $request->input('catatan_verifikasi') ?: null,
        ])->save();
    }
}
