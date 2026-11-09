<?php

namespace Modules\Products\Services;

use Modules\Products\Models\Product;
use Modules\Shared\Contracts\ProductServiceInterface;

class ProductService implements ProductServiceInterface
{
    public function getProductForOrder(int $productId): ?array
    {
        $product = Product::find($productId);

        if (!$product) {
            return null;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'price' => (float) $product->price,
            'stock' => $product->stock,
        ];
    }

    public function isStockAvailable(int $productId, int $quantity): bool
    {
        $product = Product::find($productId);

        return $product && $product->isInStock($quantity);
    }

    public function reduceStock(int $productId, int $quantity): bool
    {
        $product = Product::lockForUpdate()->find($productId);

        if (!$product || !$product->isInStock($quantity)) {
            return false;
        }

        $product->decrement('stock', $quantity);

        return true;
    }
}
