<?php

use App\Http\Controllers\AudioController;
use App\Http\Controllers\ChatMessageController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MyProfileController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SubscribeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPostController;
use App\Http\Controllers\VideoController;
use App\Models\User;
use App\Models\UserPost;
use App\Notifications\UserNotification;
use Illuminate\Http\File;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/image/{id}', function ($id) {

    return DB::table('followables as f1')
        ->LeftJoin('followables  AS f2', 'f1.followable_id', '=', 'f2.user_id')
        ->where('f1.user_id', 11)
        ->where('f2.followable_id', 11)
        ->get();

    return "SELECT f1.user_id,f1.followable_id,f2.user_id as 'f2_user_id',f2.followable_id  as 'f2_followable_id' FROM `followables` AS f1
    LEFT JOIN  `followables` AS f2
    ON f1.followable_id = f2.user_id
    where f1.user_id = 11 AND f2.followable_id = 11;";

    $path = BaseMedia::first()->getPath();

    $file = Storage::get($path);
    $type = Storage::mimeType($path);
    $response = response()->make($file, 200);
    $response->header("Content-Type", $type);
    return $response;


    dd('Event Run Successfully.');
});

Route::get('dbempty', function () {
    dd('database empty');

    DB::table('comments')->truncate();
    DB::table('followables')->truncate();
    DB::table('likes')->truncate();
    DB::table('media')->truncate();
    DB::table('notifications')->truncate();
    DB::table('oauth_access_tokens')->truncate();
    DB::table('users')->truncate();
    DB::table('password_resets')->truncate();
    DB::table('subscriptions')->truncate();
    DB::table('user_details')->truncate();
    DB::table('user_follower')->truncate();
    DB::table('user_friendship_groups')->truncate();
    DB::table('user_posts')->truncate();
});

Route::get('migrate', function () {
    // Artisan::call("migrate --path='./database/migrations/2022_08_08_163149_add_is_online_to_users.php'");
    Artisan::call("migrate");
    dd('migrate succesfull');
});

Route::get('qw/{for}', function ($for) {
    if ($for == 'start') {
        Artisan::call("queue:work ");
        dd('started');
    }
    if ($for == 'stop') {
        Artisan::call("queue:work --stop-when-empty");
        dd('stoped');
    }
    dd('migrate succesfull');
});

Route::get('update-uuid', function () {
    $posts =  UserPost::all();
    foreach ($posts as $post) {
        $post->update([
            'uuid' => Str::uuid()
        ]);
    }
    dd('updated');
});

Route::get('/t', function () {
    (User::find(5)->notify(new UserNotification('Has requested to follow you.', 'success', auth()->id())));
    event(new \App\Events\SendMessage());
    dd('Event Run Successfully.');
});

Route::get('/sk', function () {
    return view('frontend.pages.home');
});

Auth::routes(['verify' => true]);

// route for converting text into hash
Route::get('hash/{key}', function ($key) {
    return Hash::make($key);
});


