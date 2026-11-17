<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Services\GambarService;
use App\Services\StokService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi pengajuan pemeriksaan kondisi:
 *  - kondisi baru harus berbeda dari kondisi yang tercatat,
 *  - jumlah tidak boleh melebihi stok kondisi asal di lokasi tersebut,
 *  - alasan dan foto bukti wajib (perubahan kondisi harus dapat dipertanggungjawabkan).
 */
class PemeriksaanKondisiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_barang' => ['required', 'integer', 'exists:barang,id_barang'],
            'id_lokasi' => ['required', 'integer', 'exists:lokasi,id_lokasi'],
            'tanggal' => ['required', 'date'],
            'kondisi_asal' => ['required', Rule::in(Barang::KONDISI)],
            'kondisi_baru' => ['required', Rule::in(Barang::KONDISI), 'different:kondisi_asal'],
            'jumlah' => ['required', 'integer', 'min:1'],
            'alasan' => ['required', 'string', 'max:255'],
            'foto' => ['required', ...GambarService::ATURAN],
        ];
    }

    /** Jumlah tidak boleh melebihi stok kondisi asal di lokasi yang dipilih. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $barang = Barang::find($this->integer('id_barang'));
                $tersedia = app(StokService::class)->tersedia($barang->id_barang, $this->integer('id_lokasi'), $this->input('kondisi_asal'));
                if ($this->integer('jumlah') > $tersedia) {
                    $validator->errors()->add('jumlah', "Jumlah melebihi stok {$this->input('kondisi_asal')} di lokasi ini ({$tersedia} {$barang->satuan}).");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'id_barang' => 'barang',
            'id_lokasi' => 'lokasi',
            'kondisi_asal' => 'kondisi tercatat',
            'kondisi_baru' => 'kondisi sebenarnya',
            'foto' => 'foto bukti',
        ];
    }

    public function messages(): array
    {
        return [
            'kondisi_baru.different' => 'Kondisi sebenarnya harus berbeda dari kondisi yang tercatat.',
        ];
    }
}
