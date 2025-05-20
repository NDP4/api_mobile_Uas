<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index(Request $request)
    {
        $carts = Cart::with(['product.images', 'variant'])
            ->where('user_id', $request->user_id)
            ->get()
            ->map(function ($cart) {
                $price = $cart->variant_id
                    ? $cart->variant->price * (1 - $cart->variant->discount / 100)
                    : $cart->product->price * (1 - $cart->product->discount / 100);

                $cart->subtotal = $price * $cart->quantity;
                return $cart;
            });

        $total = $carts->sum('subtotal');

        return response()->json([
            'status' => 1,
            'cart_items' => $carts,
            'total' => $total
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id',
            'product_id' => 'required|exists:products_elsid,id',
            'variant_id' => 'nullable|exists:product_variants,id',
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            // Check if product exists and has enough stock
            $product = Product::findOrFail($request->product_id);

            if ($request->variant_id) {
                $variant = ProductVariant::where('id', $request->variant_id)
                    ->where('product_id', $request->product_id)
                    ->firstOrFail();

                if ($variant->stock < $request->quantity) {
                    throw new \Exception('Insufficient variant stock');
                }
            } else {
                if ($product->main_stock < $request->quantity) {
                    throw new \Exception('Insufficient product stock');
                }
            }

            // Check if item already exists in cart
            $existingCart = Cart::where('user_id', $request->user_id)
                ->where('product_id', $request->product_id)
                ->where('variant_id', $request->variant_id)
                ->first();

            if ($existingCart) {
                // Update quantity if item exists
                $newQuantity = $existingCart->quantity + $request->quantity;

                // Validate new quantity against stock
                if ($request->variant_id) {
                    if ($variant->stock < $newQuantity) {
                        throw new \Exception('Cannot add more items: exceeds available variant stock');
                    }
                } else {
                    if ($product->main_stock < $newQuantity) {
                        throw new \Exception('Cannot add more items: exceeds available product stock');
                    }
                }

                $existingCart->quantity = $newQuantity;
                $existingCart->save();
                $cart = $existingCart;
            } else {
                // Create new cart item
                $cart = Cart::create($request->all());
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Item added to cart successfully',
                'cart' => $cart->load(['product.images', 'variant'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'quantity' => 'required|integer|min:1'
        ]);

        try {
            DB::beginTransaction();

            $cart = Cart::findOrFail($id);

            // Verify user owns this cart item
            if ($cart->user_id != $request->user_id) {
                throw new \Exception('Unauthorized access to cart item');
            }

            // Check stock availability
            if ($cart->variant_id) {
                if ($cart->variant->stock < $request->quantity) {
                    throw new \Exception('Requested quantity exceeds available variant stock');
                }
            } else {
                if ($cart->product->main_stock < $request->quantity) {
                    throw new \Exception('Requested quantity exceeds available product stock');
                }
            }

            $cart->quantity = $request->quantity;
            $cart->save();

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Cart updated successfully',
                'cart' => $cart->load(['product.images', 'variant'])
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id, Request $request)
    {
        try {
            $cart = Cart::findOrFail($id);

            // Verify user owns this cart item
            if ($cart->user_id != $request->user_id) {
                throw new \Exception('Unauthorized access to cart item');
            }

            $cart->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Item removed from cart successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
