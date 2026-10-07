<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Kelola akun pengguna (khusus Admin).
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

    public function edit(User $user): View
    {
        return view('user.form', compact('user'));
    }

    public function update(UserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();

        // Password kosong berarti tidak diganti.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Admin tidak boleh menurunkan role dirinya sendiri agar sistem tidak kehilangan Admin.
        if ($user->is($request->user()) && $data['role'] !== User::ROLE_ADMIN) {
            return back()->withInput()->with('gagal', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        $user->update($data);

        return redirect()->route('user.index')->with('sukses', 'Data pengguna berhasil diperbarui.');
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

        return redirect()->route('user.index')->with('sukses', 'Pengguna berhasil dihapus.');
    }
}
