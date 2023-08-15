<?php

namespace App\Jobs;

use App\Models\TempImage;
use App\Models\UserPost;
use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;


class UploadPostQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Batchable, Queueable, SerializesModels;
    public $post, $tempMedia;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($postValues, $tempMedia)
    {
        $this->post = UserPost::create($postValues);
        $this->tempMedia = $tempMedia;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->tempMedia->map(function ($singleTempMedia) {
            return $singleTempMedia->update([
                'post_id' => $this->post->id
            ]);
        });

        foreach ($this->post->tempImages as $key => $file) {
            $url = $file->getFirstMediaUrl();
            if ($this->post->type == 'images') {
                if ($this->post->is_paid == 1) {
                    $paidImage =   $this->post->addMediaFromUrl($url)->toMediaCollection('paidImage', 's3');
                    DB::table('media')->where('id', $paidImage->id)->update([
                        'order_column' =>  $file->sorting_key
                    ]);
                } else {
                    $freeImage =  $this->post->addMediaFromUrl($url)->toMediaCollection('image', 's3');
                    DB::table('media')->where('id', $freeImage->id)->update([
                        'order_column' =>  $file->sorting_key
                    ]);
                }
            } elseif ($this->post->type == 'video') {
                $this->post->addMediaFromUrl($url)->toMediaCollection('video', 's3');
            } elseif ($this->post->type == 'audio') {
                $this->post->addMediaFromUrl($url)->toMediaCollection('audio', 's3');
            }
        }

        $this->post->user->sendNotification('post_uploaded', [
            'post_id' => $this->post->id,
            'receiver_id' => $this->post->user->id
        ]);

        // $tempMedia->delete();
        Session::put('is_uploading', 'false');
    }
}
