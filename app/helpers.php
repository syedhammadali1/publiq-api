<?php

use App\Models\Notification;
use App\Models\TempImage;
use App\Models\User;
use App\Models\UserPost;
use Carbon\Carbon;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Intervention\Image\File;
use Overtrue\LaravelSubscribe\Subscription;

/**
 * success response method.
 *
 * @return \Illuminate\Http\Response
 */
function sendResponse($result, $message)
{
    $response = [
        'success' => true,
        'data'    => $result,
        'message' => $message,
    ];


    return response()->json($response, 200);
}


/**
 * return error response.
 *
 * @return \Illuminate\Http\Response
 */
function sendError($error, $errorMessages = [], $code = 404)
{
    $response = [
        'success' => false,
        'message' => $error,
    ];

    if (!empty($errorMessages)) {
        $response['data'] = $errorMessages;
    }

    return response()->json($response, $code);
}


function createUserName($email)
{
    $str = $email;
    $characters = str_replace([' ', '.com', '@'], '', $str);
    $charactersLength = strlen($characters);
    $username = '';
    do {
        $username = '';
        for ($i = 0; $i < 4; $i++) {
            $username .= $characters[rand(0, $charactersLength - 1)];
        }
        for ($i = 0; $i < 2; $i++) {
            $username .= rand(0, 9);
        }
    } while (!is_null(User::where('name', $username)->first()));
}



/**
 * Get file content from base64 string.
 *
 * @param $data
 * @return string
 */
function getBase64Content($data)
{
    list($type, $data) = explode(';', $data);
    list(, $data) = explode(',', $data);
    $data = base64_decode($data);

    return $data;
}

/**
 * Saves temporary file from base64 string.
 *
 * @param $filename
 * @param $data
 * @return File
 */
function saveFileFromBase64($filename, $image)
{
    list($type, $image) = explode(';', $image);
    list(, $image) = explode(',', $image);
    $image = base64_decode($image);
    file_put_contents($filename, $image);
    $file = new File($filename);

    return $file;
}


function getLastestSubscribers($user)
{
    return $user->subscribers;
}



function getUserAttribute($id, $for)
{
    $user = User::find($id);
    if ($for == 'img') {
        return $user->avatar;
    }
    if ($for == 'name') {
        return $user->detail->full_name;
    }
    if ($for == 'username') {
        return $user->name;
    }
    if ($for == 'mutual-follow') {
        return $user->mutual_followers_count;
    }
}

function whichUserIsBeingViewed()
{
    $url = Session::get('viewedUser');
    if ($url['url'] == 'single') {
        $user = User::find($url['id']);
    }
    if ($url['url'] == 'my') {
        $user = auth()->user();
    }
    return $user;
}

// to get most liked free photos
function mostLikedFreePhotos()
{
    return UserPost::with('media', 'user.media')
        ->withCount('likers', 'comments', 'media')
        ->where('type', 'images')
        ->where('is_paid', 0)
        ->orderByDesc('likers_count');
    // return UserPost::with('media', 'user')->where('type', 'images')->where('is_paid', 0)->where('count_likes', '>', '0')->limit(4)->orderBy('count_likes', 'desc')->get();
}

// to get most liked paid photos
function mostLikedPaidPhotos()
{
    return UserPost::with('media', 'user.media')
        ->withCount('likers', 'comments', 'media')
        ->where('type', 'images')
        ->where('is_paid', 1)
        ->orderByDesc('likers_count');
    // return UserPost::with('media', 'user')->where('type', 'images')->where('is_paid', 1)->where('count_likes', '>', '0')->limit(4)->orderBy('count_likes', 'desc')->get();
}

// to get most liked free audios
function mostLikedFreeAudios()
{
    return UserPost::with('media', 'user')
        ->withCount('likers', 'comments')
        ->where('type', 'audio')
        ->where('is_paid', 0)
        ->orderByDesc('likers_count');
    // return UserPost::with('media', 'user')->where('type', 'audio')->where('is_paid', 0)->where('count_likes', '>', '0')->limit(4)->orderBy('count_likes', 'desc')->get();
}

// to get most liked paid audios
function mostLikedPaidAudios()
{
    return UserPost::with('media', 'user')
        ->withCount('likers', 'comments')
        ->where('type', 'audio')
        ->where('is_paid', 1)
        ->orderByDesc('likers_count');
    // return  UserPost::with('media', 'user')->where('type', 'audio')->where('is_paid', 1)->where('count_likes', '>', '0')->limit(4)->orderBy('count_likes', 'desc')->get();
}

