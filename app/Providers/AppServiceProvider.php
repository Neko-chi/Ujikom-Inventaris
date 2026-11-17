<?php

namespace App\Providers;

use App\Models\BarangKeluar;
use App\Models\PemeriksaanKondisi;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Di server produksi semua URL dipaksa https.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // Tampilan navigasi halaman (pagination) berbahasa Indonesia.
        Paginator::defaultView('partials.pagination');

        // Jumlah permintaan Pending untuk penanda di menu sidebar & tab filter,
        // dihitung di sini agar view tidak berisi query database.
        View::composer(['layouts.app', 'barang-keluar.index'], function ($view) {
            $view->with('jumlahPending', BarangKeluar::where('verifikasi', BarangKeluar::PENDING)->count());
        });
        View::composer(['layouts.app', 'pemeriksaan-kondisi.index'], function ($view) {
            $view->with('jumlahPendingKondisi', PemeriksaanKondisi::where('verifikasi', BarangKeluar::PENDING)->count());
        });
    }
}
