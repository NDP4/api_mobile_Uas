<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductReview;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index($productId)
    {
        $reviews = ProductReview::with(['user:id,fullname,avatar'])
            ->where('product_id', $productId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 1, 'reviews' => $reviews]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|exists:products_elsid,id',
            'user_id' => 'required|exists:users_elsid,id',
            'rating' => 'required|integer|min:1|max:5',
            'review' => 'nullable|string'
        ]);

        // Check if user has already reviewed this product
        $existingReview = ProductReview::where('product_id', $request->product_id)
            ->where('user_id', $request->user_id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'status' => 0,
                'message' => 'You have already reviewed this product'
            ], 400);
        }

        $review = ProductReview::create($request->all());

        return response()->json([
            'status' => 1,
            'message' => 'Review added successfully',
            'review' => $review->load('user:id,fullname,avatar')
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'rating' => 'nullable|integer|min:1|max:5',
            'review' => 'nullable|string'
        ]);

        $review = ProductReview::findOrFail($id);

        // Check if the review belongs to the user
        if ($review->user_id != $request->user_id) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->update($request->only(['rating', 'review']));

        return response()->json([
            'status' => 1,
            'message' => 'Review updated successfully',
            'review' => $review->load('user:id,fullname,avatar')
        ]);
    }

    public function destroy($id, Request $request)
    {
        $review = ProductReview::findOrFail($id);

        // Check if the review belongs to the user
        if ($review->user_id != $request->user_id) {
            return response()->json([
                'status' => 0,
                'message' => 'Unauthorized'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Review deleted successfully'
        ]);
    }

    public function userReviews($userId)
    {
        $reviews = ProductReview::with(['product:id,title', 'user:id,fullname,avatar'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['status' => 1, 'reviews' => $reviews]);
    }
}
