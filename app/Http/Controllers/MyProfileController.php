<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateSettingsRequest;
use App\Models\User;
use App\Models\UserPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Overtrue\LaravelSubscribe\Subscription;

class MyProfileController extends Controller
{
    // to show third person profile
    public function singleProfile(User $user, Request $request)
    {
        if ($request->ajax()) {
            $user_posts = timeLinePosts();

            $html = view('components.Home.NewsFeed.foreachFeedPost', compact(['user_posts']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_posts->lastPage()
            ]);
        }

        if (auth()->id() == $user->id) {
            return redirect()->route('frontend.my-profile');
        }

        //  to handle session
        session()->put('viewedUser', [
            'url' => 'single',
            'id' => $user->id
        ]);
        // how many time ajax is call on profile
        sessionStartUpProfileAjax();
        $authUser = auth()->user();
        updatedUsersForSingleProfile($authUser, $user);
        return view('frontend.pages.single_profile', compact('user', 'authUser'));
    }

    public function myProfile(Request $request)
    {
        // to handle session
        session()->put('viewedUser', [
            'url' => 'my',
            'id' => auth()->id()
        ]);

        // how many time ajax is call on profile
        sessionStartUpProfileAjax();

        // profilepage dynamic photo,audio and video sections
        // which is shown on the right and left side
        // on user profile page
        $mostLikedFreePhotos = mostLikedFreePhotos();
        $mostLikedPaidPhotos = mostLikedPaidPhotos();
        $mostLikedFreeAudios = mostLikedFreeAudios();
        $mostLikedPaidAudios = mostLikedPaidAudios();
        $mostLikedPaidVideos = mostLikedPaidVideos();
        // end profilepage dynamic photo,audio and video sections

        // profilepage dynamic suggestion section
        // which is shown on the left side
        // on user profile page
        $suggestedPeoples = suggestedPeoples();
        //end profilepage dynamic suggestion section

        return view('frontend.pages.my-profile', compact(['mostLikedFreePhotos', 'mostLikedPaidPhotos', 'mostLikedFreeAudios', 'mostLikedPaidAudios', 'mostLikedPaidVideos', 'suggestedPeoples']));
    }

    public function uploadAvatar(Request $request)
    {
        // to upload profile pic and cover pic
        if (isset($request->avatar)) {
            auth()->user()->addMediaFromBase64($request->avatar)->usingFileName(createFileName($request->avatar, 'base64'))->toMediaCollection('avatar', 's3');
            return sendResponse(['image' =>  auth()->user()->getFirstMediaUrl('avatar')], 'done');
        }
        if (isset($request->cover)) {
            auth()->user()->addMediaFromBase64($request->cover)->usingFileName(createFileName($request->cover, 'base64'))->toMediaCollection('cover', 's3');
            return sendResponse(['image' =>  auth()->user()->getFirstMediaUrl('cover')], 'done');
        }
    }

    // to set session to redirect user to tab according to previous active session
    public function processSession(Request $request)
    {
        if ($request->method == 'get') {
            return json_encode(Session::get($request->for));
        }
        if ($request->method == 'put') {
            Session::put($request->for, $request->name);
            return true;
        }
    }

    // to display counts of  all followers and subscribers
    //  into the subscriber and follower section
    //  in personal and public profile
    public function getTotalCount(Request $request)
    {
        setWhichUserIsBeingViewed($request->DOMURL);
        $user = whichUserIsBeingViewed();
        $count = 0;
        if ($request->for == 'Followers-added') {
            $count = $user->my_followers(4)->total();
        }
        if ($request->for == 'all-Following') {
            $count = $user->my_followings(4)->total();
        }
        if ($request->for == 'all-Subscribed') {
            $count = $user->my_subscriptions(4)->total();
        }
        if ($request->for == 'Subscribed-added') {
            $count = $user->my_subscribers(4)->total();
        }
        if ($request->for == 'all-photos') {
            $count = $user->free_posts('images')->paginate(8)->total();
        }
        if ($request->for == 'photos-albums') {
            $count = $user->paid_posts('images')->paginate(8)->total();
        }
        if ($request->for == 'all-free') {
            $count = $user->free_posts('video')->paginate(8)->total();
        }
        if ($request->for == 'paid-added') {
            $count = $user->paid_posts('video')->paginate(8)->total();
        }
        if ($request->for == 'all-audio') {
            $count = $user->free_posts('audio')->paginate(8)->total();
        }
        if ($request->for == 'audio-albums') {
            $count = $user->paid_posts('audio')->paginate(8)->total();
        }
        if ($request->for == 'member-free-audio') {
            $count = UserPost::where('type', 'audio')
                ->where('is_paid', 0)
                ->paginate(8)->total();
        }
        if ($request->for == 'member-paid-audio') {
            $count = UserPost::where('type', 'audio')
                ->where('is_paid', 1)
                ->paginate(8)->total();
        }
        if ($request->for == 'member-all-users') {
            $count = User::withCount('subscribers')->orderByDesc('subscribers_count')->paginate(8)->total();
        }

        if ($request->for == 'member-free-photos') {
            $count = UserPost::where('type', 'images')
                ->where('is_paid', 0)
                ->paginate(8)->total();
        }
        if ($request->for == 'member-paid-photos') {
            $count = UserPost::where('type', 'images')
                ->where('is_paid', 1)
                ->paginate(8)->total();
        }
        if ($request->for == 'member-free-videos') {
            $count = UserPost::where('type', 'video')
                ->where('is_paid', 0)
                ->paginate(8)->total();
        }
        if ($request->for == 'member-paid-videos') {
            $count = UserPost::where('type', 'video')
                ->where('is_paid', 1)
                ->paginate(8)->total();
        }


        return response()->json([
            'count' => (string) $count
        ]);
    }

