<?php

namespace App\Http\Controllers;

use App\Events\NotificationDeleteEvent;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\UserNotification;
use Illuminate\Http\Request;

class SubscribeController extends Controller
{
    // to subscribe someone
    public function subscribeUser(User $user)
    {
        $authUser = auth()->user();
        $authUser->subscribe($user);
        $user->sendNotification('user_subscribe', [
            'receiver_id' => $user->id,
            'sender_id' => $authUser->id
        ]);

        // $user->notify(new UserNotification('Has started subscribing you.', 'success', auth()->id()));
        // auth()->user()->notify(new UserNotification('You have subscribe ' . $user->name . ' successfully.', 'success', $user->id));
        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to unsubscribe someone
    public function unSubscribeUser(User $user)
    {
        $authUser = auth()->user();
        $notification = $user->notifications()
            ->where('data->sender_id', $authUser->id)
            ->where('data->receiver_id', $user->id)
            ->where('data->type', 'user_subscribe')
            ->first();
        if (!is_null($notification)) {
            event(new NotificationDeleteEvent(Notification::find($notification->id), $notification->data['receiver_id'], 'KHgYDGSGIYGsidydasdgyufAg'));
            $notification->delete();
        }
        $authUser->unsubscribe($user);
        updatedUsersForSingleProfile($authUser, $user);
        return view('components.MyProfile.singleProfile', ['user' => $user, 'authUser' => $authUser, 'noscript' => true]);
    }

    // to searchs subscribers in subscriber section

    public function subscriberSearch(Request $request)
    {
        $user = whichUserIsBeingViewed();

        if ($request->subscriber_search_for == 'all-Subscribed') {
            $user_subscriptions = $user->subscriptions()->whereHas('subscribable', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            })->paginate(8);

            // dd($user_subscriptions);
            $pagination = $user_subscriptions->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-subscriber.subscribed', compact(['user_subscriptions']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_subscriptions->lastPage()
            ]);
        }

        if ($request->subscriber_search_for == 'Subscribed-added') {
            $user_subscribers = $user->subscribers()->where(function ($query) use ($request) {
                $query->orWhere('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            })->paginate(8);

            $pagination = $user_subscribers->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-subscriber.subscribers', compact(['user_subscribers']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_subscribers->lastPage()
            ]);
        }
    }
}
