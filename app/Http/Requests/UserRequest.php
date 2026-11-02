<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi input form tambah pengguna (khusus Admin).
 */
class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nama_user' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:50', 'alpha_dash', Rule::unique('user', 'username')],
            'password' => ['required', 'string', 'min:6', 'max:50', 'confirmed'],
            'role' => ['required', Rule::in(User::ROLES)],
        ];
    }

    public function attributes(): array
    {
        return ['nama_user' => 'nama pengguna'];
    }
}
