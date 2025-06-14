<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $table = 'orders_elsid';

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'payment_token',
        'payment_url',
        'shipping_address',
        'shipping_city',
        'shipping_province',
        'shipping_postal_code',
        'shipping_cost',
        'courier',
        'courier_service',
        'estimated_day'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function couponUsage()
    {
        return $this->hasOne(CouponUsage::class, 'order_id');
    }

    protected $casts = [
        'total_amount' => 'float',
        'shipping_cost' => 'float'
    ];
}