// to get most liked paid videos
function mostLikedPaidVideos()
{
    return UserPost::with('media', 'user')
        ->withCount('likers')
        ->where('type', 'video')
        ->where('is_paid', 1)
        ->orderByDesc('likers_count');
    // return UserPost::with('media', 'user')->where('type', 'video')->where('is_paid', 1)->where('count_likes', '>', '0')->limit(4)->orderBy('count_likes', 'desc')->get();
}
function mostLikedFreeVideos()
{
    return UserPost::with('media', 'user')
        ->withCount('likers')
        ->where('type', 'video')
        ->where('is_paid', 0)
        ->orderByDesc('likers_count');
    // return UserPost::with('media', 'user')->where('type', 'video')->where('is_paid', 1)->where('count_likes', '>', '0')->limit(4)->orderBy('count_likes', 'desc')->get();
}

// to get all following people posts
function timeLinePosts()
{
    $authUser = auth()->user();
    $following = $authUser->followings()->whereNotNull('accepted_at')->pluck('followable_id');
    $subscriptions = $authUser->subscriptions()->get()->pluck('subscribable_id');

    $timeLinePosts = UserPost::withCount('comments', 'likers', 'media')
        ->with('media', 'user.media', 'comments.user.media')
        ->where('user_id', $authUser->id)
        ->orWhere(function ($query) use ($following) {
            $query->whereIn('user_id', $following)->where('is_paid', 0);
        })
        ->orWhere(function ($query) use ($subscriptions) {
            $query->whereIn('user_id', $subscriptions)->where('is_paid', 1);
        })
        ->orderBy('created_at', 'DESC')
        ->paginate(8);

    return $timeLinePosts->isEmpty() ? suggestedPost('all', $following, $subscriptions, $authUser) : $timeLinePosts;
}

function suggestedPost($for, $following = null, $subscriptions = null, $authUser = null)
{
    if (is_null($authUser)) {
        $authUser = auth()->user();
    }
    if (is_null($following)) {
        $following = $authUser->followings()->whereNotNull('accepted_at')->pluck('followable_id');
    }
    if (is_null($subscriptions)) {
        $subscriptions = $authUser->subscriptions()->get()->pluck('subscribable_id');
    }
    $notToFind = array_unique(array_merge($following->toArray(), $subscriptions->toArray()));
    array_push($notToFind, $authUser->id);
    if ($for == 'all') {
        $suggestedPost = UserPost::withCount('comments', 'likers', 'media')
            ->with('media', 'user.media', 'comments.user.media', 'user.detail')
            ->whereNotIn('user_id', $notToFind)
            ->orderBy('created_at', 'DESC')
            ->paginate(8);
        $suggestedPost->each(function ($post) {
            $post->setAttribute('isSuggested', true);
        });
        return $suggestedPost;
    }
    if ($for == 'single') {
        $suggestedPost = UserPost::withCount('comments', 'likers', 'media')
            ->with('media', 'user.media', 'comments.user.media')
            ->whereNotIn('user_id', $notToFind)
            ->inRandomOrder()
            ->first();
        if ($suggestedPost != null) {

            $suggestedPost->setAttribute('isSuggested', true);
        };

        return $suggestedPost;
    }
}

// to get users sorted by subscribers
function mostPopularPubliqars()
{
    // $users = User::with('media')->limit(6)->get();

    // $sortedPubliqer = $users->sortByDesc(function ($user, $key) {
    //     return $user->subscribers()->count();
    // });

    // $mostPopularPubliqars = $sortedPubliqer->values();

    $mostPopularPubliqars = User::withCount('subscribers')
        ->with('media')
        ->whereNot('id', auth()->id())
        ->whereNotNull('email_verified_at')
        ->orderByDesc('subscribers_count')
        ->limit(6)
        ->get();

    return $mostPopularPubliqars;
}

// to get users sorted by followers
function suggestedPeoples()
{
    $users = User::withCount('subscribers')
        ->with('media', 'detail')
        ->whereNot('id', auth()->id())
        ->whereNotIn('id', auth()->user()->subscriptions->pluck('subscribable_id'))
        ->whereNotIn('id', auth()->user()->followings->pluck('followable_id'))
        ->whereNotNull('email_verified_at')
        ->orderByDesc('subscribers_count')
        ->limit(6)
        ->get();

    // dd(auth()->user()->followings->pluck('followable_id'));
    $suggestedPeoples = $users->sortByDesc(function ($user, $key) {
        return $user->followers()->whereNotNull('accepted_at')->count();
    });

    $suggestedPeoples = $suggestedPeoples->values();
    return $suggestedPeoples;
}

