<?php

namespace Modules\Products\Providers;

use Illuminate\Support\ServiceProvider;

class ProductServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Memuat migrasi modul
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        // Memuat route modul
        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
