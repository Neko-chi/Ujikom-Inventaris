<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Kategori;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Halaman ringkasan kondisi gudang.
 */
class DashboardController extends Controller
{
    public function index(): View
    {
        $ringkasan = [
            'jenis_barang' => Barang::count(),
            'total_stok' => (int) Barang::sum('stok'),
            'total_masuk' => (int) BarangMasuk::sum('jumlah'),
            'menunggu_verifikasi' => BarangKeluar::where('verifikasi', BarangKeluar::PENDING)->count(),
        ];

        // Total stok per kategori untuk grafik batang.
        $stokPerKategori = Kategori::withCount('barang')
            ->withSum('barang as total_stok', 'stok')
            ->orderByDesc('total_stok')
            ->get();

        // Jumlah permintaan per status verifikasi, status tanpa data diisi 0.
        $jumlahPerStatus = BarangKeluar::selectRaw('verifikasi, COUNT(*) AS jumlah')
            ->groupBy('verifikasi')
            ->pluck('jumlah', 'verifikasi');
        $statusKeluar = collect(BarangKeluar::VERIFIKASI)
            ->mapWithKeys(fn (string $status) => [$status => (int) ($jumlahPerStatus[$status] ?? 0)]);

        $stokMenipis = Barang::with('kategori')
            ->where('stok', '<=', Barang::BATAS_STOK_MENIPIS)
            ->orderBy('stok')
            ->get();

        return view('dashboard', [
            'ringkasan' => $ringkasan,
            'stokPerKategori' => $stokPerKategori,
            'statusKeluar' => $statusKeluar,
            'stokMenipis' => $stokMenipis,
            'aktivitas' => $this->aktivitasTerbaru(),
            'salam' => $this->salam((int) now()->format('G')),
        ]);
    }

    /**
     * Menggabungkan barang masuk dan barang keluar terbaru menjadi satu
     * daftar aktivitas yang diurutkan dari yang paling baru.
     */
    private function aktivitasTerbaru(int $batas = 6): Collection
    {
        $masuk = BarangMasuk::with('barang')->latest('tanggal')->latest('id_masuk')->take($batas)->get()
            ->map(fn (BarangMasuk $m) => [
                'jenis' => 'masuk',
                'tanggal' => $m->tanggal,
                'urutan' => $m->id_masuk,
                'barang' => $m->barang->nama_barang,
                'keterangan' => $m->sumber_barang,
                'jumlah' => $m->jumlah,
                'satuan' => $m->barang->satuan,
                'status' => null,
                'url' => null,
            ]);

        $keluar = BarangKeluar::with('barang')->latest('tanggal')->latest('id_keluar')->take($batas)->get()
            ->map(fn (BarangKeluar $k) => [
                'jenis' => 'keluar',
                'tanggal' => $k->tanggal,
                'urutan' => $k->id_keluar,
                'barang' => $k->barang->nama_barang,
                'keterangan' => $k->pemohon.' · '.$k->tujuan,
                'jumlah' => $k->jumlah,
                'satuan' => $k->barang->satuan,
                'status' => $k->verifikasi,
                'url' => route('barang-keluar.show', $k),
            ]);

        return $masuk->concat($keluar)
            ->sortByDesc(fn (array $a) => $a['tanggal']->format('Ymd').sprintf('%06d', $a['urutan']))
            ->take($batas)
            ->values();
    }

    /**
     * Salam sesuai jam (0-23).
     */
    private function salam(int $jam): string
    {
        if ($jam < 11) {
            return 'Selamat pagi';
        } elseif ($jam < 15) {
            return 'Selamat siang';
        } elseif ($jam < 18) {
            return 'Selamat sore';
        }

        return 'Selamat malam';
    }
}
