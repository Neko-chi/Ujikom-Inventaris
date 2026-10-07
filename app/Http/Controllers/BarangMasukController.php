<?php

namespace App\Http\Controllers;

use App\Exceptions\StokTidakCukupException;
use App\Http\Requests\BarangMasukRequest;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Transaksi barang masuk. Setiap barang masuk langsung menambah stok.
 * Lihat daftar: Admin & Operator. Catat: Operator.
 * Ubah/hapus (koreksi kesalahan input): Admin, sebagai bentuk pengawasan.
 */
class BarangMasukController extends Controller
{
    public function __construct(private StokService $stok) {}

    public function index(Request $request): View
    {
        $dari = $request->query('dari');
        $sampai = $request->query('sampai');

        $barangMasuk = BarangMasuk::with(['barang', 'user'])
            ->when($dari, fn ($q, $tgl) => $q->whereDate('tanggal', '>=', $tgl))
            ->when($sampai, fn ($q, $tgl) => $q->whereDate('tanggal', '<=', $tgl))
            ->latest('tanggal')
            ->latest('id_masuk')
            ->paginate(10)
            ->withQueryString();

        return view('barang-masuk.index', compact('barangMasuk', 'dari', 'sampai'));
    }

    public function create(): View
    {
        return view('barang-masuk.form', [
            'barangMasuk' => new BarangMasuk(['tanggal' => now(), 'status_barang' => 'Baik']),
            'barang' => Barang::orderBy('kode_barang')->get(),
        ]);
    }

    public function store(BarangMasukRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request) {
            $data = $request->validated();
            $data['id_user'] = $request->user()->id_user;

            $masuk = BarangMasuk::create($data);
            $this->stok->tambah(Barang::lockForUpdate()->findOrFail($masuk->id_barang), $masuk->jumlah);
        });

        return redirect()->route('barang-masuk.index')->with('sukses', 'Barang masuk berhasil dicatat dan stok bertambah.');
    }

    public function edit(BarangMasuk $barangMasuk): View
    {
        return view('barang-masuk.form', [
            'barangMasuk' => $barangMasuk,
            'barang' => Barang::orderBy('kode_barang')->get(),
        ]);
    }

    public function update(BarangMasukRequest $request, BarangMasuk $barangMasuk): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $barangMasuk) {
                $idBarangLama = $barangMasuk->id_barang;
                $jumlahLama = $barangMasuk->jumlah;

                $barangMasuk->update($request->validated());

                if ($barangMasuk->id_barang === $idBarangLama) {
                    // Barang sama: cukup sesuaikan stok sebesar selisih jumlah.
                    $selisih = $barangMasuk->jumlah - $jumlahLama;
                    $barang = Barang::lockForUpdate()->findOrFail($idBarangLama);

                    if ($selisih > 0) {
                        $this->stok->tambah($barang, $selisih);
                    } elseif ($selisih < 0) {
                        $this->stok->kurangi($barang, abs($selisih));
                    }
                } else {
                    // Barang diganti: kembalikan stok barang lama, tambahkan ke barang baru.
                    $this->stok->kurangi(Barang::lockForUpdate()->findOrFail($idBarangLama), $jumlahLama);
                    $this->stok->tambah(Barang::lockForUpdate()->findOrFail($barangMasuk->id_barang), $barangMasuk->jumlah);
                }
            });
        } catch (StokTidakCukupException $e) {
            return back()->withInput()->with('gagal', 'Data tidak dapat diubah karena stok barang sudah terpakai. '.$e->getMessage());
        }

        return redirect()->route('barang-masuk.index')->with('sukses', 'Data barang masuk berhasil diperbarui.');
    }

    public function destroy(BarangMasuk $barangMasuk): RedirectResponse
    {
        try {
            DB::transaction(function () use ($barangMasuk) {
                $this->stok->kurangi(Barang::lockForUpdate()->findOrFail($barangMasuk->id_barang), $barangMasuk->jumlah);
                $barangMasuk->delete();
            });
        } catch (StokTidakCukupException $e) {
            return back()->with('gagal', 'Data tidak dapat dihapus karena stok barang sudah terpakai. '.$e->getMessage());
        }

        return redirect()->route('barang-masuk.index')->with('sukses', 'Data barang masuk dihapus dan stok dikembalikan.');
    }
}
