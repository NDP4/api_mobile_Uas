<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    public function index($user_id)
    {
        try {
            // Get cart items only for the specified user
            $carts = Cart::with(['product.images', 'variant'])
                ->where('user_id', $user_id)
                ->get()
                ->map(function ($cart) {
                    $price = $cart->variant_id ?
                        $cart->variant->price * (1 - $cart->variant->discount / 100) :
                        $cart->product->price * (1 - $cart->product->discount / 100);

                    $subtotal = $price * $cart->quantity;
                    return array_merge($cart->toArray(), ['subtotal' => $subtotal]);
                });

            $total = $carts->sum('subtotal');

            return response()->json([
                'status' => 1,
                'cart_items' => $carts,
                'total' => $total
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->validate($request, [
                'user_id' => 'required|exists:users_elsid,id',
                'product_id' => 'required|exists:products_elsid,id',
                'variant_id' => 'nullable|exists:product_variants_elsid,id',
                'quantity' => 'required|integer|min:1'
            ]);

            $product = Product::findOrFail($request->product_id);

            // Check if product has variants but no variant selected
            if ($product->has_variants && !$request->variant_id) {
                throw new \Exception('Please select a product variant');
            }

            // Check stock
            if ($request->variant_id) {
                $variant = ProductVariant::findOrFail($request->variant_id);
                if ($variant->stock < $request->quantity) {
                    throw new \Exception('Insufficient variant stock');
                }
                if ($variant->stock == 0) {
                    throw new \Exception('Variant is out of stock');
                }
            } else {
                if ($product->main_stock < $request->quantity) {
                    throw new \Exception('Insufficient product stock');
                }
                if ($product->main_stock == 0) {
                    throw new \Exception('Product is out of stock');
                }
            }

            // Check if item already in cart
            $existingCart = Cart::where('user_id', $request->user_id)
                ->where('product_id', $request->product_id)
                ->where('variant_id', $request->variant_id)
                ->first();

            if ($existingCart) {
                $existingCart->quantity += $request->quantity;

                // Recheck stock with updated quantity
                if ($request->variant_id) {
                    if ($variant->stock < $existingCart->quantity) {
                        throw new \Exception('Cannot add more items. Stock limit reached.');
                    }
                } else {
                    if ($product->main_stock < $existingCart->quantity) {
                        throw new \Exception('Cannot add more items. Stock limit reached.');
                    }
                }

                $existingCart->save();
                $cart = $existingCart;
            } else {
                $cart = Cart::create($request->all());
            }

            DB::commit();

            $cart->load(['product.images', 'variant']);
            return response()->json([
                'status' => 1,
                'message' => 'Item added to cart successfully',
                'cart_item' => $cart
            ], 201);
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
        try {
            DB::beginTransaction();

            $this->validate($request, [
                'quantity' => 'required|integer|min:1'
            ]);

            $cart = Cart::findOrFail($id);

            // Verify ownership
            if ($cart->user_id != $request->user_id) {
                throw new \Exception('Unauthorized access to cart item');
            }

            // Check stock
            if ($cart->variant_id) {
                $variant = ProductVariant::findOrFail($cart->variant_id);
                if ($variant->stock < $request->quantity) {
                    throw new \Exception('Insufficient variant stock');
                }
            } else {
                $product = Product::findOrFail($cart->product_id);
                if ($product->main_stock < $request->quantity) {
                    throw new \Exception('Insufficient product stock');
                }
            }

            $cart->quantity = $request->quantity;
            $cart->save();

            DB::commit();

            $cart->load(['product.images', 'variant']);
            return response()->json([
                'status' => 1,
                'message' => 'Cart item updated successfully',
                'cart_item' => $cart
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 400);
        }
    }

    public function destroy($id)
    {
        try {
            $cart = Cart::findOrFail($id);
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

    public function clearCart($user_id)
    {
        try {
            Cart::where('user_id', $user_id)->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Cart cleared successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
