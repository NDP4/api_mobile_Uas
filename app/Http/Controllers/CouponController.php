<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CouponController extends Controller
{
    public function index()
    {
        $coupons = Coupon::all();
        return response()->json(['status' => 1, 'coupons' => $coupons]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|unique:coupons_elsid,code',
            'description' => 'nullable|string',
            'discount_amount' => 'required|numeric|min:0',
            'discount_type' => 'required|in:fixed,percentage',
            'min_purchase' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'required|date',
            'valid_until' => 'required|date|after:valid_from',
            'is_active' => 'boolean'
        ]);

        try {
            $coupon = Coupon::create($request->all());
            return response()->json([
                'status' => 1,
                'message' => 'Coupon created successfully',
                'coupon' => $coupon
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to create coupon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function validate_coupon(Request $request)
    {
        $this->validate($request, [
            'code' => 'required|exists:coupons_elsid,code',
            'user_id' => 'required|exists:users_elsid,id',
            'subtotal' => 'required|numeric|min:0'
        ]);

        try {
            $coupon = Coupon::where('code', $request->code)->first();

            if (!$coupon->isValid()) {
                return response()->json([
                    'status' => 0,
                    'message' => 'This coupon has expired or is no longer valid'
                ], 400);
            }

            if (!$coupon->canBeUsedByUser($request->user_id)) {
                return response()->json([
                    'status' => 0,
                    'message' => 'You have reached the usage limit for this coupon'
                ], 400);
            }

            if ($request->subtotal < $coupon->min_purchase) {
                return response()->json([
                    'status' => 0,
                    'message' => "Minimum purchase amount of {$coupon->min_purchase} required"
                ], 400);
            }

            $discount = $coupon->calculateDiscount($request->subtotal);

            return response()->json([
                'status' => 1,
                'message' => 'Coupon is valid',
                'discount' => $discount,
                'coupon' => $coupon
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Error validating coupon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'code' => 'unique:coupons_elsid,code,' . $id,
            'discount_amount' => 'numeric|min:0',
            'discount_type' => 'in:fixed,percentage',
            'min_purchase' => 'numeric|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'valid_from' => 'date',
            'valid_until' => 'date|after:valid_from',
            'is_active' => 'boolean'
        ]);

        try {
            $coupon = Coupon::findOrFail($id);
            $coupon->update($request->all());

            return response()->json([
                'status' => 1,
                'message' => 'Coupon updated successfully',
                'coupon' => $coupon
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to update coupon: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            $coupon = Coupon::findOrFail($id);
            $coupon->delete();

            return response()->json([
                'status' => 1,
                'message' => 'Coupon deleted successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Failed to delete coupon: ' . $e->getMessage()
            ], 500);
        }
    }
}
