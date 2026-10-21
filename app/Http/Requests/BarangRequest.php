<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Services\GambarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi input form tambah / ubah data barang.
 * Kode barang tidak diinput manual, melainkan dibuat otomatis.
 * Stok awal (opsional) hanya diisi saat barang baru dibuat: satu lokasi + jumlah per kondisi.
 * Selanjutnya stok berubah lewat transaksi barang masuk, barang keluar, dan pengembalian.
 */
class BarangRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'id_kategori' => ['required', 'integer', 'exists:kategori,id_kategori'],
            'nama_barang' => ['required', 'string', 'max:100'],
            'satuan' => ['required', 'string', 'max:20'],
            'gambar' => ['nullable', ...GambarService::ATURAN],
            'hapus_gambar' => ['nullable', 'boolean'],
        ];

        if ($this->isMethod('post')) {
            $rules['id_lokasi'] = ['nullable', 'integer', 'exists:lokasi,id_lokasi'];
            foreach (Barang::KOLOM_KONDISI as $kolom) {
                $rules[$kolom] = ['nullable', 'integer', 'min:0'];
            }
        }

        return $rules;
    }

    /** Bila stok awal diisi, lokasi penyimpanannya wajib dipilih. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $total = array_sum(array_map(fn ($k) => (int) $this->input($k), Barang::KOLOM_KONDISI));
                if ($this->isMethod('post') && $total > 0 && ! $this->filled('id_lokasi')) {
                    $validator->errors()->add('id_lokasi', 'Pilih lokasi penyimpanan untuk stok awal.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'id_kategori' => 'kategori',
            'nama_barang' => 'nama barang',
            'id_lokasi' => 'lokasi',
            'gambar' => 'gambar barang',
        ];
    }
}
