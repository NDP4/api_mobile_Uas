<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Coupon extends Model
{
    protected $table = 'coupons_elsid';

    protected $fillable = [
        'code',
        'description',
        'discount_amount',
        'discount_type',
        'min_purchase',
        'usage_limit',
        'used_count',
        'valid_from',
        'valid_until',
        'is_active'
    ];

    protected $casts = [
        'discount_amount' => 'float',
        'min_purchase' => 'float',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'is_active' => 'boolean',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime'
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isValid()
    {
        $now = Carbon::now();
        return $this->is_active &&
            $now->between($this->valid_from, $this->valid_until) &&
            ($this->usage_limit === null || $this->used_count < $this->usage_limit);
    }

    public function canBeUsedByUser($userId)
    {
        $userUsageCount = $this->usages()
            ->where('user_id', $userId)
            ->count();

        return $userUsageCount < ($this->usage_limit ?? PHP_INT_MAX);
    }

    public function calculateDiscount($subtotal)
    {
        if ($subtotal < $this->min_purchase) {
            return 0;
        }

        if ($this->discount_type === 'fixed') {
            return $this->discount_amount;
        }

        // For percentage discount
        return ($subtotal * $this->discount_amount) / 100;
    }
}
