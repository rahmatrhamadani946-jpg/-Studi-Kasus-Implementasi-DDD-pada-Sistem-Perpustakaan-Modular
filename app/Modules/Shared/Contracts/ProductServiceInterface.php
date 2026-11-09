<?php

namespace Modules\Shared\Contracts;

interface ProductServiceInterface
{
    public function getProductForOrder(int $productId): ?array;

    public function isStockAvailable(int $productId, int $quantity): bool;

    public function reduceStock(int $productId, int $quantity): bool;
}
