<?php

namespace App\Jobs;

use App\Http\Requests\UpdateUserPostRequest;
use App\Models\TempImage;
use App\Models\UserPost;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class EditPostQueue implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public $request, $post;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($request, UserPost $post)
    {
        $this->request = $request;
        $this->post = $post;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $this->post->update([
            'title' => $this->request['title'],
            'description' => $this->request['description'],
            'is_paid' => $this->request['is_paid'],
        ]);


        if (!is_null($this->request['deleteImageArray'])) {
            $deleteImageArray = explode(',', $this->request['deleteImageArray']);
            foreach ($deleteImageArray as $singleID) {
                $media =  $this->post->media->find($singleID);
                $media->delete();
            }
        }

        if (!is_null($this->request['sortEditArry'])) {
            $sortedUploadedArray = explode(',', $this->request['sortEditArry']);
        }

        if ($this->post->wasChanged('is_paid')) {
            $oldImageUploadFiles = [];
            if (is_null($this->request['deleteImageArray'])) {
                $deleteImageArray = [];
            }

            collect($this->post->media()->get())->map(function ($oldImage) use (&$sortedUploadedArray, &$oldImageUploadFiles, $deleteImageArray) {
                if (($key = array_search($oldImage->id, $deleteImageArray)) == false) {
                    $temp_image = TempImage::create([]);
                    $oldImageCollection = $temp_image->addMediaFromUrl($oldImage->getFullUrl())->toMediaCollection();
                    $temp_image->update([
                        'media_id' => $oldImageCollection->id
                    ]);

                    array_push($oldImageUploadFiles, (string) $oldImageCollection->id);
                    if (($key = array_search($oldImage->id, $sortedUploadedArray)) !== false) {
                        $sortedUploadedArray[$key] = (string) $oldImageCollection->id;
                    }
                }
            });

            $this->request['uploadedFilesId'] = is_null($this->request['uploadedFilesId']) ?
                $oldImageUploadFiles :
                array_merge($oldImageUploadFiles, explode(',', $this->request['uploadedFilesId']));

            Log::alert($this->request, $oldImageUploadFiles);
            Log::alert($oldImageUploadFiles);
            $this->post->media()->delete();
        }



        if (!is_null($this->request['uploadedFilesId'])) {
            $filesArray = gettype($this->request['uploadedFilesId']) == 'array' ?
                $this->request['uploadedFilesId'] :
                explode(',', $this->request['uploadedFilesId']);

            $tempMedia = TempImage::with('media')->whereIn('media_id', $filesArray)->get();
            $tempMedia->map(function ($singleTempMedia) {
                return $singleTempMedia->update([
                    'post_id' => $this->post->id
                ]);
            });

            foreach ($tempMedia as $key => $file) {
                $url = $file->getFirstMediaUrl();
                if ($this->post->type == 'images') {
                    if ($this->request['is_paid'] == 1) {
                        $permanentMedia = $this->post->addMediaFromUrl($url)->toMediaCollection('paidImage', 's3');
                        if (($key = array_search($file->media_id, $sortedUploadedArray)) !== false) {
                            $sortedUploadedArray[$key] = $permanentMedia->id;
                        }
                    } else {
                        $permanentMedia = $this->post->addMediaFromUrl($url)->toMediaCollection('image', 's3');
                        if (($key = array_search($file->media_id, $sortedUploadedArray)) !== false) {
                            $sortedUploadedArray[$key] = $permanentMedia->id;
                        }
                    }
                } elseif ($this->post->type == 'video') {
                    if (!$this->post->wasChanged('is_paid')) {
                        $this->post->media()->delete();
                    }
                    $this->post->addMediaFromUrl($url)->toMediaCollection('video', 's3');
                } elseif ($this->post->type == 'audio') {
                    if (!$this->post->wasChanged('is_paid')) {
                        $this->post->media()->delete();
                    }
                    $this->post->addMediaFromUrl($url)->toMediaCollection('audio', 's3');
                }
            }
        }

        if ($this->post->type == 'images') {
            collect($this->post->media()->get())->map(function ($media) use (&$sortedUploadedArray) {
                $key = array_search($media->id, $sortedUploadedArray);
                $media->update([
                    'order_column' =>  $key
                ]);
            });
        }

        $this->post->user->sendNotification('post_updated', [
            'post_id' => $this->post->id,
            'receiver_id' => $this->post->user->id
        ]);
        Session::put('is_updating', 'false');

    }
}
