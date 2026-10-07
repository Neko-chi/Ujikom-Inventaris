<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Models\BarangMasuk;
use App\Models\Kategori;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Mengisi database dengan Data Sampel dari dokumen ujikom.
 *
 * Catatan revisi: barang EK-002, AO-003, KN-005, EK-010, AO-007 dan FR-008
 * ditambahkan agar foreign key data transaksi valid; tanggal transaksi
 * diurutkan (barang masuk lebih dulu dari barang keluar).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Password di-hash otomatis oleh cast 'hashed' pada model User.
        // id_user 1 = Operator (petugas gudang), id_user 2 = Admin (pengawas/verifikator).
        User::create(['nama_user' => 'Ibrahim Risyad', 'username' => 'operator_ohim', 'password' => 'operator123', 'role' => User::ROLE_OPERATOR]);
        User::create(['nama_user' => 'Marco Ivanos', 'username' => 'admin_marco', 'password' => '@admin123', 'role' => User::ROLE_ADMIN]);

        foreach (['Alat Tulis', 'Furnitur', 'Kebersihan', 'Alat Olahraga', 'Elektronik'] as $nama) {
            Kategori::create(['nama_kategori' => $nama]);
        }

        // [kode_barang, id_kategori, nama_barang, stok, satuan, lokasi]
        $daftarBarang = [
            // Data sampel dokumen
            ['AT-001', 1, 'Pulpen', 5, 'pack', 'sarpras'],
            ['FR-001', 2, 'Meja Guru', 7, 'buah', 'gudang utama'],
            ['KN-001', 3, 'Sapu', 10, 'buah', 'gudang utama'],
            ['AO-001', 4, 'Bola Voli', 4, 'buah', 'gudang olahraga'],
            ['EK-001', 5, 'Proyektor Epson', 6, 'unit', 'gudang utama'],
            // Tambahan agar data sampel transaksi valid
            ['EK-002', 5, 'Laptop Asus', 9, 'unit', 'gudang utama'],
            ['AO-003', 4, 'Bola Basket', 10, 'buah', 'gudang olahraga'],
            ['KN-005', 3, 'Kain Pel', 23, 'buah', 'gudang utama'],
            ['EK-010', 5, 'Speaker Aktif', 6, 'unit', 'gudang utama'],
            ['AO-007', 4, 'Matras Senam', 20, 'buah', 'gudang olahraga'],
            ['FR-008', 2, 'Kursi Siswa', 120, 'buah', 'gudang utama'],
        ];

        foreach ($daftarBarang as [$kode, $idKategori, $nama, $stok, $satuan, $lokasi]) {
            Barang::create([
                'kode_barang' => $kode,
                'id_kategori' => $idKategori,
                'nama_barang' => $nama,
                'stok' => $stok,
                'satuan' => $satuan,
                'lokasi' => $lokasi,
                'status_barang' => 'Baik',
            ]);
        }

        $idBarang = Barang::pluck('id_barang', 'kode_barang');

        // Transaksi dicatat oleh Operator (id_user 1) dan diverifikasi Admin (id_user 2).
        BarangMasuk::insert([
            ['id_barang' => $idBarang['EK-002'], 'id_user' => 1, 'tanggal' => '2026-09-15', 'jumlah' => 9, 'sumber_barang' => 'Dana BOS 2026', 'status_barang' => 'Baik'],
            ['id_barang' => $idBarang['AO-003'], 'id_user' => 1, 'tanggal' => '2026-09-30', 'jumlah' => 10, 'sumber_barang' => 'Pembelian', 'status_barang' => 'Baik'],
            ['id_barang' => $idBarang['KN-005'], 'id_user' => 1, 'tanggal' => '2026-10-01', 'jumlah' => 23, 'sumber_barang' => 'Bantuan Dinas', 'status_barang' => 'Baik'],
        ]);

        BarangKeluar::insert([
            [
                'id_barang' => $idBarang['EK-010'], 'id_user' => 1, 'tanggal' => '2026-10-05', 'jumlah' => 4,
                'pemohon' => 'Budi Santoso (Kaprog TKJ)', 'tujuan' => 'Lab TKJ3', 'status_barang' => 'Baik',
                'verifikasi' => BarangKeluar::DISETUJUI, 'id_verifikator' => 2, 'tanggal_verifikasi' => '2026-10-05', 'catatan_verifikasi' => null,
            ],
            [
                'id_barang' => $idBarang['AO-007'], 'id_user' => 1, 'tanggal' => '2026-10-06', 'jumlah' => 10,
                'pemohon' => 'Sari Wulandari (Guru PJOK)', 'tujuan' => 'Lab JB 3', 'status_barang' => 'Baik',
                'verifikasi' => BarangKeluar::DITOLAK, 'id_verifikator' => 2, 'tanggal_verifikasi' => '2026-10-06', 'catatan_verifikasi' => 'Matras sedang dipakai untuk persiapan lomba senam.',
            ],
            [
                'id_barang' => $idBarang['FR-008'], 'id_user' => 1, 'tanggal' => '2026-10-08', 'jumlah' => 100,
                'pemohon' => 'Andi Pratama (Wali Kelas 10 NKPI 2)', 'tujuan' => 'Kelas 10 NKPI 2', 'status_barang' => 'Baik',
                'verifikasi' => BarangKeluar::PENDING, 'id_verifikator' => null, 'tanggal_verifikasi' => null, 'catatan_verifikasi' => null,
            ],
        ]);
    }
}
