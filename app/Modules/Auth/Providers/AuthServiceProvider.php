<?php

namespace Modules\Auth\Providers;

use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Memuat migrasi modul (roles, role_user)
        $this->loadMigrationsFrom(__DIR__ . '/../Migrations');

        $this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
    }
}
