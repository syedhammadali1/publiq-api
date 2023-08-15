<?php

namespace App\Models;

// use Attribute;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Overtrue\LaravelLike\Traits\Likeable;
use Illuminate\Support\Str;


class UserPost extends Model implements HasMedia
{
    use InteractsWithMedia;
    use HasFactory;
    use Likeable;
    protected $fillable = ['user_id', 'uuid', 'title', 'description', 'is_paid', 'type', 'parent_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(config('media-library.media_model'), 'model')->orderBy('order_column');
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('image');
        $this
            ->addMediaCollection('paidImage');
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('blurimage')
            // ->blur(25)
            // ->blur(99);
            ->pixelate(30)
            // ->sharpen(10)
            ->performOnCollections('paidImage');
        // ->nonQueued();
    }

    /**
     * Get the shareOf that owns the UserPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shareOf()
    {
        return $this->belongsTo(UserPost::class, 'parent_id', 'id');
    }
    /**
     * Get the shareOf that owns the UserPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shareOfWithRelations()
    {
        return $this->belongsTo(UserPost::class, 'parent_id', 'id')->withCount('comments', 'likers', 'media')
            ->with('media', 'user.media', 'comments.user.media');
    }

    // /**
    //  * Get all of the shares for the UserPost
    //  *
    //  * @return \Illuminate\Database\Eloquent\Relations\HasMany
    //  */
    // public function shares()
    // {
    //     return $this->hasMany(UserPost::class, 'parent_id', 'id');
    // }

    /**
     * Get all of the shares for the UserPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function shares()
    {
        // return $this->hasManyThrough(UserPost::class, 'parent_id', 'id', User::class, 'user_id', 'id')->dd();
        return $this->hasManyThrough(
            User::class,
            UserPost::class,
            'parent_id', // Foreign key on the ConstructionProduct table...
            'id', // Local key on the projects table...
            'id', // Local key on the environments table...
            'user_id', // Foreign key on the ConstructionStoreProduct table...
        );
    }




    /**
     * Get all of the comments for the UserPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function comments()
    {
        return $this->hasMany(Comment::class, 'post_id');
    }

    public function paginatedComments($perPage)
    {
        return $this->comments()->latest()->paginate($perPage, $columns = ['*'], $pageName = 'comments' . $this->id);
    }

    public function getFirstImageAttribute()
    {
        if ($this->getFirstMediaUrl('paidImage') == '') {
            return asset('frontend/assets/images/my-profile.jpg');
        } else {
            return $this->getFirstMediaUrl('avatar');
        }
    }

    /**
     * Get all of the tempImages for the UserPost
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tempImages()
    {
        return $this->hasMany(TempImage::class, 'post_id');
    }


    public static function boot()
    {
        parent::boot();

        self::created(function ($post) {
            $post->update([
                'uuid' => Str::uuid()
            ]);
        });

        static::deleting(function ($post) { // before delete() method call this
            if ($post->likers) {
                $post->likers->each(function ($liker) {
                    $liker->pivot->delete();
                });
            }
            if ($post->comments) {
                $post->comments->each(function ($comment) {
                    if ($comment->likers) {
                        $comment->likers->each(function ($liker) {
                            $liker->pivot->delete();
                        });
                    }
                    $comment->forceDelete();
                });
            }
            if ($post->media) {
                $post->media()->delete();
            }
        });
    }
}
