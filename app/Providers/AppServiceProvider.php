<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Modules\Shared\Contracts\ProductServiceInterface;
use Modules\Products\Services\ProductService;
use Modules\Shared\Contracts\BookServiceInterface;
use Modules\Shared\Contracts\MemberServiceInterface;
use Modules\Catalog\Services\BookService;
use Modules\Membership\Services\MemberService;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Contract binding: Sales Context memakai ProductServiceInterface,
        // implementasinya milik Catalog Context.
        $this->app->bind(
            ProductServiceInterface::class,
            ProductService::class
        );

        // Contract binding Perpustakaan: Circulation memakai contract milik Catalog & Membership
        $this->app->bind(BookServiceInterface::class, BookService::class);
        $this->app->bind(MemberServiceInterface::class, MemberService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
