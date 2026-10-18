<?php

namespace App\Services;

use App\Exceptions\GambarGagalDisimpanException;
use Aws\Exception\AwsException;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

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
     *
     * @throws GambarGagalDisimpanException bila penyimpanan menolak / tidak dapat dihubungi.
     *         Penyebab aslinya dicatat ke log (di Vercel tampil di menu Logs).
     */
    public function simpan(UploadedFile $file, string $folder): string
    {
        try {
            $path = $this->disk()->putFile($folder, $file);
        } catch (Throwable $e) {
            Log::error('Gambar gagal disimpan: '.$this->ringkasPenyebab($e));
            throw new GambarGagalDisimpanException($e);
        }

        // Sebagian driver mengembalikan false (bukan error) saat gagal menyimpan
        if (! is_string($path) || $path === '') {
            Log::error('Gambar gagal disimpan: disk "'.config('filesystems.upload_disk').'" mengembalikan false');
            throw new GambarGagalDisimpanException;
        }

        return $path;
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
        if (! $path) {
            return;
        }

        // Gagal menghapus file lama tidak boleh menggagalkan proses utama; cukup dicatat di log.
        try {
            $this->disk()->delete($path);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Alamat gambar untuk ditampilkan di tag <img>, atau null bila tidak ada gambar.
     */
    public function url(?string $path): ?string
    {
        return $path ? $this->disk()->url($path) : null;
    }

    /**
     * Penyebab kegagalan dalam satu baris pendek, agar tidak terpotong di log Vercel.
     * Contoh: disk "s3" | UnableToWriteFile: ... <- S3: InvalidAccessKeyId - The Access Key Id ... does not exist
     */
    private function ringkasPenyebab(Throwable $e): string
    {
        $bagian = [];
        for ($x = $e; $x !== null; $x = $x->getPrevious()) {
            if ($x instanceof AwsException) {
                $bagian[] = 'S3: '.($x->getAwsErrorCode() ?? 'tanpa kode').' - '.($x->getAwsErrorMessage() ?? 'HTTP '.$x->getStatusCode());
            } else {
                $bagian[] = class_basename($x).': '.Str::limit(strtok($x->getMessage(), "\n") ?: '', 200);
            }
        }

        return 'disk "'.config('filesystems.upload_disk').'" | '.implode(' <- ', $bagian);
    }
}
