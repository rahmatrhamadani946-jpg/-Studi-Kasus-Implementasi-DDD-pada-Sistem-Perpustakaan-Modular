<?php

namespace Modules\Orders\Services;

use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderItem;
use Modules\Orders\Models\OrderStatusHistory;
use Modules\Orders\Events\OrderCreated;
use Modules\Orders\Events\OrderStatusChanged;
use Modules\Shared\Contracts\ProductServiceInterface;
use Modules\Shared\Exceptions\InsufficientStockException;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private ProductServiceInterface $productService
    ) {}

    public function createOrder(int $userId, array $items, ?string $shippingAddress = null): Order
    {
        return DB::transaction(function () use ($userId, $items, $shippingAddress) {
            $order = Order::create([
                'user_id' => $userId,
                'order_number' => 'ORD-' . strtoupper(uniqid()),
                'total_amount' => 0,
                'status' => 'pending',
                'shipping_address' => $shippingAddress,
                'ordered_at' => now(),
            ]);

            $totalAmount = 0;

            foreach ($items as $item) {
                $product = $this->productService->getProductForOrder($item['product_id']);

                if (!$product) {
                    throw new \Exception("Produk tidak ditemukan: {$item['product_id']}");
                }

                if (!$this->productService->isStockAvailable($item['product_id'], $item['quantity'])) {
                    throw new InsufficientStockException(
                        $product['name'],
                        $product['stock'],
                        $item['quantity']
                    );
                }

                $subtotal = $product['price'] * $item['quantity'];

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'price_at_purchase' => $product['price'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $subtotal,
                ]);

                $totalAmount += $subtotal;

                $this->productService->reduceStock($item['product_id'], $item['quantity']);
            }

            $order->update(['total_amount' => $totalAmount]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => 'pending',
                'notes' => 'Order created',
                'changed_by' => $userId,
            ]);

            event(new OrderCreated($order->load('items')));

            return $order->load('items', 'statusHistories');
        });
    }

    public function updateStatus(Order $order, string $newStatus, int $changedBy, ?string $notes = null): Order
    {
        return DB::transaction(function () use ($order, $newStatus, $changedBy, $notes) {
            $oldStatus = $order->status;

            $order->update(['status' => $newStatus]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $newStatus,
                'notes' => $notes,
                'changed_by' => $changedBy,
            ]);

            event(new OrderStatusChanged($order, $oldStatus, $newStatus));

            return $order->fresh('statusHistories');
        });
    }
}
