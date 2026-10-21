<?php

namespace App\Http\Controllers;

use App\Http\Requests\PengembalianRequest;
use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\Pengembalian;
use App\Services\GambarService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Pengembalian barang pinjaman (dicatat Operator).
 * Stok di lokasi penyimpanan bertambah sesuai kondisi barang saat dikembalikan.
 * Peminjaman bisa dikembalikan beberapa kali sampai lunas.
 */
class PengembalianController extends Controller
{
    public function __construct(
        private StokService $stok,
        private GambarService $gambar,
    ) {}

    public function store(PengembalianRequest $request, BarangKeluar $barangKeluar): RedirectResponse
    {
        if ($barangKeluar->sisaPinjaman() < 1) {
            return back()->with('gagal', 'Tidak ada barang pinjaman yang perlu dikembalikan.');
        }

        $rincian = Pengembalian::rincianDariInput($request->validated());
        $foto = $request->hasFile('foto') ? $this->gambar->simpan($request->file('foto'), 'pengembalian') : null;

        try {
            $berhasil = DB::transaction(function () use ($request, $barangKeluar, $rincian, $foto) {
                // Kunci peminjaman lalu cek ulang sisa agar dua pencatatan bersamaan tidak melebihi jumlah pinjaman
                $pinjaman = BarangKeluar::lockForUpdate()->findOrFail($barangKeluar->id_keluar);
                if (array_sum($rincian) > $pinjaman->sisaPinjaman()) {
                    return false;
                }

                $kembali = new Pengembalian([
                    'id_keluar' => $pinjaman->id_keluar,
                    'id_user' => $request->user()->id_user,
                    'id_lokasi' => $request->validated('id_lokasi'),
                    'tanggal' => $request->validated('tanggal'),
                    'catatan' => $request->validated('catatan'),
                    'foto' => $foto,
                ]);
                $kembali->isiRincian($rincian)->save();

                $this->stok->tambahRincian(Barang::lockForUpdate()->findOrFail($pinjaman->id_barang), $kembali->id_lokasi, $rincian);

                return true;
            });
        } catch (Throwable $e) {
            $this->gambar->hapus($foto);
            throw $e;
        }

        if (! $berhasil) {
            $this->gambar->hapus($foto);

            return back()->with('gagal', 'Jumlah yang dikembalikan melebihi sisa pinjaman.');
        }

        return redirect()->route('barang-keluar.show', $barangKeluar)
            ->with('sukses', 'Pengembalian berhasil dicatat dan stok bertambah.');
    }
}
