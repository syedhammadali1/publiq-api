<?php

namespace App\Http\Controllers;

use App\Events\NotificationDeleteEvent;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Http\Request;

class FollowController extends Controller
{

    public function notificationFollowRequest(Request $request)
    {
        $notification = Notification::find($request->notification_id);
        if (!is_null($notification)) {
            if ($request->action == 'accept') {
                $authUser = auth()->user();
                $user = User::find($notification->data['sender_id']);
                $notification->delete();
                $authUser->acceptFollowRequestFrom($user);
                // $notification->markAsRead();
                $user->sendNotification('user_follow_accept', [
                    'receiver_id' => $user->id,
                    'sender_id' => $authUser->id
                ]);
                return response()->json([
                    'status' => true,
                    'unreadCount' => getUserNotificationsCount($authUser->id, 'follow')
                ]);
            }
            if ($request->action == 'decline') {
                $authUser = auth()->user();
                $user = User::find($notification->data['sender_id']);
                event(new NotificationDeleteEvent($notification, $notification->data['sender_id'], 'bavsjVAFSYufhvQWUVWfvhjwVJHXUy'));
                $notification->delete();
                $authUser->rejectFollowRequestFrom($user);
                // $notification->markAsRead();
                return response()->json([
                    'status' => true,
                    'unreadCount' => getUserNotificationsCount($authUser->id, 'follow')
                ]);
            }
        }
    }

    // to send follow request to someone
    public function sendFollowRequest(User $user, Request $request)
    {
        $authUser = auth()->user();
        $authUser->follow($user);

        $user->sendNotification('user_follow_request', [
            'receiver_id' => $user->id,
            'sender_id' => $authUser->id
        ]);


        if ($request->global_search) {
            $user->loadCount('subscribers');
            return view('components.GlobalSearch.Member.single-member', ['user' => $user]);
        }

        if ($request->like_user) {
            $user->loadCount('subscribers');
            return view('components.SinglePost.like.feedpost.single', ['user' => $user, 'authId' => $authUser->id]);
        }

        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to unfollow someone
    public function removeFollowRequest(User $user, Request $request)
    {
        $authUser = auth()->user();
        $notificationFollowReq = $user->notifications()
            ->where('data->sender_id', $authUser->id)
            ->where('data->receiver_id', $user->id)
            ->where('data->type', 'user_follow_request')
            ->get();

        // $notificationAuth = $authUser->notifications()
        //     ->where('data->receiver_id', $authUser->id)
        //     ->where('data->sender_id', $user->id)
        //     ->where('data->type', 'user_follow_accept')
        //     ->first();

        $notificationUser = $user->notifications()
            ->where('data->sender_id', $authUser->id)
            ->where('data->receiver_id', $user->id)
            ->where('data->type', 'user_follow_accept')
            ->first();

        if (!is_null($notificationFollowReq)) {
            if ($notificationFollowReq->count() == 1) {
                $noti = $notificationFollowReq->first();
                event(new NotificationDeleteEvent(Notification::find($noti->id), $noti->data['receiver_id'], 'skjdgTSDiyvYFssdsdJBKSGUI'));
            }
            $notificationFollowReq->map(function ($noti) {
                $noti->delete();
            });
        }
        // if (!is_null($notificationAuth)) {
        //     event(new NotificationDeleteEvent(Notification::find($notificationAuth->id), $notificationAuth->data['sender_id'], 'IUDGygYsyudTYIDTIYSdigvghvYS'));
        //     $notificationAuth->delete();
        // }
        if (!is_null($notificationUser)) {
            event(new NotificationDeleteEvent(Notification::find($notificationUser->id), $notificationUser->data['sender_id'], 'psojIPJSIDjsIDhBSdjbusdSDuySG'));
            $notificationUser->delete();
        }

        $authUser->unfollow($user);

        if ($request->global_search) {
            $user->loadCount('subscribers');
            return view('components.GlobalSearch.Member.single-member', ['user' => $user]);
        }
        if ($request->like_user) {
            $user->loadCount('subscribers');
            return view('components.SinglePost.like.feedpost.single', ['user' => $user, 'authId' => $authUser->id]);
        }
        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to reject follow request
    public function declineFollowRequest(User $user)
    {
        $authUser = auth()->user();
        $notification = $authUser->notifications()
            ->where('data->sender_id', $user->id)
            ->where('data->receiver_id', $authUser->id)
            ->where('data->type', 'user_follow_request')
            ->first();

        if (!is_null($notification)) {
            event(new NotificationDeleteEvent(Notification::find($notification->id), $notification->data['receiver_id'], 'lsjhdusdusgudqy7wugq7wdgi'));
            event(new NotificationDeleteEvent(Notification::find($notification->id), $notification->data['sender_id'], 'hvsdsadyfGAHSFyFhFDJSyuSDJ'));
            $notification->delete();
        }
        $authUser->rejectFollowRequestFrom($user);
        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to accept follow request
    public function acceptFollowRequest(User $user)
    {
        $authUser = auth()->user();
        $notification = $authUser->notifications()
            ->where('data->sender_id', $user->id)
            ->where('data->receiver_id', $authUser->id)
            ->where('data->type', 'user_follow_request')
            ->first();
        if (!is_null($notification)) {
            event(new NotificationDeleteEvent(Notification::find($notification->id), $notification->data['receiver_id'], 'lsjhdusdusgudqy7wugq7wdgi'));
            // event(new NotificationDeleteEvent(Notification::find($notification->id), $notification->data['sender_id'], 'hvsdsadyfGAHSFyFhFDJSyuSDJ'));
            $notification->delete();
        }
        $authUser->acceptFollowRequestFrom($user);
        $user->sendNotification('user_follow_accept', [
            'receiver_id' => $user->id,
            'sender_id' => $authUser->id
        ]);
        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to unfollow someone
    public function blockUser(User $user)
    {
        auth()->user()->unfollow($user);

        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to unfollow someone
    public function reportUser(User $user)
    {
        auth()->user()->unfollow($user);

        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to searchs in follower section
    public function followerSearch(Request $request)
    {
        $user = whichUserIsBeingViewed();
        if ($request->follower_search_for == 'all-Following') {
            $following_users = $user->followings()->whereNotNull('accepted_at')->whereHas('followable', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            })->paginate(8);

            $pagination = $following_users->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-follower.following', compact(['following_users']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $following_users->lastPage()
            ]);
        }
        if ($request->follower_search_for == 'Followers-added') {
            $user_followers = $user->followers()->whereNotNull('accepted_at')->where(function ($query) use ($request) {
                $query->orWhere('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            })->paginate(8);

            $pagination = $user_followers->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-follower.followers', compact(['user_followers']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_followers->lastPage()
            ]);
        }
    }
}
