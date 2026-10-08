<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Services\GambarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input form barang masuk.
 */
class BarangMasukRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_barang' => ['required', 'integer', 'exists:barang,id_barang'],
            'tanggal' => ['required', 'date'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'sumber_barang' => ['required', 'string', 'max:255'],
            'status_barang' => ['required', Rule::in(Barang::STATUS_BARANG)],
            'foto' => ['nullable', ...GambarService::ATURAN],
        ];
    }

    public function attributes(): array
    {
        return [
            'id_barang' => 'barang',
            'sumber_barang' => 'sumber barang',
            'status_barang' => 'kondisi barang',
            'foto' => 'foto bukti',
        ];
    }
}
