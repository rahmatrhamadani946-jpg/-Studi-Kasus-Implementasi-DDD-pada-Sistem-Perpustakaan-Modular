<?php

namespace Modules\Orders\Providers;

use Illuminate\Support\ServiceProvider;

class OrderServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Memuat migrasi modul (orders, order_items, order_status_histories)
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');
    }
}
