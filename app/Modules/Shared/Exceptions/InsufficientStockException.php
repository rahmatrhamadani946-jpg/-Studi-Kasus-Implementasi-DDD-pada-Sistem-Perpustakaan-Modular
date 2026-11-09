<?php

namespace Modules\Shared\Exceptions;

use Exception;

class InsufficientStockException extends Exception
{
    protected $productName;
    protected $available;
    protected $requested;

    public function __construct(string $productName, int $available, int $requested)
    {
        $this->productName = $productName;
        $this->available = $available;
        $this->requested = $requested;

        parent::__construct(
            "Stok produk '{$productName}' tidak mencukupi. " .
                "Tersedia: {$available}, diminta: {$requested}."
        );
    }

    public function getProductName(): string
    {
        return $this->productName;
    }

    public function getAvailable(): int
    {
        return $this->available;
    }

    public function getRequested(): int
    {
        return $this->requested;
    }

    public function render($request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'insufficient_stock',
                'details' => [
                    'product' => $this->productName,
                    'available' => $this->available,
                    'requested' => $this->requested,
                ],
            ], 422);
        }

        return back()->withErrors([
            'stock' => $this->getMessage(),
        ]);
    }
}
