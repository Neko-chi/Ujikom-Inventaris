<?php

namespace App\Http\Requests;

use App\Services\GambarService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Validasi form profil: setiap pengguna hanya dapat mengubah data dirinya sendiri.
 * Untuk mengganti password, password lama wajib diisi dengan benar.
 */
class ProfilRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_user' => ['required', 'string', 'max:100'],
            'foto' => ['nullable', ...GambarService::ATURAN],
            'hapus_foto' => ['nullable', 'boolean'],
            'password_lama' => ['nullable', 'required_with:password', 'current_password'],
            'password' => ['nullable', 'string', 'min:6', 'max:50', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'nama_user' => 'nama lengkap',
            'foto' => 'foto profil',
            'password_lama' => 'password lama',
            'password' => 'password baru',
        ];
    }

    public function messages(): array
    {
        return [
            'password_lama.current_password' => 'Password lama tidak sesuai.',
        ];
    }
}
