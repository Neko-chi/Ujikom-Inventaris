<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Services\GambarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi input form permintaan barang keluar.
 * Selain aturan dasar, jumlah yang diminta tidak boleh melebihi stok saat ini.
 */
class BarangKeluarRequest extends FormRequest
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
            'pemohon' => ['required', 'string', 'max:100'],
            'tujuan' => ['required', 'string', 'max:100'],
            'status_barang' => ['required', Rule::in(Barang::STATUS_BARANG)],
            'foto' => ['required', ...GambarService::ATURAN],
        ];
    }

    /**
     * Validasi tambahan setelah aturan dasar lolos: cek ketersediaan stok.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $barang = Barang::find($this->integer('id_barang'));

                if ($barang && $this->integer('jumlah') > $barang->stok) {
                    $validator->errors()->add(
                        'jumlah',
                        "Jumlah melebihi stok {$barang->nama_barang} yang tersedia ({$barang->stok} {$barang->satuan})."
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'id_barang' => 'barang',
            'status_barang' => 'kondisi barang',
            'foto' => 'foto bukti',
        ];
    }
}
