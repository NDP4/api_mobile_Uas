<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string|null $description
 * @property string|null $category
 * @property float $price
 * @property float $discount
 * @property int $main_stock
 * @property int $weight
 * @property string $status
 * @property bool $has_variants
 * @property int $purchase_count
 * @property int $view_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Product extends Model
{
    protected $table = 'products_elsid';

    protected $fillable = [
        'title',
        'description',
        'category',
        'price',
        'discount',
        'main_stock',
        'weight',
        'status',
        'has_variants',
        'purchase_count',
        'view_count'
    ];

    // public $id;
    // public $has_variants;
    // public $main_stock;

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id');
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id')->orderBy('image_order');
    }

    protected $casts = [
        'has_variants' => 'boolean',
        'price' => 'float',
        'discount' => 'float',
        'main_stock' => 'integer',
        'weight' => 'integer',
        'purchase_count' => 'integer',
        'view_count' => 'integer'
    ];
}
