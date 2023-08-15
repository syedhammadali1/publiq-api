<?php

namespace App\Http\Controllers;

use App\Jobs\UploadAndUpdateNotificationDeleteQueue;
use App\Models\Comment;
use App\Models\Notification;
use App\Models\User;
use App\Models\UserPost;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\DocBlock\Tags\Uses;

class FrontendController extends Controller
{
    public function home(Request $request)
    {
        session()->put('viewedUser', [
            'url' => 'my',
            'id' => auth()->id()
        ]);

        return view('frontend.pages.home');
    }

    public function renderHome(Request $request)
    {
        if ($request->page) {
            if ($request->suggestions) {
                $user_posts = suggestedPost('all');
                $html = view('components.Home.NewsFeed.foreachFeedPost', compact(['user_posts']))->render();
                return response()->json([
                    'html' => $html,
                    'lastPage' => (string) $user_posts->lastPage(),
                ]);
            }
            $user_posts = timeLinePosts();
            $suggested_Post = suggestedPost('single');
            $html = view('components.Home.NewsFeed.foreachFeedPost', compact(['user_posts']))->render();
            $suggestedPostHtml = null;

            if (!is_null($suggested_Post)) {
                $suggestedPostHtml = view('components.Home.NewsFeed.feedPost', ['user_post' => $suggested_Post])->render();
            }
            return response()->json([
                'html' => $html,
                'suggestedPostHtml' => $suggestedPostHtml,
                'lastPage' => (string) $user_posts->lastPage(),
                'showSuggestion' => $request->page == $user_posts->lastPage() ? true : false
            ]);
        }
        setWhichUserIsBeingViewed($request->DOMURL);
        if ($request->render_for == 'widgetViewProfile') {
            $user = User::with('media', 'detail')
                ->find(session()->get('viewedUser')['id'])
                ->append(['media_count_images', 'media_count_video', 'media_count_audio']);
            return view('components.Home.widgetViewProfile', [
                'user' => $user,
                'subscriberUsers' => $user->userSubscribers()
            ]);
        }

        if ($request->render_for == 'widgetLikePhoto') {
            if ($request->isPaid == 1) {
                $mostLikedPaidPhotos = mostLikedPaidPhotos()->limit(4)->get();
                return view('components.Home.widgetLikePhoto', [
                    'heading' => 'Most Liked Premium Photos',
                    'mostLikedPhotos' => $mostLikedPaidPhotos,
                    'isPaid' => 1,
                ]);
            }

            if ($request->isPaid == 0) {
                $mostLikedFreePhotos = mostLikedFreePhotos()->limit(4)->get();
                return view('components.Home.widgetLikePhoto', [
                    'heading' => 'Most Liked Free Photos',
                    'mostLikedPhotos' => $mostLikedFreePhotos,
                    'isPaid' => 0,
                ]);
            }
        }

        if ($request->render_for == 'widgetFollowSuggestion') {
            $suggestedPeoples = suggestedPeoples();
            return view('components.Home.widgetFollowSuggestion', [
                'suggestedPeoples' => $suggestedPeoples,
            ]);
        }

        if ($request->render_for == 'widgetAudio') {
            if ($request->isPaid == 1) {
                $mostLikedPaidAudios = mostLikedPaidAudios()->limit(4)->get();
                $userSubscribableArray = userSubscribableArray();
                return  view('components.Home.widgetAudio', [
                    'heading' => 'Most Liked Premium Audios',
                    'mostLikedAudios' => $mostLikedPaidAudios,
                    'userSubscribableArray' => $userSubscribableArray,
                    'isPaid' => 1,
                ]);
            }

            if ($request->isPaid == 0) {
                $mostLikedFreeAudios = mostLikedFreeAudios()->limit(4)->get();
                return view('components.Home.widgetAudio', [
                    'heading' => 'Most Liked Free Audios',
                    'mostLikedAudios' => $mostLikedFreeAudios,
                    'isPaid' => 0,
                ]);
            }
        }

        if ($request->render_for == 'widgetWatchVideo') {
            $getMostLikedVideo = getMostLikedVideo();
            return view('components.Home.widgetWatchVideo', [
                'getMostLikedVideo' => $getMostLikedVideo,
            ]);
        }

        if ($request->render_for == 'widgetVideo') {
            $mostLikedPaidVideos = mostLikedPaidVideos()->limit(4)->get();
            return view('components.Home.widgetVideo', [
                'heading' => 'Most Liked Premium Videos',
                'mostLikedVideos' => $mostLikedPaidVideos,
            ]);
        }

        if ($request->render_for == 'newsFeed') {
            $timeLinePosts = timeLinePosts();
            $mostPopularPubliqars = mostPopularPubliqars();
            return view('components.Home.NewsFeed.newsFeed', [
                'mostPopularPubliqars' => $mostPopularPubliqars,
                'user_posts' => $timeLinePosts,
                'newsFeed' => 'News Feed',
            ]);
        }
        if ($request->render_for == 'widgetAdvertisement') {
            return view('components.Home.widgetAdvertisement');
        }
    }

