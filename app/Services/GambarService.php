<?php

namespace App\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Satu-satunya tempat yang menyimpan, mengganti, dan menghapus file gambar.
 *
 * Lokasi penyimpanan diatur lewat UPLOAD_DISK:
 *  - "public" (lokal/XAMPP) : storage/app/public, diakses lewat /storage (perlu `php artisan storage:link`)
 *  - "s3"     (online)      : Supabase Storage yang kompatibel dengan protokol S3
 * Database hanya menyimpan lokasi file (path), misalnya "barang/aBc123.jpg".
 */
class GambarService
{
    /** Aturan validasi untuk setiap gambar yang diunggah (maksimal 2 MB). */
    public const ATURAN = ['image', 'mimes:jpg,jpeg,png,webp', 'max:2048'];

    public function disk(): Filesystem
    {
        return Storage::disk(config('filesystems.upload_disk'));
    }

    /**
     * Menyimpan file ke folder tertentu dengan nama acak, lalu mengembalikan path-nya.
     */
    public function simpan(UploadedFile $file, string $folder): string
    {
        return $this->disk()->putFile($folder, $file);
    }

    /**
     * Mengganti gambar lama dengan yang baru. Bila tidak ada file baru, path lama dipertahankan.
     */
    public function ganti(?UploadedFile $fileBaru, ?string $pathLama, string $folder): ?string
    {
        if ($fileBaru === null) {
            return $pathLama;
        }

        $pathBaru = $this->simpan($fileBaru, $folder);
        $this->hapus($pathLama);

        return $pathBaru;
    }

    public function hapus(?string $path): void
    {
        if ($path) {
            $this->disk()->delete($path);
        }
    }

    /**
     * Alamat gambar untuk ditampilkan di tag <img>, atau null bila tidak ada gambar.
     */
    public function url(?string $path): ?string
    {
        return $path ? $this->disk()->url($path) : null;
    }
}
