<?php

namespace App\Events;

use App\Models\Notification;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NotificationDeleteEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $notification, $code, $notifyOnUser, $unreadCount;


    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(Notification $notification, $notifyOnUser, $code)
    {
        $this->notification = $notification;
        $this->notifyOnUser = $notifyOnUser;
        $this->code = $code;
        $unreadCountF = getUserNotificationsCount($this->notifyOnUser, 'follow');
        $unreadCountN = getUserNotificationsCount($this->notifyOnUser, 'notification');
        $this->unreadCount = [
            'follow' => $unreadCountF == 0 ? $unreadCountF : $unreadCountF - 1,
            'notification' => $unreadCountN == 0 ? $unreadCountN : $unreadCountN - 1
        ];
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.' . $this->notifyOnUser);
    }


    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'NotificationDeleteEvent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'notification_id' => $this->notification->id,
            'type' =>  $this->notification->data['type'],
            'sender_id' => $this->notification->data['sender_id'],
            'receiver_id' => $this->notification->data['receiver_id'],
            'code' => $this->code,
            'unreadCount' => $this->unreadCount
        ];
    }
}