    // to show notification page
    public function notifications()
    {
        // dd(UserPost::find(259)->getFirstMediaUrl('paidImage'));

        return view('frontend.pages.notifications');
    }

    // to show messages page
    public function messages()
    {

        return view('frontend.pages.messages');
    }

    // extra theme pages
    public function liveChat()
    {
        return view('frontend.pages.live-chat');
    }

    // to show friends page
    public function friends()
    {
        return view('frontend.pages.friends');
    }


    public function video()
    {
        return view('frontend.pages.video');
    }

    // extra theme pages
    public function weather()
    {
        return view('frontend.pages.weather');
    }
    // extra theme pages
    public function marketplace()
    {
        return view('frontend.pages.marketplace');
    }

    public function settings()
    {
        return view('frontend.pages.setting');
    }

    // extra theme pages
    public function privacy()
    {
        return view('frontend.pages.privacy');
    }

    // extra theme pages
    public function helpAndSupport()
    {
        return view('frontend.pages.help-and-support');
    }



    // to show payment page
    public function memberSubscription()
    {
        return view('frontend.pages.member-subscription');
    }

    // to show single member profile
    public function singleMember()
    {
        return view('frontend.pages.Singlememberfriend');
    }

    public function chatroom()
    {
        return view('frontend.pages.chatroom.chatroom');
    }

    // to show all photos page
    public function photos()
    {
        // to get photos sorted by likes
        $mostLikedFreePhotos = mostLikedFreePhotos()
            ->paginate(8);


        $mostLikedPaidPhotos = mostLikedPaidPhotos()
            ->paginate(8);
        // dd($mostLikedFreePhotos, $mostLikedPaidPhotos);
        return view('frontend.pages.photo', compact('mostLikedFreePhotos', 'mostLikedPaidPhotos'));
    }

    // to show all videos page
    public function videos()
    {
        $mostLikedFreeVideos = mostLikedFreeVideos()
            ->paginate(8);

        $mostLikedPaidVideos = mostLikedPaidVideos()
            ->paginate(8);
        return view('frontend.pages.video', compact('mostLikedFreeVideos', 'mostLikedPaidVideos'));
    }

    // to show all audios page
    public function audios()
    {
        // to get audios sorted by likes
        $mostLikedFreeAudios = mostLikedFreeAudios()
            ->paginate(8);
        $mostLikedPaidAudios = mostLikedPaidAudios()
            ->paginate(8);

        return view('frontend.pages.audios', compact('mostLikedFreeAudios', 'mostLikedPaidAudios'));
    }

    // to show single post page
    public function singlePost(UserPost $userpost)
    {
        return view('frontend.pages.single-post', compact('userpost'));
    }

    public function notificationMarkAsRead(Request $request)
    {
        if (!$request->foruse) {
            return response()->json([
                'success' => false
            ]);
        }
        if ($request->foruse == 'notification') {
            $notification = Notification::where('notifiable_id', auth()->id())
                ->where('type', '!=', 'App\Notifications\UserFollowRequestNotification')
                ->where('type', '!=', 'App\Notifications\ChatNotification')
                ->where('read_at', null)
                ->get();
            $UploadOrUpdatePostNotification = Notification::where('notifiable_id', auth()->id())
                ->where(function ($query) {
                    $query->orWhere('type', 'App\Notifications\UserPostUpdatedNotification')
                        ->orWhere('type', 'App\Notifications\UserPostUploadedNotification');
                })
                ->where('read_at', null)
                ->pluck('id')->toArray();

            if (!empty($UploadOrUpdatePostNotification)) {
                $on = \Carbon\Carbon::now()->addMinutes(1);
                dispatch(new UploadAndUpdateNotificationDeleteQueue($UploadOrUpdatePostNotification))->delay($on);
            }
        }

        if ($request->foruse == 'message') {
            $notification = Notification::where('notifiable_id', auth()->id())
                ->where('type', 'App\Notifications\ChatNotification')
                ->where('read_at', null)
                ->get();
        }


        if (count($notification) > 0) {
            collect($notification)->map(function ($noti) {
                return $noti->markAsRead();
            });
            return response()->json([
                'message' => 'markAsRead'
            ]);
        }
    }

