<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Services\GambarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi input form barang masuk.
 * Barang masuk dicatat ke satu lokasi, dengan jumlah per kondisi
 * (tidak semua barang yang datang selalu dalam kondisi baik).
 */
class BarangMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'id_barang' => ['required', 'integer', 'exists:barang,id_barang'],
            'id_lokasi' => ['required', 'integer', 'exists:lokasi,id_lokasi'],
            'tanggal' => ['required', 'date'],
            'sumber_barang' => ['required', 'string', 'max:255'],
            'foto' => ['nullable', ...GambarService::ATURAN],
        ];
        foreach (Barang::KOLOM_KONDISI as $kolom) {
            $rules[$kolom] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }

    /** Minimal satu unit harus dicatat. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                $total = array_sum(array_map(fn ($k) => (int) $this->input($k), Barang::KOLOM_KONDISI));
                if ($total < 1) {
                    $validator->errors()->add('jumlah', 'Isi jumlah minimal 1 pada salah satu kondisi.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'id_barang' => 'barang',
            'id_lokasi' => 'lokasi',
            'sumber_barang' => 'sumber barang',
            'foto' => 'foto bukti',
        ];
    }
}
