<?php

use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    App\Providers\AppServiceProvider::class,
    Modules\Auth\Providers\AuthServiceProvider::class,
    Modules\Products\Providers\ProductServiceProvider::class,
    Modules\Orders\Providers\OrderServiceProvider::class,
    Modules\Catalog\Providers\CatalogServiceProvider::class,
    Modules\Membership\Providers\MembershipServiceProvider::class,
    Modules\Circulation\Providers\CirculationServiceProvider::class,
];