function sessionStartUpProfileAjax()
{
    $for = [
        'timeline', 'Followers', 'Subscriber', 'photos', 'Audios', 'Videos'
    ];
    foreach ($for as $value) {
        session()->put('profileAjax' . $value, 1);
    }
}

function setWhichUserIsBeingViewed($DOMURL)
{
    if (str_contains($DOMURL, '/profile' . '/')) {
        if (str_contains($DOMURL, 'my-profile')) {
            if (session()->get('viewedUser')['id'] != auth()->id()) {
                session()->put('viewedUser', [
                    'url' => 'my',
                    'id' => auth()->id()
                ]);
            }
        } else {
            $name = str_replace(['#'], '', str_replace('/profile' . '/', '', $DOMURL));
            $user_id = User::where('name', $name)->pluck('id')->first();
            session()->put('viewedUser', [
                'url' => 'single',
                'id' => $user_id
            ]);
        }
    } else {
        session()->put('viewedUser', [
            'url' => 'my',
            'id' => auth()->id()
        ]);
    }
}

function getSessionProfileAjax($for, $view)
{
    if (session()->get('profileAjax' . $for) == 1) {
        session()->get('profileAjax' . $for) == 2
            ? null
            : session()->put('profileAjax' . $for, 2);
        return view($view, [
            'user' => whichUserIsBeingViewed()
        ]);
    } else {
        return view($view, [
            'user' => whichUserIsBeingViewed(),
            'noscript' => true
        ]);
    }
}
function getUserNotificationsCount($id, $type)
{
    if ($type == 'notification') {
        return Notification::where('notifiable_id', $id)
            ->where('type', '!=', 'App\Notifications\UserFollowRequestNotification')
            ->where('type', '!=', 'App\Notifications\ChatNotification')
            ->where('read_at', null)
            ->count();
    }
    if ($type == 'follow') {
        return Notification::where('notifiable_id', $id)
            ->where('type', 'App\Notifications\UserFollowRequestNotification')
            ->where('read_at', null)
            ->count();
    }
    if ($type == 'message') {
        return Notification::where('notifiable_id', $id)
            ->where('type', 'App\Notifications\ChatNotification')
            ->where('read_at', null)
            ->count();
    }
}

function getAuthUnreadNotification()
{
    $notification = Notification::where('notifiable_id', auth()->id())
        ->where('type', '!=', 'App\Notifications\UserFollowRequestNotification')
        ->where('type', '!=', 'App\Notifications\ChatNotification')
        ->where('read_at', null)
        ->orderByDesc('created_at')
        ->get();

    if (count($notification) > 0) {
        return $notification;
    } else {
        return Notification::where('notifiable_id', auth()->id())
            ->where('type', '!=', 'App\Notifications\UserFollowRequestNotification')
            ->where('type', '!=', 'App\Notifications\ChatNotification')
            ->latest()
            ->limit(5)
            ->get();
    }
}

function hasAuthUnreadNotification()
{
    $notification = Notification::where('notifiable_id', auth()->id())
        ->where('type', '!=', 'App\Notifications\UserFollowRequestNotification')
        ->where('type', '!=', 'App\Notifications\ChatNotification')
        ->where('read_at', null)
        ->get();

    if (count($notification) > 0) {
        return true;
    } else {
        return false;
    }
}

function getAuthUnreadFollowNotification()
{
    return Notification::where('notifiable_id', auth()->id())
        ->where('type', 'App\Notifications\UserFollowRequestNotification')
        ->where('read_at', null)
        ->orderByDesc('created_at')
        ->paginate(8);
}

function getAuthUnreadMessageNotification()
{
    return Notification::where('notifiable_id', auth()->id())
        ->where('type', 'App\Notifications\ChatNotification')
        ->orderByDesc('created_at')
        ->paginate(8);
}

function getAuthAllNotification()
{
    return Notification::where('notifiable_id', auth()->id())
        ->where('type', '!=', 'App\Notifications\UserFollowRequestNotification')
        ->where('type', '!=', 'App\Notifications\ChatNotification')
        ->orderByDesc('created_at')
        ->paginate(8);
}

