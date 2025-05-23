<?php

namespace App\Http\Controllers;

use App\Models\Wishlist;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index($userId)
    {
        try {
            $wishlist = Wishlist::with('product')
                ->where('user_id', $userId)
                ->get();

            return response()->json([
                'status' => 1,
                'wishlist' => $wishlist
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error retrieving wishlist: ' . $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request, $userId)
    {
        $this->validate($request, [
            'product_id' => 'required|exists:products_elsid,id'
        ]);

        try {
            $exists = Wishlist::where('user_id', $userId)
                ->where('product_id', $request->product_id)
                ->exists();

            if ($exists) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Product already in wishlist'
                ], 400);
            }

            $wishlist = Wishlist::create([
                'user_id' => $userId,
                'product_id' => $request->product_id
            ]);

            return response()->json([
                'status' => 1,
                'message' => 'Product added to wishlist',
                'data' => $wishlist
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error adding to wishlist: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($userId, $productId)
    {
        try {
            $wishlist = Wishlist::where('user_id', $userId)
                ->where('product_id', $productId)
                ->first();

            if (!$wishlist) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Product not found in wishlist'
                ], 404);
            }

            $wishlist->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Product removed from wishlist'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error removing from wishlist: ' . $e->getMessage()
            ], 500);
        }
    }

    public function check($userId, $productId)
    {
        try {
            $exists = Wishlist::where('user_id', $userId)
                ->where('product_id', $productId)
                ->exists();

            return response()->json([
                'status' => 1,
                'exists' => $exists
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error checking wishlist: ' . $e->getMessage()
            ], 500);
        }
    }
}
