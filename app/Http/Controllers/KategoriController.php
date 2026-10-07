<?php

namespace App\Http\Controllers;

use App\Http\Requests\KategoriRequest;
use App\Models\Kategori;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * CRUD data kategori (khusus Admin).
 */
class KategoriController extends Controller
{
    public function index(): View
    {
        $kategori = Kategori::withCount('barang')->orderBy('id_kategori')->get();

        return view('kategori.index', compact('kategori'));
    }

    public function create(): View
    {
        return view('kategori.form', ['kategori' => new Kategori]);
    }

    public function store(KategoriRequest $request): RedirectResponse
    {
        Kategori::create($request->validated());

        return redirect()->route('kategori.index')->with('sukses', 'Kategori berhasil ditambahkan.');
    }

    public function edit(Kategori $kategori): View
    {
        return view('kategori.form', compact('kategori'));
    }

    public function update(KategoriRequest $request, Kategori $kategori): RedirectResponse
    {
        $kategori->update($request->validated());

        return redirect()->route('kategori.index')->with('sukses', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Kategori $kategori): RedirectResponse
    {
        // Kategori yang masih dipakai barang tidak boleh dihapus (menjaga relasi).
        if ($kategori->barang()->exists()) {
            return back()->with('gagal', "Kategori {$kategori->nama_kategori} masih digunakan oleh data barang.");
        }

        $kategori->delete();

        return redirect()->route('kategori.index')->with('sukses', 'Kategori berhasil dihapus.');
    }
}