    // to update setting
    public function updateSetting(UpdateSettingsRequest $request, User $user)
    {
        $user->detail()->update($request->validated());

        return response()->json();
    }

    public function ProfilePageTrigger(Request $request)
    {
        if ($request->page) {
            $user_posts = UserPost::withCount('comments', 'likers', 'media')
                ->with('media', 'user.detail', 'comments.user.media', 'user.detail')
                ->where('user_id', whichUserIsBeingViewed()->id)
                ->orderBy('created_at', 'DESC')
                ->paginate(8);

            $html = view('components.Home.NewsFeed.foreachFeedPost', compact(['user_posts']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_posts->lastPage()
            ]);
        }

        setWhichUserIsBeingViewed($request->DOMURL);

        if ($request->for == "timeline") {
            if (session()->get('profileAjaxtimeline') == 1) {
                session()->get('profileAjaxtimeline') == 2
                    ? null
                    : session()->put('profileAjaxtimeline', 2);

                return view('components.MyProfile.SubPages.profileTimeline', [
                    'user_posts' => UserPost::withCount('comments', 'likers', 'media')
                        ->with('media', 'user.detail', 'comments.user.media', 'user.detail')
                        ->where('user_id', whichUserIsBeingViewed()->id)
                        ->orderBy('created_at', 'DESC')
                        ->paginate(8),
                ]);
            } else {
                return view('components.MyProfile.SubPages.profileTimeline', [
                    'user_posts' => UserPost::withCount('comments', 'likers', 'media')
                        ->with('media', 'user.detail', 'comments.user.media', 'user.detail')
                        ->where('user_id', whichUserIsBeingViewed()->id)
                        ->orderBy('created_at', 'DESC')
                        ->paginate(8),
                    'noscript' => true
                ]);
            }
        }

        if ($request->for == "about") {
            return view('components.MyProfile.SubPages.profileAbout', [
                'user' => whichUserIsBeingViewed()
            ]);
        }
        if ($request->for == "Followers") {
            return getSessionProfileAjax('Followers', 'components.MyProfile.SubPages.profileFollower');
        }
        if ($request->for == "Subscriber") {
            return getSessionProfileAjax('Subscriber', 'components.MyProfile.SubPages.profileSubscriber');
        }
        if ($request->for == "photos") {
            return getSessionProfileAjax('photos', 'components.MyProfile.SubPages.profilePhoto');
        }
        if ($request->for == "Audios") {
            return getSessionProfileAjax('Audios', 'components.MyProfile.SubPages.profileAudio');
        }
        if ($request->for == "Videos") {
            return getSessionProfileAjax('Videos', 'components.MyProfile.SubPages.profileVideo');
        }
    }

    public function reportUser(Request $request)
    {
        $validatedData = $request->validate([
            'problem' => ['required'],
            'feedback' => ['required', 'min:10'],
            'user' => ['required'],
        ]);
        $user = User::where('name', $request->user)->first();
        if ($user->id != auth()->id()) {
            $array = [
                'heading' => 'Report Succesfull',
                'content' => 'You have sccuessfully report ' . $user->full_name
            ];
            $successHtml = view('components.sub-components.successPopup', ['array' => $array])->render();
            return response()->json([
                'successHtml' => $successHtml
            ]);
        }
    }
}
