<?php

namespace app\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Admin dan pimpinan boleh melihat daftar akun
        Gate::define('view-users', function (User $user) {
            return in_array(
                $user->role,
                ['admin', 'pimpinan'],
                true
            );
        });

        // Hanya admin boleh mengelola akun
        Gate::define('manage-users', function (User $user) {
            return $user->role === 'admin';
        });

        // Semua role boleh melihat data stok dan laporan
        Gate::define('view-stock', function (User $user) {
            return in_array(
                $user->role,
                ['admin', 'pimpinan', 'gudang'],
                true
            );
        });

        // Hanya admin boleh mengelola data master barang
        Gate::define('manage-barang', function (User $user) {
            return $user->role === 'admin';
        });

        // Admin dan gudang boleh mengelola transaksi
        Gate::define('manage-stock-transactions', function (User $user) {
            return in_array(
                $user->role,
                ['admin', 'gudang'],
                true
            );
        });
    }
}
