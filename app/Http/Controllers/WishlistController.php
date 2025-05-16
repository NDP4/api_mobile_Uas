<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id'
        ]);

        $wishlists = Wishlist::with(['product.images', 'product.variants'])
            ->where('user_id', $request->user_id)
            ->get();

        return response()->json([
            'status' => 1,
            'wishlist' => $wishlists
        ]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id',
            'product_id' => 'required|exists:products_elsid,id'
        ]);

        try {
            Wishlist::create([
                'user_id' => $request->user_id,
                'product_id' => $request->product_id
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'Product added to wishlist successfully'
            ], 201);
        } catch (\Exception $e) {
            // If unique constraint fails - product already in wishlist
            return response()->json([
                'status' => 0,
                'message' => 'Product already in wishlist'
            ], 400);
        }
    }

    public function destroy(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id',
            'product_id' => 'required|exists:products_elsid,id'
        ]);

        $deleted = Wishlist::where([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id
        ])->delete();

        if ($deleted) {
            return response()->json([
                'status' => 1,
                'message' => 'Product removed from wishlist successfully'
            ]);
        }

        return response()->json([
            'status' => 0,
            'message' => 'Product not found in wishlist'
        ], 404);
    }

    public function check(Request $request)
    {
        $this->validate($request, [
            'user_id' => 'required|exists:users_elsid,id',
            'product_id' => 'required|exists:products_elsid,id'
        ]);

        $exists = Wishlist::where([
            'user_id' => $request->user_id,
            'product_id' => $request->product_id
        ])->exists();

        return response()->json([
            'status' => 1,
            'in_wishlist' => $exists
        ]);
    }
}