function userSubscribableArray()
{
    $subscribable_id =  DB::select("SELECT subscribable_id FROM `subscriptions`
    WHERE user_id = ?", [auth()->id()]);

    $userSubscribableArray = Arr::map($subscribable_id, function ($value, $key) {
        return  $value->subscribable_id;
    });

    return $userSubscribableArray;
}

function doesFileExists($file = null, $url = null)
{
    return $url;

    // if (is_object($file) && file_exists($file->getPath())) {

    //     return $url;
    // } else {
    //     // return 'https://upload.wikimedia.org/wikipedia/commons/thumb/a/ac/No_image_available.svg/1024px-No_image_available.svg.png';

    //     return asset('frontend/assets/images/nomediaavalaible.jpg');
    // }
    // return $file;
}

function getMostLikedVideo()
{
    return UserPost::with('media')
        ->withCount('likers', 'media')
        ->where('type', 'video')
        ->where('is_paid', 0)
        ->orderByDesc('likers_count')
        ->first();
}

function getUserIsSubscriberByAnotherUser($user1, $user2)
{
    $hasSub = Subscription::where('user_id', $user1->id)
        ->where('subscribable_id', $user2->id)
        ->where('subscribable_type', 'App\Models\User')
        ->first();
    if (is_null($hasSub)) {
        $hasSub = false;
    } else {
        $hasSub = true;
    }
    return $user1->setAttribute('hasSubscribed', $hasSub);
}

function getUserIsFollowingAnotherUser($user1, $user2)
{
    $isFollowing = $user1->isFollowing($user2);
    return $user1->setAttribute('isFollowingTheUser', $isFollowing);
}

function updatedUsersForSingleProfile($authUser, $user)
{
    getUserIsSubscriberByAnotherUser($authUser, $user);
    getUserIsFollowingAnotherUser($authUser, $user);
    $user->attachFollowStatus($authUser);
    $authUser->setAttribute('hasRequestedToFollow', $authUser->hasRequestedToFollow($user));

    $user->append([
        'my_followers_count',
        'my_following_count',
        'my_subscribers_count',
        'my_subscribeds_count',
        'media_count_images',
        'media_count_video',
        'media_count_audio'
    ]);
    getUserIsFollowingAnotherUser($user, $authUser);
    $user->setAttribute('hasRequestedToFollow', $user->hasRequestedToFollow($authUser));
}

function createFileName($file, $for = null)
{
    if ($for == null) {
        $completeFileName = $file->getClientOriginalName();
        $fileNameOnly = pathinfo($completeFileName, PATHINFO_FILENAME);
        return  \Illuminate\Support\Str::random(30) . time() .  date('YmdHis') . '.' . $file->getClientOriginalExtension();
    } else {
        return  \Illuminate\Support\Str::random(30) . time() .  date('YmdHis') . '.' . explode('/', mime_content_type($file))[1];;
    }
}

function getFeedPostAuthUser($post)
{
    $authUser = User::with('media', 'detail')->find(auth()->id());
    getUserIsSubscriberByAnotherUser($authUser, $post->user);
    getUserIsFollowingAnotherUser($authUser, $post->user);
    $authUser->setAttribute('hasLiked', $post->isLikedBy($authUser));
    return $authUser;
}

function uploadPost($data)
{
    $post = UserPost::create($data);
    if ($data['type'] == 'images') {
        if (count($data['images']) > 0) {
            foreach ($data['images'] as $key => $file) {
                if ($data['is_paid'] == 1) {
                    $post->addMedia($file)->usingFileName(createFileName($file))->toMediaCollection('paidImage', 's3');
                } else {
                    $post->addMedia($file)->usingFileName(createFileName($file))->toMediaCollection('image', 's3');
                }
            }
        }
    }
    if ($data['type'] == 'video') {
        $post->addMedia($data['video'])->usingFileName(createFileName($data['video']))->toMediaCollection('video', 's3');
    }
    if ($data['type'] == 'audio') {
        $post->addMedia($data['audio'])->usingFileName(createFileName($data['audio']))->toMediaCollection('audio', 's3');
    }
}


function deleteTempImages()
{
    function deleteAble($item)
    {
        $item->clearMediaCollection();
        $item->delete();
    }
    TempImage::with('media')
        ->whereDate('created_at', '<', Carbon::now()->toDateString())
        ->get()
        ->map(fn ($item) => deleteAble($item));
}
