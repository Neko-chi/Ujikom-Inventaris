<?php

namespace App\Http\Controllers;

use App\Http\Requests\LokasiRequest;
use App\Models\Lokasi;
use App\Models\StokBarang;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Kelola lokasi penyimpanan barang (khusus Admin): gudang dan ruangan.
 */
class LokasiController extends Controller
{
    public function index(): View
    {
        $lokasi = Lokasi::withSum('stokBarang as total_stok', 'jumlah')
            ->orderBy('jenis')
            ->orderBy('nama_lokasi')
            ->get();

        // Banyaknya jenis barang yang ada di tiap lokasi: [id_lokasi => jumlah jenis]
        $jenisBarang = StokBarang::where('jumlah', '>', 0)
            ->selectRaw('id_lokasi, COUNT(DISTINCT id_barang) AS jumlah_jenis')
            ->groupBy('id_lokasi')
            ->pluck('jumlah_jenis', 'id_lokasi');

        return view('lokasi.index', compact('lokasi', 'jenisBarang'));
    }

    public function create(): View
    {
        return view('lokasi.form', ['lokasi' => new Lokasi(['jenis' => Lokasi::GUDANG])]);
    }

    public function store(LokasiRequest $request): RedirectResponse
    {
        Lokasi::create($request->validated());

        return redirect()->route('lokasi.index')->with('sukses', 'Lokasi berhasil ditambahkan.');
    }

    public function edit(Lokasi $lokasi): View
    {
        return view('lokasi.form', compact('lokasi'));
    }

    public function update(LokasiRequest $request, Lokasi $lokasi): RedirectResponse
    {
        $lokasi->update($request->validated());

        return redirect()->route('lokasi.index')->with('sukses', 'Lokasi berhasil diperbarui.');
    }

    public function destroy(Lokasi $lokasi): RedirectResponse
    {
        // Lokasi yang masih berisi barang atau tercatat di transaksi tidak boleh dihapus (menjaga riwayat).
        if ($lokasi->sudahDipakai()) {
            return back()->with('gagal', "Lokasi {$lokasi->nama_lokasi} masih berisi barang atau tercatat di riwayat transaksi.");
        }

        $lokasi->delete();

        return redirect()->route('lokasi.index')->with('sukses', 'Lokasi berhasil dihapus.');
    }
}