//  frontend routes
Route::group(['as' => 'frontend.', 'middleware' => ['auth', 'verified']], function () {
    Route::get('/', [FrontendController::class, 'home'])->name('home');
    Route::get('processing-session', [MyProfileController::class, 'processSession'])->name('session-control');
    Route::get('/messages', [FrontendController::class, 'messages'])->name('messages');
    Route::get('live-chat', [FrontendController::class, 'liveChat'])->name('live-chat');
    Route::get('groups', [FrontendController::class, 'groups'])->name('groups');
    Route::get('members', [MemberController::class, 'members'])->name('members');
    Route::get('friends', [FrontendController::class, 'friends'])->name('friends');
    Route::get('birthdays', [FrontendController::class, 'birthdays'])->name('birthdays');
    Route::get('videos', [FrontendController::class, 'videos'])->name('videos');
    Route::get('audios', [FrontendController::class, 'audios'])->name('audios');
    Route::get('chatroom', [FrontendController::class, 'chatroom'])->name('chatroom');
    Route::get('photos', [FrontendController::class, 'photos'])->name('photos');
    Route::get('weathers', [FrontendController::class, 'weather'])->name('weathers');
    Route::get('marketplaces', [FrontendController::class, 'marketplaces'])->name('marketplaces');
    Route::get('my-profile', [MyProfileController::class, 'myProfile'])->name('my-profile');
    Route::get('settings', [FrontendController::class, 'settings'])->name('settings');
    Route::get('privacy', [FrontendController::class, 'privacy'])->name('privacy');
    Route::get('help-and-support', [FrontendController::class, 'helpAndSupport'])->name('help-and-support');
    Route::get('member-subscriptions', [FrontendController::class, 'memberSubscription'])->name('member-subscriptions');
    Route::get('single-member', [FrontendController::class, 'singleMember'])->name('single.member');
    Route::get('posts/{userpost}', [FrontendController::class, 'singlePost'])->name('post.single');
    Route::view('/uicheck', 'frontend.pages.global-search');
    // Route::get('search/hint',[FrontendController::class, 'searchHint'])->name('search.hint');
    Route::get('profile-page-trigger', [MyProfileController::class, 'ProfilePageTrigger'])->name('profile.page.trigger');
    Route::get('comment-render', [FrontendController::class, 'commentRender'])->name('comment.render');
    Route::view('/single/meesage', 'frontend.pages.single-message')->name('single.message');

    // notification routes
    Route::get('notifications', [FrontendController::class, 'notifications'])->name('notifications');
    Route::get('notification-render', [FrontendController::class, 'notificationRender'])->name('notification.render');
    Route::get('notification/markAsRead', [FrontendController::class, 'notificationMarkAsRead'])->name('notification.markAsRead');
    Route::get('notification/search', [FrontendController::class, 'notificationSearch'])->name('notification.search');
    Route::post('notifications/follow-request', [FollowController::class, 'notificationFollowRequest'])->name('notifications.follow-request');



    // home ajax route
    Route::get('render/home', [FrontendController::class, 'renderHome'])->name('render.home');
    // post route
    Route::post('media/upload', [MyProfileController::class, 'uploadAvatar'])->name('upload.media');
    Route::post('setting/update/{user}', [MyProfileController::class, 'updateSetting'])->name('setting.update');
    Route::post('post/store', [UserPostController::class, 'store'])->name('post.store');
    Route::post('get/total-count', [MyProfileController::class, 'getTotalCount'])->name('get.count');

    // global search
    Route::get('global-search', [SearchController::class, 'globalSearch'])->name('global.search');

    //  search routes
    Route::any('follower-search', [FollowController::class, 'followerSearch'])->name('search.follower');
    Route::any('subscriber-search', [SubscribeController::class, 'subscriberSearch'])->name('search.subscriber');
    Route::any('audio-search', [AudioController::class, 'audioSearch'])->name('search.audio');
    Route::any('video-search', [VideoController::class, 'videoSearch'])->name('search.video');
    Route::any('photo-search', [PhotoController::class, 'photoSearch'])->name('search.photo');
    Route::any('online-search', [SearchController::class, 'onlineSearch'])->name('search.online');
    Route::any('allphotos-search', [PhotoController::class, 'allPhotosSearch'])->name('search.allphotos');
    Route::any('allaudios-search', [AudioController::class, 'allAudiosSearch'])->name('search.allaudios');
    Route::any('allvideos-search', [VideoController::class, 'allVideosSearch'])->name('search.allvideos');
    Route::any('allmembers-search', [MemberController::class, 'allMembersSearch'])->name('search.allmembers');
    Route::get('render/single-post', [UserPostController::class, 'singlePostRender'])->name('single.post.render');
    Route::any('render/component', [FrontendController::class, 'renderComponent'])->name('render.component');
    Route::post('payment/method', [FrontendController::class, 'paymentMethod'])->name('payment.method');
    Route::any('/render/post/{post:id}/likes', [UserPostController::class, 'postLikesRender'])->name('post.like.render');
    Route::any('/render/post/{post:id}/shares', [UserPostController::class, 'postSharesRender'])->name('post.share.render');
    Route::any('/render/post/{post:uuid}/previous-comments', [UserPostController::class, 'postPreviousCommentsRender'])->name('post.previous.comment.render');
    Route::any('/render/comment/{comment:id}/likes', [UserPostController::class, 'commentLikesRender'])->name('post.like.render');


    // like route

    Route::get('/post/share', [UserPostController::class, 'viewSharePost'])->name('view.share.post');
    Route::post('/share/post/store', [UserPostController::class, 'sharePost'])->name('share.post');
    Route::post('/like-post', [UserPostController::class, 'likePost'])->name('like.post');
    Route::post('/like-comment', [UserPostController::class, 'likeComment'])->name('like.comment');
    Route::post('/comment-post', [UserPostController::class, 'commentPost'])->name('comment.post');

    // post edit route



    // dynamic route
    Route::get('send-follow-request/{user:name}', [FollowController::class, 'sendFollowRequest'])->name('send.follow-request');
    Route::get('remove-follow-request/{user:name}', [FollowController::class, 'removeFollowRequest'])->name('remove.follow-request');
    Route::get('block/{user:name}', [MyProfileController::class, 'subscribeUser'])->name('block.user');
    Route::get('report/user', [MyProfileController::class, 'reportUser'])->name('report.user');
    Route::get('subscribe/{user:name}', [SubscribeController::class, 'subscribeUser'])->name('subscribe');
    Route::get('un-subscribe/{user:name}', [SubscribeController::class, 'unSubscribeUser'])->name('unsubscribe');
    Route::get('decline-follow-request/{user:name}', [FollowController::class, 'declineFollowRequest'])->name('decline.follow-request');
    Route::get('accept-follow-request/{user:name}', [FollowController::class, 'acceptFollowRequest'])->name('accept.follow-request');
    Route::get('profile/{user:name}', [MyProfileController::class, 'singleProfile'])->name('single.profile');


    Route::get('chat', [ChatMessageController::class, 'index'])->name('chat.list');
    Route::post('chat/store', [ChatMessageController::class, 'store'])->name('chat.store');
    Route::get('chat', [ChatMessageController::class, 'index'])->name('chat.get.single');
    Route::get('chat/{username?}', [ChatMessageController::class, 'edit'])->name('chat.get.single');
    Route::post('chat/markAsSeen', [ChatMessageController::class, 'markAsSeen'])->name('chat.markasseen');
    Route::post('chat/sendNotification', [ChatMessageController::class, 'sendNotification'])->name('chat.send.notification');


    Route::resource('users', UserController::class);

    // Route::get('follow/{user:name}', [FollowController::class, 'sendFollowRequest'])->name('send.follow-request');
    Route::get('/post/{user:name}/{post:uuid}', [UserPostController::class, 'singlePost'])->name('single.post');
    Route::get('/post/{user:name}/{post:uuid}/edit', [UserPostController::class, 'edit'])->name('edit.post');
    Route::post('/post/{user:name}/{post:uuid}/update', [UserPostController::class, 'update'])->name('update.post');
    Route::get('/post/{user:name}/{post:uuid}/report', [UserPostController::class, 'report'])->name('report.post');
    Route::post('/{user:name}/{post:uuid}/delete', [UserPostController::class, 'destroy'])->name('post.delete');

    Route::get('/temUploadTest', function () {
        return view('frontend.pages.temUploadTest');
    });
    Route::post('/temp-upload', [UserPostController::class, 'tempUpload'])->name('temp-upload');
});
