<?php

namespace App\Http\Requests;

use App\Models\Barang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input form tambah / ubah data barang.
 * Kode barang tidak diinput manual, melainkan dibuat otomatis.
 * Stok hanya diisi saat barang baru dibuat (stok awal); selanjutnya
 * stok berubah lewat transaksi barang masuk / keluar.
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
            'lokasi' => ['required', 'string', 'max:100'],
            'status_barang' => ['required', Rule::in(Barang::STATUS_BARANG)],
        ];

        if ($this->isMethod('post')) {
            $rules['stok'] = ['required', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'id_kategori' => 'kategori',
            'nama_barang' => 'nama barang',
            'status_barang' => 'kondisi barang',
        ];
    }
}
