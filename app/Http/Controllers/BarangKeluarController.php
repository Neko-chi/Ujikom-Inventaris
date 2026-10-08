<?php

namespace App\Http\Controllers;

use App\Exceptions\StokTidakCukupException;
use App\Http\Requests\BarangKeluarRequest;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Services\GambarService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Transaksi barang keluar.
 *
 * Alur (pemisahan tugas):
 *  1. Operator mengajukan permintaan + foto bukti (wajib) -> verifikasi = Pending, stok belum berubah.
 *  2. Manager memverifikasi -> Disetujui (stok berkurang) atau Ditolak (wajib alasan).
 * Siapa dan kapan verifikasi dilakukan dicatat di id_verifikator & tanggal_verifikasi.
 * Data pengajuan tidak dapat diubah/dihapus agar menjadi jejak audit; pengajuan
 * yang keliru cukup ditolak oleh Manager dengan catatan.
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
        $data = $request->safe()->except('foto');
        $data['id_user'] = $request->user()->id_user;
        $data['verifikasi'] = BarangKeluar::PENDING;
        $data['foto'] = $this->gambar->simpan($request->file('foto'), 'barang-keluar');

        try {
            BarangKeluar::create($data);
        } catch (Throwable $e) {
            $this->gambar->hapus($data['foto']);
            throw $e;
        }

        return redirect()->route('barang-keluar.index')
            ->with('sukses', 'Permintaan barang keluar berhasil diajukan dan menunggu verifikasi Manager.');
    }

    /**
     * Manager menyetujui permintaan: stok barang dikurangi.
     */
    public function setujui(Request $request, BarangKeluar $barangKeluar): RedirectResponse
    {
        $request->validate(['catatan_verifikasi' => ['nullable', 'string', 'max:255']]);

        try {
            $berhasil = DB::transaction(function () use ($request, $barangKeluar) {
                $permintaan = $this->kunciPermintaanPending($barangKeluar);
                if ($permintaan === null) {
                    return false;
                }

                $this->stok->kurangi(Barang::lockForUpdate()->findOrFail($permintaan->id_barang), $permintaan->jumlah);
                $this->simpanVerifikasi($request, $permintaan, BarangKeluar::DISETUJUI);

                return true;
            });
        } catch (StokTidakCukupException $e) {
            return back()->with('gagal', $e->getMessage());
        }

        if (! $berhasil) {
            return back()->with('gagal', 'Permintaan ini sudah diverifikasi sebelumnya.');
        }

        return redirect()->route('barang-keluar.show', $barangKeluar)
            ->with('sukses', 'Permintaan disetujui, stok barang telah dikurangi.');
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

            $this->simpanVerifikasi($request, $permintaan, BarangKeluar::DITOLAK);

            return true;
        });

        if (! $berhasil) {
            return back()->with('gagal', 'Permintaan ini sudah diverifikasi sebelumnya.');
        }

        return redirect()->route('barang-keluar.show', $barangKeluar)
            ->with('sukses', 'Permintaan barang keluar ditolak.');
    }

    /**
     * Mengunci baris permintaan (SELECT ... FOR UPDATE) lalu memastikan statusnya masih Pending.
     * Harus dipanggil di dalam DB::transaction(). Jika dua Manager memverifikasi bersamaan,
     * Manager kedua menunggu sampai transaksi pertama selesai, lalu mendapati status
     * sudah bukan Pending sehingga stok tidak berkurang dua kali.
     */
    private function kunciPermintaanPending(BarangKeluar $barangKeluar): ?BarangKeluar
    {
        $permintaan = BarangKeluar::lockForUpdate()->findOrFail($barangKeluar->id_keluar);

        return $permintaan->isPending() ? $permintaan : null;
    }

    /**
     * Mencatat hasil verifikasi beserta Manager yang melakukannya.
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
