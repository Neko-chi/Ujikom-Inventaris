<?php

namespace App\Http\Controllers;

use App\Exceptions\StokTidakCukupException;
use App\Http\Requests\BarangMasukRequest;
use App\Models\Barang;
use App\Models\BarangMasuk;
use App\Models\Lokasi;
use App\Services\GambarService;
use App\Services\StokService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Throwable;

/**
 * Transaksi barang masuk. Setiap barang masuk langsung menambah stok di lokasi tujuan,
 * sesuai jumlah per kondisi (Baik / Rusak Ringan / Rusak Berat).
 * Lihat daftar: semua role. Catat: Operator (foto bukti opsional).
 * Ubah/hapus (koreksi kesalahan input): Admin.
 *
 * File foto disimpan sebelum transaksi database. Bila transaksi gagal, file baru
 * dihapus kembali agar tidak ada file yatim; file lama baru dihapus setelah berhasil.
 */
class BarangMasukController extends Controller
{
    public function __construct(
        private StokService $stok,
        private GambarService $gambar,
    ) {}

    public function index(Request $request): View
    {
        $dari = $request->query('dari');
        $sampai = $request->query('sampai');

        $barangMasuk = BarangMasuk::with(['barang', 'user', 'lokasi'])
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
            'barangMasuk' => new BarangMasuk(['tanggal' => now()]),
            'barang' => Barang::orderBy('kode_barang')->get(),
            'lokasi' => $this->daftarLokasi(),
        ]);
    }

    public function store(BarangMasukRequest $request): RedirectResponse
    {
        $data = $request->safe()->only(['id_barang', 'id_lokasi', 'tanggal', 'sumber_barang']);
        $data['id_user'] = $request->user()->id_user;
        $data['foto'] = $request->hasFile('foto') ? $this->gambar->simpan($request->file('foto'), 'barang-masuk') : null;
        $rincian = BarangMasuk::rincianDariInput($request->validated());

        try {
            DB::transaction(function () use ($data, $rincian) {
                $masuk = new BarangMasuk($data);
                $masuk->isiRincian($rincian)->save();
                $this->stok->tambahRincian(Barang::lockForUpdate()->findOrFail($masuk->id_barang), $masuk->id_lokasi, $rincian);
            });
        } catch (Throwable $e) {
            $this->gambar->hapus($data['foto']);
            throw $e;
        }

        return redirect()->route('barang-masuk.index')->with('sukses', 'Barang masuk berhasil dicatat dan stok bertambah.');
    }

    public function edit(BarangMasuk $barangMasuk): View
    {
        return view('barang-masuk.form', [
            'barangMasuk' => $barangMasuk,
            'barang' => Barang::orderBy('kode_barang')->get(),
            'lokasi' => $this->daftarLokasi(),
        ]);
    }

    /**
     * Koreksi oleh Admin: stok dari data lama dikembalikan dulu (di lokasi & kondisi lama),
     * lalu stok dari data baru ditambahkan. Bila stok lama sudah terpakai, koreksi ditolak.
     */
    public function update(BarangMasukRequest $request, BarangMasuk $barangMasuk): RedirectResponse
    {
        $data = $request->safe()->only(['id_barang', 'id_lokasi', 'tanggal', 'sumber_barang']);
        $rincianBaru = BarangMasuk::rincianDariInput($request->validated());
        $fotoLama = $barangMasuk->foto;
        $fotoBaru = $request->hasFile('foto') ? $this->gambar->simpan($request->file('foto'), 'barang-masuk') : null;
        if ($fotoBaru) {
            $data['foto'] = $fotoBaru;
        }

        try {
            DB::transaction(function () use ($data, $rincianBaru, $barangMasuk) {
                $this->stok->kurangiRincian(
                    Barang::lockForUpdate()->findOrFail($barangMasuk->id_barang),
                    $barangMasuk->id_lokasi,
                    $barangMasuk->rincian()
                );

                $barangMasuk->fill($data)->isiRincian($rincianBaru)->save();

                $this->stok->tambahRincian(
                    Barang::lockForUpdate()->findOrFail($barangMasuk->id_barang),
                    $barangMasuk->id_lokasi,
                    $rincianBaru
                );
            });
        } catch (StokTidakCukupException $e) {
            $this->gambar->hapus($fotoBaru);

            return back()->withInput()->with('gagal', 'Data tidak dapat diubah karena stok barang sudah terpakai. '.$e->getMessage());
        } catch (Throwable $e) {
            $this->gambar->hapus($fotoBaru);
            throw $e;
        }

        if ($fotoBaru) {
            $this->gambar->hapus($fotoLama);
        }

        return redirect()->route('barang-masuk.index')->with('sukses', 'Data barang masuk berhasil diperbarui.');
    }

    public function destroy(BarangMasuk $barangMasuk): RedirectResponse
    {
        try {
            DB::transaction(function () use ($barangMasuk) {
                $this->stok->kurangiRincian(
                    Barang::lockForUpdate()->findOrFail($barangMasuk->id_barang),
                    $barangMasuk->id_lokasi,
                    $barangMasuk->rincian()
                );
                $barangMasuk->delete();
            });
        } catch (StokTidakCukupException $e) {
            return back()->with('gagal', 'Data tidak dapat dihapus karena stok barang sudah terpakai. '.$e->getMessage());
        }

        $this->gambar->hapus($barangMasuk->foto);

        return redirect()->route('barang-masuk.index')->with('sukses', 'Data barang masuk dihapus dan stok dikembalikan.');
    }

    private function daftarLokasi()
    {
        return Lokasi::orderBy('jenis')->orderBy('nama_lokasi')->get();
    }
}
