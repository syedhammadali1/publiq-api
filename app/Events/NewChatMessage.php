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

class NewChatMessage  implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $receiver;
    public $message;
    public $attachment;
    public $notification;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($receiver, $sender, $message, $data, $attachment = null)
    {
        $this->sender = $sender;
        $this->receiver = $receiver;
        $this->message = $message;
        $this->attachment = $attachment;

        $this->notification = Notification::where('data->sender_id', $data['sender_id'])
            ->where('data->receiver_id', $data['receiver_id'])
            ->where('data->type', 'chat_room')
            ->first();

        if ($this->notification) {
            $this->notification->update([
                'data' => [
                    'sender_id' => $data['sender_id'],
                    'receiver_id' => $data['receiver_id'],
                    'last_message' => $data['last_message'],
                    'unseen' => $data['unseen'],
                    'type' => 'chat_room'
                ],
                'created_at' => now(),
            ]);
        }
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.' . $this->receiver->id);
    }
}
