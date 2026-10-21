<?php

namespace App\Http\Requests;

use App\Models\Barang;
use App\Models\BarangKeluar;
use App\Services\GambarService;
use App\Services\StokService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Validasi input form pengajuan barang keluar.
 *  - Peminjaman  : wajib rencana tanggal kembali.
 *  - Pemindahan  : wajib lokasi tujuan (berbeda dari lokasi asal).
 *  - Pemusnahan  : hanya barang Rusak Berat.
 * Jumlah per kondisi tidak boleh melebihi stok di lokasi asal.
 */
class BarangKeluarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'jenis_keluar' => ['required', Rule::in(BarangKeluar::JENIS)],
            'id_barang' => ['required', 'integer', 'exists:barang,id_barang'],
            'id_lokasi_asal' => ['required', 'integer', 'exists:lokasi,id_lokasi'],
            'id_lokasi_tujuan' => ['nullable', 'required_if:jenis_keluar,'.BarangKeluar::PEMINDAHAN, 'integer', 'exists:lokasi,id_lokasi', 'different:id_lokasi_asal'],
            'tanggal' => ['required', 'date'],
            'tanggal_kembali' => ['nullable', 'required_if:jenis_keluar,'.BarangKeluar::PEMINJAMAN, 'date', 'after_or_equal:tanggal'],
            'pemohon' => ['required', 'string', 'max:100'],
            'tujuan' => ['required', 'string', 'max:100'],
            'foto' => ['required', ...GambarService::ATURAN],
        ];
        foreach (Barang::KOLOM_KONDISI as $kolom) {
            $rules[$kolom] = ['nullable', 'integer', 'min:0'];
        }

        return $rules;
    }

    /**
     * Validasi tambahan setelah aturan dasar lolos: total minimal 1,
     * aturan pemusnahan, dan ketersediaan stok per kondisi di lokasi asal.
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                foreach (self::periksaRincian(
                    $this->input('jenis_keluar'),
                    Barang::find($this->integer('id_barang')),
                    $this->integer('id_lokasi_asal'),
                    BarangKeluar::rincianDariInput($this->all())
                ) as $field => $pesan) {
                    $validator->errors()->add($field, $pesan);
                }
            },
        ];
    }

    /**
     * Aturan rincian kondisi yang juga dipakai Manager saat mengoreksi rincian ketika menyetujui.
     *
     * @param  array<string, int>  $rincian
     * @return array<string, string> [field => pesan error]
     */
    public static function periksaRincian(string $jenis, ?Barang $barang, int $idLokasi, array $rincian): array
    {
        $error = [];
        if (array_sum($rincian) < 1) {
            $error['jumlah'] = 'Isi jumlah minimal 1 pada salah satu kondisi.';
        }
        if ($jenis === BarangKeluar::PEMUSNAHAN && ($rincian[Barang::BAIK] > 0 || $rincian[Barang::RUSAK_RINGAN] > 0)) {
            $error['jumlah'] = 'Pemusnahan hanya untuk barang Rusak Berat.';
        }

        if ($barang) {
            $stok = app(StokService::class);
            foreach ($rincian as $kondisi => $jumlah) {
                $tersedia = $stok->tersedia($barang->id_barang, $idLokasi, $kondisi);
                if ($jumlah > $tersedia) {
                    $error[Barang::KOLOM_KONDISI[$kondisi]] = "Jumlah {$kondisi} melebihi stok di lokasi asal ({$tersedia} {$barang->satuan}).";
                }
            }
        }

        return $error;
    }

    public function attributes(): array
    {
        return [
            'jenis_keluar' => 'jenis barang keluar',
            'id_barang' => 'barang',
            'id_lokasi_asal' => 'lokasi asal',
            'id_lokasi_tujuan' => 'lokasi tujuan',
            'tanggal_kembali' => 'rencana tanggal kembali',
            'pemohon' => 'peminjam / penanggung jawab',
            'tujuan' => 'keperluan',
            'foto' => 'foto bukti',
        ];
    }

    public function messages(): array
    {
        return [
            'id_lokasi_tujuan.required_if' => 'Pilih ruangan tujuan untuk pemindahan.',
            'id_lokasi_tujuan.different' => 'Lokasi tujuan harus berbeda dari lokasi asal.',
            'tanggal_kembali.required_if' => 'Isi rencana tanggal kembali untuk peminjaman.',
        ];
    }
}