    public function commentRender(Request $request)
    {
        try {
            $comment = Comment::find($request->comment_id);
            return view('components.Home.NewsFeed.singleComment', [
                'comment' => $comment
            ]);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function renderComponent(Request $request)
    {
        if ($request->comp_for == 'payment') {
            $user = User::where('name', $request->user)->first();

            return view('components.Payment.payment', compact('user'));
            // $array = [
            //     'heading' => 'Payment Succesfull',
            //     'content' => 'You have sccuessfully subscribe ' . $user->full_name,
            //     'username' =>  $user->name
            // ];

            // return view('components.sub-components.successPopup', ['array' => $array]);
        }
        if ($request->comp_for == 'report-user') {
            $user = User::where('name', $request->user)->first();
            return view('components.MyProfile.SubPages.reportUserForm', compact('user'));
        }
        if ($request->comp_for == 'report-post') {
            $post = UserPost::with('user')->where('uuid', $request->post)->first();
            return view('components.MyProfile.SubPages.reportPostForm', compact('post'));
        }
        if ($request->comp_for == 'follow-req-accept') {
            $user = User::find($request->user);
            $authUser = auth()->user();
            updatedUsersForSingleProfile($authUser, $user);
            return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
        }
        if ($request->comp_for == 'feed-post') {
            $user_post = UserPost::withCount('likers', 'comments', 'media')->with('media', 'user')->find($request->post);
            return response()->json([
                'html' =>  view('components.Home.NewsFeed.feedPost', compact('user_post'))->render(),
                'uuid' =>  $user_post->uuid
            ]);
        }
    }

    public function paymentMethod(Request $request)
    {
        if ($request->for == 'subscribePayment') {
            $validatedData = $request->validate([
                'card_name' => ['required', 'regex:/^[a-z]+$/i'],
                'card_no' => ['required', 'min:15'],
                'expire_at' => ['required'],
                'cvc' => ['required', 'max:3'],
                'country' => ['required'],
                'user' => ['required'],
            ]);
            $user = User::where('name', $request->user)->first();
            $array = [
                'should_redirect' => true,
                'redirect_path' => route('frontend.single.profile', $user),
                'heading' => 'Payment Succesfull',
                'content' => 'You have sccuessfully subscribe ' . $user->full_name,
                'redirect_time' => 3000
            ];
            $html = (new SubscribeController)->subscribeUser($user)->render();
            $successHtml = view('components.sub-components.successPopup', ['array' => $array])->render();
            return response()->json([
                'html' => $html,
                'successHtml' => $successHtml
            ]);
        }
    }

    public function notificationRender(Request $request)
    {
        $notification = Notification::find($request->notification_id);
        if (!is_null($notification)) {
            if ($notification->type == 'App\Notifications\UserPostLikeNotification') {
                return view('components.sub-components.notifications.type.postLike', [
                    'notification' => $notification,
                    'notification_page' => false,
                    'new' => true,
                ]);
            }
            if ($notification->type == 'App\Notifications\UserPostCommentNotification') {
                return view('components.sub-components.notifications.type.postComment', [
                    'notification' => $notification,
                    'new' => true,
                    'notification_page' => false,
                ]);
            }
            if ($notification->type == 'App\Notifications\UserSubscribeNotification') {
                return view('components.sub-components.notifications.type.userSubscribe', [
                    'notification' => $notification,
                    'new' => true,
                    'notification_page' => false,
                ]);
            }
            if ($notification->type == 'App\Notifications\UserFollowRequestNotification') {
                return view('components.sub-components.notifications.type.userFollowRequest', [
                    'notification' => $notification,
                    'new' => true,
                    'notification_page' => false,
                ]);
            }
            if ($notification->type == 'App\Notifications\UserFollowAcceptNotification') {
                return view('components.sub-components.notifications.type.userFollowAccept', [
                    'notification' => $notification,
                    'new' => true,
                    'notification_page' => false,
                ]);
            }
            if ($notification->type == 'App\Notifications\UserCommentLikeNotification') {
                return view('components.sub-components.notifications.type.commentLike', [
                    'notification' => $notification,
                    'new' => true,
                    'notification_page' => false,
                ]);
            }
            if ($notification->type == 'App\Notifications\UserPostUploadedNotification') {
                return view('components.sub-components.notifications.type.postUploaded', [
                    'notification' => $notification,
                    'new' => true,
                    'notification_page' => false,
                ]);
            }
            if ($notification->type == 'App\Notifications\UserPostUpdatedNotification') {
                return view('components.sub-components.notifications.type.postUpdated', [
                    'notification' => $notification,
                    'new' => true,
                    'notification_page' => false,
                ]);
            }
            if ($notification->type == 'App\Notifications\ChatNotification') {
                return view('components.sub-components.notifications.type.chatMessage', [
                    'notification' => $notification,
                ]);
            }
        }
    }




    public function notificationSearch(Request $request)
    {
        if ($request->notification_search_for == 'all-notifications') {
            $notifications = getAuthAllNotification();

            $pagination = $notifications->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.notifications.notification', [
                'notification_page' => true,
                'notifications' => $notifications,
                'new' => false,
            ])->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $notifications->lastPage(),
                'total' => (string) $notifications->total()
            ]);
        }

        if ($request->notification_search_for == 'follow-notifications') {
            $notifications = getAuthUnreadFollowNotification();

            $pagination = $notifications->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.notifications.followNotification', [
                'notifications' => $notifications,
            ])->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $notifications->lastPage(),
                'total' => (string) $notifications->total()
            ]);
        }
    }
}
