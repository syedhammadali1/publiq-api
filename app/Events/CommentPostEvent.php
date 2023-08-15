<?php

namespace App\Events;

use App\Models\Comment;
use App\Models\UserPost;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CommentPostEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $post, $comment, $authUser;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(UserPost $post, Comment $comment)
    {
        $this->authUser = auth()->user();
        $this->post = $post;
        $comment->loadCount('likers');
        $this->authUser->attachLikeStatus($comment);
        $this->comment = $comment;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new Channel('post-comment-channel');
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'PostCommentEvent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'post_id' => $this->post->id,
            'post_uuid' => $this->post->uuid,
            // 'comment_id' => $this->comment->id,
            'count' => $this->post->comments_count,
            'html' => view('components.Home.NewsFeed.singleComment', [
                'comment' => $this->comment,
                'authUser' => $this->authUser
            ])->render(),
            'singleposthtml' => view('components.SinglePost.comment.singleComment', [
                'comment' => $this->comment
            ])->render()
        ];
    }
}
