<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Services\GambarService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

/**
 * Validasi pengembalian barang pinjaman.
 * Kondisi saat kembali boleh berbeda dari saat dipinjam (mis. dipinjam 10 Baik,
 * kembali 8 Baik + 2 Rusak Ringan). Jumlah tidak boleh melebihi sisa pinjaman.
 */
class PengembalianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'tanggal' => ['required', 'date'],
            'id_lokasi' => ['required', 'integer', 'exists:lokasi,id_lokasi'],
            'catatan' => ['nullable', 'string', 'max:255'],
            'foto' => ['nullable', ...GambarService::ATURAN],
        ];
        foreach (Barang::KOLOM_KONDISI as $kolom) {
            $rules[$kolom] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var BarangKeluar $pinjaman */
                $pinjaman = $this->route('barangKeluar');
                $total = array_sum(BarangKeluar::rincianDariInput($this->all()));
                $sisa = $pinjaman->sisaPinjaman();

                if ($total < 1) {
                    $validator->errors()->add('jumlah', 'Isi jumlah minimal 1 pada salah satu kondisi.');
                } elseif ($total > $sisa) {
                    $validator->errors()->add('jumlah', "Jumlah yang dikembalikan melebihi sisa pinjaman ({$sisa} {$pinjaman->barang->satuan}).");
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'id_lokasi' => 'lokasi penyimpanan',
            'foto' => 'foto bukti',
        ];
    }
}
