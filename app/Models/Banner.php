<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $title
 * @property string $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Banner extends Model
{
    protected $table = 'banners_elsid';

    protected $fillable = [
        'title',
        'status'
    ];

    public function images()
    {
        return $this->hasMany(BannerImage::class, 'banner_id')->orderBy('image_order');
    }
}
