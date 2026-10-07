<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input form tambah / ubah kategori.
 */
class KategoriRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idKategori = $this->route('kategori')?->id_kategori;

        return [
            'nama_kategori' => [
                'required', 'string', 'max:20',
                Rule::unique('kategori', 'nama_kategori')->ignore($idKategori, 'id_kategori'),
            ],
        ];
    }

    public function attributes(): array
    {
        return ['nama_kategori' => 'nama kategori'];
    }
}
