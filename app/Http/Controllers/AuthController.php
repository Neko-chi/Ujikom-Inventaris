<?php

namespace App\Http\Controllers;

use App\Support\Suasana;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Menangani proses login dan logout pengguna.
 */
class AuthController extends Controller
{
    public function index(): View
    {
        // Suasana awal mengikuti jam server; JavaScript lalu menyesuaikan dengan jam perangkat.
        return view('auth.login', [
            'suasana' => Suasana::dariJam(now()->hour),
            'daftarSuasana' => Suasana::untukBrowser(),
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        // Auth::attempt mencocokkan username lalu memverifikasi hash password.
        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'username' => 'Username atau password salah.',
        ])->onlyInput('username');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
