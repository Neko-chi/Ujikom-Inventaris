<?php

namespace App\Http\Requests;

use App\Models\Lokasi;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input form tambah / ubah lokasi.
 */
class LokasiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $idLokasi = $this->route('lokasi')?->id_lokasi;

        return [
            'nama_lokasi' => [
                'required', 'string', 'max:50',
                Rule::unique('lokasi', 'nama_lokasi')->ignore($idLokasi, 'id_lokasi'),
            ],
            'jenis' => ['required', Rule::in(Lokasi::JENIS)],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return ['nama_lokasi' => 'nama lokasi'];
    }
}
