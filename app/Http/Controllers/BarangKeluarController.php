<?php

namespace App\Http\Controllers;

use App\Exceptions\StokTidakCukupException;
use App\Http\Requests\BarangKeluarRequest;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Transaksi barang keluar.
 *
 * Alur (pemisahan tugas):
 *  1. Operator mengajukan permintaan -> verifikasi = Pending, stok belum berubah.
 *  2. Admin memverifikasi -> Disetujui (stok berkurang) atau Ditolak (wajib alasan).
 * Siapa dan kapan verifikasi dilakukan dicatat di id_verifikator & tanggal_verifikasi.
 * Data pengajuan tidak dapat diubah/dihapus agar menjadi jejak audit; pengajuan
 * yang keliru cukup ditolak oleh Admin dengan catatan.
 */
class BarangKeluarController extends Controller
{
    public function __construct(private StokService $stok) {}

    public function index(Request $request): View
    {
        $verifikasi = $request->query('verifikasi');

        $barangKeluar = BarangKeluar::with(['barang', 'user', 'verifikator'])
            ->when(in_array($verifikasi, BarangKeluar::VERIFIKASI, true), fn ($q) => $q->where('verifikasi', $verifikasi))
            ->latest('tanggal')
            ->latest('id_keluar')
            ->paginate(10)
            ->withQueryString();

        return view('barang-keluar.index', compact('barangKeluar', 'verifikasi'));
    }

    public function show(BarangKeluar $barangKeluar): View
    {
        $barangKeluar->load(['barang.kategori', 'user', 'verifikator']);

        return view('barang-keluar.show', compact('barangKeluar'));
    }

    public function create(): View
    {
        return view('barang-keluar.form', [
            'barangKeluar' => new BarangKeluar(['tanggal' => now(), 'status_barang' => 'Baik']),
            'barang' => Barang::where('stok', '>', 0)->orderBy('kode_barang')->get(),
        ]);
    }

    public function store(BarangKeluarRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['id_user'] = $request->user()->id_user;
        $data['verifikasi'] = BarangKeluar::PENDING;

        BarangKeluar::create($data);

        return redirect()->route('barang-keluar.index')
            ->with('sukses', 'Permintaan barang keluar berhasil diajukan dan menunggu verifikasi Admin.');
    }

    /**
     * Admin menyetujui permintaan: stok barang dikurangi.
     */
    public function setujui(Request $request, BarangKeluar $barangKeluar): RedirectResponse
    {
        $request->validate(['catatan_verifikasi' => ['nullable', 'string', 'max:255']]);

        if (! $barangKeluar->isPending()) {
            return back()->with('gagal', 'Permintaan ini sudah diverifikasi sebelumnya.');
        }

        try {
            DB::transaction(function () use ($request, $barangKeluar) {
                $this->stok->kurangi(Barang::lockForUpdate()->findOrFail($barangKeluar->id_barang), $barangKeluar->jumlah);
                $this->simpanVerifikasi($request, $barangKeluar, BarangKeluar::DISETUJUI);
            });
        } catch (StokTidakCukupException $e) {
            return back()->with('gagal', $e->getMessage());
        }

        return redirect()->route('barang-keluar.show', $barangKeluar)
            ->with('sukses', 'Permintaan disetujui, stok barang telah dikurangi.');
    }

    /**
     * Admin menolak permintaan: stok tidak berubah, alasan wajib diisi.
     */
    public function tolak(Request $request, BarangKeluar $barangKeluar): RedirectResponse
    {
        $request->validate(
            ['catatan_verifikasi' => ['required', 'string', 'max:255']],
            ['catatan_verifikasi.required' => 'Alasan penolakan wajib diisi.']
        );

        if (! $barangKeluar->isPending()) {
            return back()->with('gagal', 'Permintaan ini sudah diverifikasi sebelumnya.');
        }

        $this->simpanVerifikasi($request, $barangKeluar, BarangKeluar::DITOLAK);

        return redirect()->route('barang-keluar.show', $barangKeluar)
            ->with('sukses', 'Permintaan barang keluar ditolak.');
    }

    /**
     * Mencatat hasil verifikasi beserta Admin yang melakukannya.
     */
    private function simpanVerifikasi(Request $request, BarangKeluar $barangKeluar, string $hasil): void
    {
        $barangKeluar->update([
            'verifikasi' => $hasil,
            'id_verifikator' => $request->user()->id_user,
            'tanggal_verifikasi' => now()->toDateString(),
            'catatan_verifikasi' => $request->input('catatan_verifikasi'),
        ]);
    }
}
