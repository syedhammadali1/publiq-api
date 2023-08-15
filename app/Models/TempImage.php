<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class TempImage extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = [
        'media_id',
        'post_id',
        'sorting_key'
    ];

    /**
     * Get the Post that owns the TempImage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function post()
    {
        return $this->belongsTo(UserPost::class, 'id', 'post_id');
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('temp_image');
    }
}
