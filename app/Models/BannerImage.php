<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $banner_id
 * @property string $image_url
 * @property int $image_order
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class BannerImage extends Model
{
    protected $table = 'banner_images_elsid';

    protected $fillable = [
        'banner_id',
        'image_url',
        'image_order'
    ];

    public function banner()
    {
        return $this->belongsTo(Banner::class, 'banner_id');
    }
}
