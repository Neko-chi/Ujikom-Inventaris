<?php

use App\Http\Middleware\CekRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => CekRole::class,
        ]);

        // Saat di-hosting (misalnya Vercel), HTTPS ditangani oleh proxy di depan aplikasi.
        // Header X-Forwarded-* dari proxy dipercaya agar URL & form tetap memakai https.
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Error 419 "Page Expired": token form (CSRF) sudah tidak cocok, biasanya karena halaman
        // dibiarkan terbuka lebih lama dari masa berlaku sesi. Daripada menampilkan halaman error,
        // pengguna dikembalikan ke halaman sebelumnya dengan pesan jelas dan isian tetap terisi
        // (kecuali password dan file).
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() !== 419 || $request->expectsJson()) {
                return null;
            }

            $pesan = 'Halaman sudah terlalu lama dibuka sehingga sesi berakhir. Silakan kirim ulang formulirnya.';

            return redirect()->back()
                ->withInput($request->except(['_token', 'password', 'password_confirmation', 'password_lama']))
                ->withErrors(['sesi' => $pesan])
                ->with('gagal', $pesan);
        });
    })->create();
