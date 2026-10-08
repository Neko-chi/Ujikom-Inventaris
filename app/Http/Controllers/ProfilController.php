<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfilRequest;
use App\Services\GambarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Profil pengguna yang sedang login (semua role).
 * Pengguna dapat mengubah nama, foto profil, dan password miliknya sendiri.
 * Username dan role hanya dapat diubah Admin lewat menu Pengguna.
 */
class ProfilController extends Controller
{
    public function __construct(private GambarService $gambar) {}

    public function edit(Request $request): View
    {
        return view('profil.edit', ['user' => $request->user()]);
    }

    public function update(ProfilRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = ['nama_user' => $request->validated('nama_user')];

        if ($request->hasFile('foto')) {
            $data['foto'] = $this->gambar->ganti($request->file('foto'), $user->foto, 'profil');
        } elseif ($request->boolean('hapus_foto')) {
            $this->gambar->hapus($user->foto);
            $data['foto'] = null;
        }

        if ($request->filled('password')) {
            $data['password'] = $request->validated('password');
        }

        $user->update($data);

        return redirect()->route('profil.edit')->with('sukses', 'Profil berhasil diperbarui.');
    }
}
