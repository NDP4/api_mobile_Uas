<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    protected $table = 'product_variants_elsid';

    protected $fillable = [
        'product_id',
        'variant_name',
        'price',
        'stock',
        'discount'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    protected $casts = [
        'price' => 'float',
        'discount' => 'float',
        'stock' => 'integer'
    ];
}
