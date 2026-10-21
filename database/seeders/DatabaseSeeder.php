<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Kategori;
use App\Models\Lokasi;
use App\Models\Pengembalian;
use App\Models\StokBarang;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mengisi database dengan Data Sampel dari dokumen ujikom.
 *
 * Stok dicatat per lokasi dan kondisi (saran pembina), sehingga data sampel memuat:
 * barang di lebih dari satu gudang (Kursi Siswa), barang masuk yang tidak semuanya baik
 * (Bola Basket), pemindahan ke ruangan (Speaker ke Lab TKJ 3), peminjaman yang baru
 * dikembalikan sebagian, dan pengajuan pemusnahan barang rusak berat.
 * Stok akhir di bawah sudah sesuai dengan semua transaksi sampel.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Password di-hash otomatis oleh cast 'hashed' pada model User.
        User::create(['nama_user' => 'Ibrahim Risyad', 'username' => 'admin_ohim', 'password' => 'admin123', 'role' => User::ROLE_ADMIN]);
        $manager = User::create(['nama_user' => 'Marco Ivanos', 'username' => 'manager_marco', 'password' => '@admin123', 'role' => User::ROLE_MANAGER]);
        $operator = User::create(['nama_user' => 'Siti Aminah', 'username' => 'operator_siti', 'password' => 'operator123', 'role' => User::ROLE_OPERATOR]);

        foreach (['Alat Tulis', 'Furnitur', 'Kebersihan', 'Alat Olahraga', 'Elektronik'] as $nama) {
            Kategori::create(['nama_kategori' => $nama]);
        }

        // [nama_lokasi, jenis, keterangan]
        foreach ([
            ['Gudang Utama', Lokasi::GUDANG, 'Gedung A lantai 1'],
            ['Gudang 2', Lokasi::GUDANG, 'Gedung B lantai 1'],
            ['Gudang Olahraga', Lokasi::GUDANG, 'Samping lapangan'],
            ['Ruang Sarpras', Lokasi::GUDANG, 'Lemari alat tulis kantor'],
            ['Lab TKJ 3', Lokasi::RUANGAN, null],
            ['Lab JB 3', Lokasi::RUANGAN, null],
            ['Kelas 10 NKPI 2', Lokasi::RUANGAN, null],
        ] as [$nama, $jenis, $keterangan]) {
            Lokasi::create(['nama_lokasi' => $nama, 'jenis' => $jenis, 'keterangan' => $keterangan]);
        }
        $lokasi = Lokasi::pluck('id_lokasi', 'nama_lokasi');

        // [kode, id_kategori, nama, satuan, [lokasi => [Baik, Rusak Ringan, Rusak Berat]]]
        $daftarBarang = [
            // Data sampel dokumen
            ['AT-001', 1, 'Pulpen', 'pack', ['Ruang Sarpras' => [5, 0, 0]]],
            ['FR-001', 2, 'Meja Guru', 'buah', ['Gudang Utama' => [5, 2, 0]]],
            ['KN-001', 3, 'Sapu', 'buah', ['Gudang Utama' => [8, 0, 2]]],
            ['AO-001', 4, 'Bola Voli', 'buah', ['Gudang Olahraga' => [4, 0, 0]]],
            ['EK-001', 5, 'Proyektor Epson', 'unit', ['Gudang Utama' => [5, 1, 0]]],
            // Tambahan agar data sampel transaksi valid
            ['EK-002', 5, 'Laptop Asus', 'unit', ['Gudang Utama' => [9, 0, 0]]],
            ['AO-003', 4, 'Bola Basket', 'buah', ['Gudang Olahraga' => [6, 3, 0]]],
            ['KN-005', 3, 'Kain Pel', 'buah', ['Gudang Utama' => [23, 0, 0]]],
            ['EK-010', 5, 'Speaker Aktif', 'unit', ['Gudang Utama' => [2, 0, 0], 'Lab TKJ 3' => [4, 0, 0]]],
            ['AO-007', 4, 'Matras Senam', 'buah', ['Gudang Olahraga' => [20, 0, 0]]],
            ['FR-008', 2, 'Kursi Siswa', 'buah', ['Gudang Utama' => [110, 0, 0], 'Gudang 2' => [3, 7, 0]]],
        ];

        foreach ($daftarBarang as [$kode, $idKategori, $nama, $satuan, $stok]) {
            $barang = Barang::create([
                'kode_barang' => $kode, 'id_kategori' => $idKategori, 'nama_barang' => $nama,
                'satuan' => $satuan, 'stok' => array_sum(array_map('array_sum', $stok)),
            ]);
            foreach ($stok as $namaLokasi => $jumlah) {
                foreach (Barang::KONDISI as $i => $kondisi) {
                    if ($jumlah[$i] > 0) {
                        StokBarang::create([
                            'id_barang' => $barang->id_barang, 'id_lokasi' => $lokasi[$namaLokasi],
                            'kondisi' => $kondisi, 'jumlah' => $jumlah[$i],
                        ]);
                    }
                }
            }
        }

        $idBarang = Barang::pluck('id_barang', 'kode_barang');
        $rincian = fn (int $baik, int $ringan = 0, int $berat = 0) => [
            'jumlah' => $baik + $ringan + $berat,
            'jumlah_baik' => $baik, 'jumlah_rusak_ringan' => $ringan, 'jumlah_rusak_berat' => $berat,
        ];

        // Barang masuk dicatat Operator ke lokasi tertentu; tidak semua barang datang dalam kondisi baik.
        foreach ([
            ['EK-002', 'Gudang Utama', '2026-09-15', 'Dana BOS 2026', $rincian(9)],
            ['AO-003', 'Gudang Olahraga', '2026-09-30', 'Pembelian', $rincian(8, 2)],
            ['KN-005', 'Gudang Utama', '2026-10-01', 'Bantuan Dinas', $rincian(23)],
        ] as [$kode, $namaLokasi, $tanggal, $sumber, $jumlah]) {
            BarangMasuk::create([
                'id_barang' => $idBarang[$kode], 'id_user' => $operator->id_user, 'id_lokasi' => $lokasi[$namaLokasi],
                'tanggal' => $tanggal, 'sumber_barang' => $sumber, ...$jumlah,
            ]);
        }

        // Barang keluar diajukan Operator dan diverifikasi Manager.
        // Data sampel belum memiliki foto bukti; foto wajib hanya untuk pengajuan baru lewat aplikasi.
        $keluar = fn (array $data) => BarangKeluar::create(['id_user' => $operator->id_user, ...$data]);
        $disetujui = fn (string $tanggal, ?string $catatan = null) => [
            'verifikasi' => BarangKeluar::DISETUJUI, 'id_verifikator' => $manager->id_user,
            'tanggal_verifikasi' => $tanggal, 'catatan_verifikasi' => $catatan,
        ];

        $keluar([
            'jenis_keluar' => BarangKeluar::PEMINDAHAN, 'id_barang' => $idBarang['EK-010'], 'tanggal' => '2026-10-05',
            'id_lokasi_asal' => $lokasi['Gudang Utama'], 'id_lokasi_tujuan' => $lokasi['Lab TKJ 3'],
            'pemohon' => 'Budi Santoso (Kaprog TKJ)', 'tujuan' => 'Praktik audio Lab TKJ 3', ...$rincian(4),
            ...$disetujui('2026-10-05'),
        ]);
        $keluar([
            'jenis_keluar' => BarangKeluar::PEMINJAMAN, 'id_barang' => $idBarang['AO-007'], 'tanggal' => '2026-10-06',
            'id_lokasi_asal' => $lokasi['Gudang Olahraga'], 'tanggal_kembali' => '2026-10-20',
            'pemohon' => 'Sari Wulandari (Guru PJOK)', 'tujuan' => 'Latihan senam kelas XI', ...$rincian(10),
            'verifikasi' => BarangKeluar::DITOLAK, 'id_verifikator' => $manager->id_user, 'tanggal_verifikasi' => '2026-10-06',
            'catatan_verifikasi' => 'Matras sedang dipakai untuk persiapan lomba senam.',
        ]);
        $pinjamBasket = $keluar([
            'jenis_keluar' => BarangKeluar::PEMINJAMAN, 'id_barang' => $idBarang['AO-003'], 'tanggal' => '2026-10-07',
            'id_lokasi_asal' => $lokasi['Gudang Olahraga'], 'tanggal_kembali' => '2026-10-14',
            'pemohon' => 'Rudi Hartono (Pembina Basket)', 'tujuan' => 'Latihan ekstrakurikuler basket', ...$rincian(4),
            ...$disetujui('2026-10-07'),
        ]);
        $keluar([
            'jenis_keluar' => BarangKeluar::PEMINDAHAN, 'id_barang' => $idBarang['FR-008'], 'tanggal' => '2026-10-08',
            'id_lokasi_asal' => $lokasi['Gudang Utama'], 'id_lokasi_tujuan' => $lokasi['Kelas 10 NKPI 2'],
            'pemohon' => 'Andi Pratama (Wali Kelas 10 NKPI 2)', 'tujuan' => 'Kursi kelas baru', ...$rincian(100),
            'verifikasi' => BarangKeluar::PENDING,
        ]);
        $keluar([
            'jenis_keluar' => BarangKeluar::PEMUSNAHAN, 'id_barang' => $idBarang['KN-001'], 'tanggal' => '2026-10-09',
            'id_lokasi_asal' => $lokasi['Gudang Utama'],
            'pemohon' => 'Tim Sarpras', 'tujuan' => 'Gagang patah, tidak dapat diperbaiki', ...$rincian(0, 0, 2),
            'verifikasi' => BarangKeluar::PENDING,
        ]);

        // Bola basket baru kembali 3 dari 4; satu di antaranya sudah rusak ringan. Sisa 1 masih dipinjam.
        Pengembalian::create([
            'id_keluar' => $pinjamBasket->id_keluar, 'id_user' => $operator->id_user, 'id_lokasi' => $lokasi['Gudang Olahraga'],
            'tanggal' => '2026-10-10', 'catatan' => '1 bola kempes, perlu dipompa ulang', ...$rincian(2, 1),
        ]);
    }
}
