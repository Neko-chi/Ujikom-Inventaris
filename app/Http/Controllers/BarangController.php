<?php

namespace App\Http\Controllers;

use App\Http\Requests\BarangRequest;
use App\Models\Barang;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Data master barang.
 * Lihat daftar & detail: Admin & Operator. Tambah: Admin & Operator
 * (Operator perlu mendaftarkan barang baru sebelum mencatat barang masuk).
 * Ubah/hapus: Admin.
 */
class BarangController extends Controller
{
    public function index(Request $request): View
    {
        $cari = $request->query('cari');
        $idKategori = $request->query('kategori');

        $barang = Barang::with('kategori')
            ->when($cari, function ($query, $cari) {
                // whereLike tidak membedakan huruf besar/kecil di MySQL maupun PostgreSQL.
                $query->where(function ($q) use ($cari) {
                    $q->whereLike('nama_barang', "%{$cari}%")
                        ->orWhereLike('kode_barang', "%{$cari}%");
                });
            })
            ->when($idKategori, fn ($query, $id) => $query->where('id_kategori', $id))
            ->orderBy('kode_barang')
            ->paginate(10)
            ->withQueryString();

        $kategori = Kategori::orderBy('nama_kategori')->get();

        return view('barang.index', compact('barang', 'kategori', 'cari', 'idKategori'));
    }

    public function create(): View
    {
        return view('barang.form', [
            'barang' => new Barang(['status_barang' => 'Baik', 'stok' => 0]),
            'kategori' => Kategori::orderBy('nama_kategori')->get(),
        ]);
    }

    public function store(BarangRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['kode_barang'] = Barang::generateKode(Kategori::findOrFail($data['id_kategori']));

        $barang = Barang::create($data);

        return redirect()->route('barang.index')
            ->with('sukses', "Barang {$barang->nama_barang} berhasil ditambahkan dengan kode {$barang->kode_barang}.");
    }

    public function show(Barang $barang): View
    {
        $barang->load([
            'kategori',
            'barangMasuk' => fn ($q) => $q->with('user')->latest('tanggal'),
            'barangKeluar' => fn ($q) => $q->with('user')->latest('tanggal'),
        ]);

        return view('barang.show', compact('barang'));
    }

    public function edit(Barang $barang): View
    {
        return view('barang.form', [
            'barang' => $barang,
            'kategori' => Kategori::orderBy('nama_kategori')->get(),
        ]);
    }

    public function update(BarangRequest $request, Barang $barang): RedirectResponse
    {
        $data = $request->validated();

        // Jika kategori diganti, kode barang dibuat ulang agar prefix tetap sesuai.
        if ((int) $data['id_kategori'] !== $barang->id_kategori) {
            $data['kode_barang'] = Barang::generateKode(Kategori::findOrFail($data['id_kategori']));
        }

        $barang->update($data);

        return redirect()->route('barang.index')->with('sukses', 'Data barang berhasil diperbarui.');
    }

    public function destroy(Barang $barang): RedirectResponse
    {
        // Barang yang sudah punya riwayat transaksi tidak boleh dihapus agar laporan tetap utuh.
        if ($barang->barangMasuk()->exists() || $barang->barangKeluar()->exists()) {
            return back()->with('gagal', "Barang {$barang->nama_barang} sudah memiliki riwayat transaksi sehingga tidak dapat dihapus.");
        }

        $barang->delete();

        return redirect()->route('barang.index')->with('sukses', 'Barang berhasil dihapus.');
    }
}
