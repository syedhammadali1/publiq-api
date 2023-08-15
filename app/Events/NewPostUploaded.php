<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewPostUploaded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    public $post_uuid, $user;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($post_uuid, $user_id)
    {
        $this->post_uuid = $post_uuid;
        $this->user = User::find($user_id);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('App.Models.User.' . $this->user->id);
    }


    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'NewPostUploaded';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'post' => $this->post_uuid,
            'user' => [
                'avatar' => $this->user->avatar,
                'name' => $this->user->name
            ]
        ];
    }
}
