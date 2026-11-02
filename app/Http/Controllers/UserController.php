<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use App\Services\GambarService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola akun pengguna (khusus Admin): lihat, tambah, dan hapus.
 * Admin tidak dapat mengubah data maupun password pengguna lain;
 * setiap pengguna mengubah akunnya sendiri lewat Profil.
 */
class UserController extends Controller
{
    public function index(): View
    {
        $users = User::withCount(['barangMasuk', 'barangKeluar', 'verifikasiBarangKeluar'])
            ->orderBy('role')
            ->orderBy('nama_user')
            ->get();

        return view('user.index', compact('users'));
    }

    public function create(): View
    {
        return view('user.form', ['user' => new User(['role' => User::ROLE_OPERATOR])]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()->route('user.index')->with('sukses', 'Pengguna berhasil ditambahkan.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('gagal', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if ($user->punyaTransaksi()) {
            return back()->with('gagal', "Pengguna {$user->nama_user} sudah memiliki riwayat transaksi sehingga tidak dapat dihapus.");
        }

        $user->delete();
        app(GambarService::class)->hapus($user->foto);

        return redirect()->route('user.index')->with('sukses', 'Pengguna berhasil dihapus.');
    }
}
