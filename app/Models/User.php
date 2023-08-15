<?php

namespace App\Models;

use App\Notifications\ChatNotification;
use App\Notifications\UserCommentLikeNotification;
use App\Notifications\UserFollowAcceptNotification;
use App\Notifications\UserFollowRequestNotification;
use App\Notifications\UserPostCommentNotification;
use App\Notifications\UserPostLikeNotification;
use App\Notifications\UserPostUpdatedNotification;
use App\Notifications\UserPostUploadedNotification;
use App\Notifications\UserSubscribeNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;
use Laravel\Passport\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Overtrue\LaravelSubscribe\Traits\Subscribable;
use Overtrue\LaravelSubscribe\Traits\Subscriber;
use Overtrue\LaravelFollow\Traits\Followable;
use Overtrue\LaravelFollow\Traits\Follower;
use Overtrue\LaravelFollow\Followable as FollowModel;

use Overtrue\LaravelLike\Traits\Liker;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    use HasApiTokens,
        HasFactory,
        Notifiable,
        Followable,
        Follower,
        Subscriber,
        Subscribable,
        Notifiable,
        InteractsWithMedia,
        Liker;


    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'type',
        'is_online',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'media'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',

    ];


    /**
     * The channels the user receives notification broadcasts on.
     *
     * @return string
     */
    public function receivesBroadcastNotificationsOn()
    {
        return [
            'App.Models.User.' . $this->id,
            'App.Chat.User.' . $this->id
        ];
    }

    protected $appends = ['full_name', 'avatar', 'cover'];

    public function getFullNameAttribute()
    {
        if ($this->detail) {
            return $this->detail->first_name . ' ' . $this->detail->last_name;
        }
    }


    public function getAvatarAttribute()
    {
        if ($this->getFirstMediaUrl('avatar') == '') {
            return asset('frontend/assets/images/noimage.png');
        } else {
            return $this->getFirstMediaUrl('avatar');
        }

        // $avatar = $this->getFirstMediaUrl('avatar');
        // if (!$avatar) {
        //     $avatar = url('/frontend/assets/images/noimage.png');
        // }
        // return [
        //     'cover' => $cover,
        //     'avatar' => $avatar,
        // ];
    }

    public function getCoverAttribute()
    {
        if ($this->getFirstMediaUrl('cover') == '') {
            return asset('frontend/assets/images/view-profile-bg.jpg');
        } else {
            return $this->getFirstMediaUrl('cover');
        }
    }

    public function needsToApproveFollowRequests()
    {
        return true;
    }

    public function detail()
    {
        return $this->hasOne(UserDetails::class);
    }

    // to upload media with media library
    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('avatar')
            ->singleFile();
        $this
            ->addMediaCollection('cover')
            ->singleFile();
    }

    // to count user followers
    public function getMyFollowersCountAttribute()
    {
        return $this->followers()->whereNotNull('accepted_at')->count();
    }

    // to count user followers
    public function getFriends()
    {
        // return User::join('followables', 'followables.user_id', '=', 'users.id')
        //     ->where(function ($query) {
        //         $query->where('followables.user_id', $this->id)
        //             ->orWhere('followables.followable_id', $this->id);
        //     })
        //     ->whereNot('users.id', $this->id)
        //     ->whereNotNull('accepted_at')
        //     ->get();

        // $a = FollowModel::where('followable_type', 'App\Models\User')
        //     ->where(function ($query) {
        //         $query->orWhere('user_id', $this->id)
        //             ->orWhere('followable_id', $this->id);
        //     })
        //     ->whereNotNull('accepted_at')
        //     ->where('user_id',$this->id)
        //     ->get();


        $followersArray = $this->followers()->whereNotNull('accepted_at')->pluck('followables.user_id')->toArray();
        $followingsArray = $this->followings()->whereNotNull('accepted_at')->pluck('followables.followable_id')->toArray();
        $users = array_unique(array_merge($followersArray, $followingsArray));
        return User::whereIn('id',$users)->get();

    }

    // to count user followings
    public function getMyFollowingCountAttribute()
    {
        return $this->followings()->whereNotNull('accepted_at')->count();
    }

    // to count user followings
    public function isFollowingUser($user)
    {
        $isFollowing = FollowModel::where('user_id', $this->id)
            ->where('followable_id', $user->id)
            ->where('followable_type', 'App\Models\User')
            ->whereNotNull('accepted_at')
            ->first();
        if (is_null($isFollowing)) {
            $isFollowing = false;
        } else {
            $isFollowing = true;
        }

        return $isFollowing;
    }

    // to count user subscriber
    public function getMySubscribersCountAttribute()
    {
        return $this->subscribers()->count();
    }

    // to count user subscribeds
    public function getMySubscribedsCountAttribute()
    {
        return $this->subscriptions()->count();
    }
    // to count user subscribeds
    public function getMediaCountImagesAttribute()
    {
        return UserPost::withCount('media')->where('user_id', $this->id)->where('type', 'images')->pluck('media_count')->sum() ?? 0;
    }
    public function getMediaCountVideosAttribute()
    {
        return UserPost::withCount('media')->where('user_id', $this->id)->where('type', 'video')->pluck('media_count')->sum() ?? 0;
    }

    public function getMediaCountAudiosAttribute()
    {
        return UserPost::withCount('media')->where('user_id', $this->id)->where('type', 'audio')->pluck('media_count')->sum() ?? 0;
    }

    // to get user followers with pagination
    public function my_followers($pagination)
    {
        return $this->followers()->whereNotNull('accepted_at')->paginate($pagination);
    }

    // to get user followings with pagination
    public function my_followings($pagination)
    {
        return $this->followings()->whereNotNull('accepted_at')->paginate($pagination);
    }

    // to get user subscribers with pagination
    public function my_subscribers($pagination)
    {
        return $this->subscribers()->paginate($pagination);
    }

    // to get user subscriptions with pagination
    public function my_subscriptions($pagination)
    {
        return $this->subscriptions()->paginate($pagination);
    }

    // to get user followings
    public function getMyFollowingAttribute()
    {
        return $this->followings()->whereNotNull('accepted_at');
    }

    // to get user subscribers
    public function getMySubscribersAttribute()
    {
        return $this->subscribers;
    }

    // to get user subscribeds
    public function getMySubscribedsAttribute()
    {
        return $this->subscriptions;
    }

    // to get user allposts
    public function posts()
    {
        return $this->hasMany(UserPost::class)->withCount('likers', 'comments', 'media')->with('media', 'user', 'likers')->orderBy('created_at', 'desc');
    }

    // to get user free_posts
    public function free_posts($type)
    {
        return $this->hasMany(UserPost::class)->withCount('likers', 'comments', 'media')->where('is_paid', 0)->where('type', $type)->with('media')->orderBy('created_at', 'desc');
    }

    // to get user paid_posts
    public function paid_posts($type)
    {
        return $this->hasMany(UserPost::class)->withCount('likers', 'comments', 'media')->where('is_paid', 1)->where('type', $type)->with('media')->orderBy('created_at', 'desc');
    }

    //to get mutual follower
    public function getMutualFollowersCountAttribute()
    {
        $thisUserFollowersArray = $this->followers()->whereNotNull('accepted_at')->pluck('followables.user_id')->toArray();
        $authUserFollowersArray = auth()->user()->followers()->whereNotNull('accepted_at')->pluck('followables.user_id')->toArray();
        $mutual_followers = array_intersect($authUserFollowersArray, $thisUserFollowersArray);
        return count($mutual_followers) == 0 ? 'No ' : count($mutual_followers);
    }

    // to count user media
    public function count_media($type)
    {
        return UserPost::withCount('media')->where('user_id', $this->id)->where('type', $type)->pluck('media_count')->sum() ?? 0;
    }

    // to increase likes on runtime
    public function sendUserPostLikeNotification($data)
    {
        return $this->notify(new UserPostLikeNotification($data));
    }

    public function sendNotification($type, $data)
    {
        if ($type == 'post_like') {
            return $this->notify(new UserPostLikeNotification($data));
        }
        if ($type == 'post_comment') {
            return $this->notify(new UserPostCommentNotification($data));
        }
        if ($type == 'user_subscribe') {
            return $this->notify(new UserSubscribeNotification($data));
        }
        if ($type == 'user_follow_request') {
            return $this->notify(new UserFollowRequestNotification($data));
        }
        if ($type == 'user_follow_accept') {
            return $this->notify(new UserFollowAcceptNotification($data));
        }
        if ($type == 'comment_like') {
            return $this->notify(new UserCommentLikeNotification($data));
        }
        if ($type == 'post_uploaded') {
            return $this->notify(new UserPostUploadedNotification($data));
        }
        if ($type == 'post_updated') {
            return $this->notify(new UserPostUpdatedNotification($data));
        }
        if ($type == 'chat_room') {
            return $this->notify(new ChatNotification($data));
        }
    }

    public function userSubscribers()
    {
        $subscribers_id =  DB::select("SELECT user_id FROM `subscriptions`
        WHERE subscribable_id = ? ORDER BY created_at DESC LIMIT 6", [$this->id]);
        $mapped = Arr::map($subscribers_id, function ($value, $key) {
            return  $value->user_id;
        });
        //  return $mapped;

        $subscribers = User::with('media')->whereIn('id', $mapped)->get();
        // dd($subscribers);
        return $subscribers;
    }
}
