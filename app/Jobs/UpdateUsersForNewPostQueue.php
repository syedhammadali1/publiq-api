<?php

namespace App\Jobs;

use App\Events\NewPostUploaded;
use App\Models\User;
use App\Models\UserPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateUsersForNewPostQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $post, $user;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct(User $user, UserPost $post)
    {
        $this->user = $user;
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if ($this->post->is_paid == 1) {
            $subscriptions = $this->user->subscribers()->get()->pluck('id');
            $subscriptions->each(function ($user_id) {
                event(new NewPostUploaded($this->post->uuid, $user_id));
            });
        }
        if ($this->post->is_paid == 0) {
            $followings = $this->user->followers()->whereNotNull('accepted_at')->pluck('user_id');
            $followings->each(function ($user_id) {
                event(new NewPostUploaded($this->post->uuid, $user_id));
            });
        }
    }
}
