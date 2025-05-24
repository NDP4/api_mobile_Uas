<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CouponUsage extends Model
{
    protected $table = 'coupon_usage_elsid';

    protected $fillable = [
        'coupon_id',
        'user_id',
        'order_id',
        'discount_amount'
    ];

    protected $casts = [
        'discount_amount' => 'float'
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
