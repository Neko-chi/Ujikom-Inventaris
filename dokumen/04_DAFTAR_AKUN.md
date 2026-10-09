# Daftar Akun dan Password

| Keterangan | Isi |
|---|---|
| Aplikasi | Inventaris Gudang Sekolah |
| Berlaku untuk | Lokal (XAMPP) dan online (Vercel), setelah data sampel diisi dengan `php artisan migrate:fresh --seed` |

## 1. Akun Login Aplikasi

| No | Nama | Username | Password | Role | Tugas |
|---|---|---|---|---|---|
| 1 | Ibrahim Risyad | `admin_ohim` | `admin123` | Admin | Mengelola pengguna, kategori, data barang; mengoreksi barang masuk |
| 2 | Marco Ivanos | `manager_marco` | `@admin123` | Manager | Menyetujui / menolak barang keluar; melihat semua data |
| 3 | Siti Aminah | `operator_siti` | `operator123` | Operator | Mendaftarkan barang, mencatat barang masuk, mengajukan barang keluar |

Catatan:

- Username dan password peka huruf besar/kecil. Perhatikan tanda `@` pada password Manager.
- Di database, password tersimpan sebagai **hash bcrypt** (awalan `$2y$12$...`), bukan teks asli. Hash tidak dapat dikembalikan menjadi password asli.
- Akun lama dari versi sebelumnya (`admin_marco`, `operator_ohim`) **sudah tidak berlaku**.

## 2. Hak Akses Ringkas

| Menu / Aksi | Admin | Operator | Manager |
|---|---|---|---|
| Dashboard, Profil (klik foto profil) | Ya | Ya | Ya |
| Pengguna, Kategori | Ya | Tidak | Tidak |
| Data Barang — lihat | Ya | Ya | Ya |
| Data Barang — tambah | Ya | Ya | Tidak |
| Data Barang — ubah / hapus | Ya | Tidak | Tidak |
| Barang Masuk — catat | Tidak | Ya | Tidak |
| Barang Masuk — koreksi | Ya | Tidak | Tidak |
| Barang Keluar — ajukan (foto wajib) | Tidak | Ya | Tidak |
| Barang Keluar — Setujui / Tolak | Tidak | Tidak | Ya |

## 3. Mengganti Password

- **Password sendiri**: klik **foto profil** di kanan atas → panel Profil Saya → bagian *Ganti Password* → isi password lama, password baru, dan ulangi password baru.
- **Password pengguna lain**: login sebagai Admin → menu **Pengguna** → **Ubah** → isi password baru (kosongkan bila tidak ingin mengganti).
- Password minimal 6 karakter.

## 4. Mengembalikan Akun ke Kondisi Awal

Bila password lupa atau data perlu dikembalikan seperti semula:

- Lokal: `php artisan migrate:fresh --seed`
- Online (Supabase): `php artisan migrate:fresh --seed --env=supabase`

Perintah ini **menghapus semua data** lalu mengisi ulang data sampel beserta ketiga akun di atas.

## 5. Data Rahasia Lainnya (bukan untuk dibagikan)

Data berikut **tidak dicatat di dokumen ini** karena bersifat rahasia. Simpan sendiri di tempat aman dan jangan pernah di-commit ke GitHub:

| Data | Disimpan di |
|---|---|
| `APP_KEY` | File `.env` (lokal) dan Environment Variables Vercel |
| Password database Supabase | File `.env.supabase` dan Environment Variables Vercel |
| Kunci akses Supabase Storage (S3) | Environment Variables Vercel |
| Akun GitHub, Vercel, Supabase | Akun pribadi peserta |

Bila salah satu data rahasia tersebar: buat ulang (reset) di dashboard layanannya, lalu perbarui di `.env` / Vercel.
