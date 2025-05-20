<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['user:id,fullname', 'items.product', 'items.variant'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 1, 'orders' => $orders]);
    }

    public function show($id)
    {
        $order = Order::with(['user:id,fullname', 'items.product', 'items.variant'])
            ->find($id);

        if (!$order) {
            return response()->json(['status' => 0, 'message' => 'Order not found'], 404);
        }

        return response()->json(['status' => 1, 'order' => $order]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id',
            'items' => 'required|json',
            'shipping_address' => 'required|string',
            'shipping_city' => 'required|string',
            'shipping_province' => 'required|string',
            'shipping_postal_code' => 'required|string',
            'shipping_cost' => 'required|numeric|min:0',
            'courier' => 'required|string',
            'courier_service' => 'required|string'
        ]);

        $items = json_decode($request->items, true);

        if (empty($items)) {
            return response()->json(['status' => 0, 'message' => 'No items in order']);
        }

        try {
            DB::beginTransaction();

            // Calculate total and validate stock
            $total_amount = 0;
            $validated_items = [];

            foreach ($items as $item) {
                $product = Product::with('variants')->findOrFail($item['product_id']);
                $quantity = $item['quantity'];
                $variant = null;

                // Validate if product has variants but no variant_id is provided
                if ($product->has_variants && !isset($item['variant_id'])) {
                    throw new \Exception('Please select a variant for this product');
                }

                if (isset($item['variant_id'])) {
                    $variant = $product->variants->where('id', $item['variant_id'])->first();

                    if (!$variant) {
                        throw new \Exception('Invalid variant selected');
                    }

                    // Check if variant has enough stock
                    if ($variant->stock < $quantity) {
                        throw new \Exception('Insufficient stock for variant');
                    }
                    if ($variant->stock == 0) {
                        throw new \Exception('Selected variant is out of stock');
                    }

                    // Validate total variant stock against main stock
                    $totalVariantStock = $product->variants->sum('stock');
                    if ($totalVariantStock !== $product->main_stock) {
                        throw new \Exception('Product variant stock mismatch with main stock');
                    }

                    $price = $variant->price * (1 - ($variant->discount / 100));
                } else {
                    if ($product->main_stock < $quantity) {
                        throw new \Exception('Insufficient stock for product');
                    }
                    if ($product->main_stock == 0) {
                        throw new \Exception('Product is out of stock');
                    }

                    $price = $product->price * (1 - ($product->discount / 100));
                }

                $subtotal = $price * $quantity;
                $total_amount += $subtotal;

                $validated_items[] = [
                    'product_id' => $product->id,
                    'variant_id' => $variant ? $variant->id : null,
                    'quantity' => $quantity,
                    'price' => $price,
                    'subtotal' => $subtotal
                ];
            }

            // Create order
            $order = Order::create([
                'user_id' => $request->user_id,
                'total_amount' => $total_amount + $request->shipping_cost,
                'shipping_cost' => $request->shipping_cost,
                'courier' => $request->courier,
                'courier_service' => $request->courier_service,
                'shipping_address' => $request->shipping_address,
                'shipping_city' => $request->shipping_city,
                'shipping_province' => $request->shipping_province,
                'shipping_postal_code' => $request->shipping_postal_code
            ]);

            // Create order items and update stock
            foreach ($validated_items as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'],
                    'subtotal' => $item['subtotal']
                ]);

                if ($item['variant_id']) {
                    // Update both variant stock and main stock
                    ProductVariant::where('id', $item['variant_id'])
                        ->decrement('stock', $item['quantity']);
                    Product::where('id', $item['product_id'])
                        ->decrement('main_stock', $item['quantity']);
                } else {
                    Product::where('id', $item['product_id'])
                        ->decrement('main_stock', $item['quantity']);
                }

                // Update purchase count
                Product::where('id', $item['product_id'])
                    ->increment('purchase_count', $item['quantity']);
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Order created successfully',
                'order' => $order->load(['items.product', 'items.variant'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => 0, 'message' => $e->getMessage()], 400);
        }
    }

    public function getUserOrders($userId)
    {
        $orders = Order::with(['items.product', 'items.variant'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 1, 'orders' => $orders]);
    }

    public function updateStatus(Request $request, $id)
    {
        $this->validate($request, [
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $order = Order::findOrFail($id);
        $order->status = $request->status;
        $order->save();

        return response()->json([
            'status' => 1,
            'message' => 'Order status updated successfully'
        ]);
    }

    public function getPurchaseHistory($userId)
    {
        $orders = Order::with(['items.product', 'items.variant'])
            ->where('user_id', $userId)
            ->where('status', 'delivered')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($order) {
                $order->items->map(function ($item) {
                    $item->can_reorder = true;
                    if ($item->variant_id) {
                        $variant = ProductVariant::find($item->variant_id);
                        $item->can_reorder = $variant && $variant->stock > 0;
                    } else {
                        $product = Product::find($item->product_id);
                        $item->can_reorder = $product && $product->main_stock > 0;
                    }
                    return $item;
                });
                return $order;
            });

        return response()->json([
            'status' => 1,
            'purchase_history' => $orders
        ]);
    }

    public function reorder(Request $request)
    {
        $this->validate($request, [
            'order_id' => 'required|exists:orders_elsid,id',
            'items' => 'required|array',
            'items.*.order_item_id' => 'required|exists:order_items_elsid,id'
        ]);

        try {
            $originalOrder = Order::with('items')->findOrFail($request->order_id);
            $selectedItems = collect($request->items)->pluck('order_item_id');

            $items = $originalOrder->items->whereIn('id', $selectedItems)
                ->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'variant_id' => $item->variant_id,
                        'quantity' => 1 // Default to 1 for reorders
                    ];
                })->toArray();

            // Create new request with items
            $newRequest = new Request();
            $newRequest->merge([
                'user_id' => $originalOrder->user_id,
                'items' => json_encode($items),
                'shipping_address' => $originalOrder->shipping_address,
                'shipping_city' => $originalOrder->shipping_city,
                'shipping_province' => $originalOrder->shipping_province,
                'shipping_postal_code' => $originalOrder->shipping_postal_code,
                'shipping_cost' => $originalOrder->shipping_cost,
                'courier' => $originalOrder->courier,
                'courier_service' => $originalOrder->courier_service
            ]);

            return $this->store($newRequest);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to reorder: ' . $e->getMessage()
            ], 400);
        }
    }
}
