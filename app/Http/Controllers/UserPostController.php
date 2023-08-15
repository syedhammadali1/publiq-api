<?php

namespace App\Http\Controllers;

use App\Events\CommentPostEvent;
use App\Events\LikeCommentEvent;
use App\Events\LikePostEvent;
use App\Events\NotificationDeleteEvent;
use App\Models\UserPost;
use App\Http\Requests\StoreUserPostRequest;
use App\Http\Requests\UpdateUserPostRequest;
use App\Jobs\DeletePostQueue;
use App\Jobs\EditPostQueue;
use App\Jobs\UpdateUsersForNewPostQueue;
use App\Jobs\UploadPostQueue;
use App\Models\Comment;
use App\Models\TempImage;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class UserPostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \App\Http\Requests\StoreUserPostRequest  $request
     * @return \Illuminate\Http\Response
     */
    // to store post
    public function store(StoreUserPostRequest $request)
    {

        if (isset($request->uploadedFilesId)) {
            if (isset($request->sortedObj)) {
                $sortedObj = explode(',', $request->sortedObj);
                $sortedObjCollection = collect($sortedObj);

                $sortedObjCollection->each(function ($item, $key) {
                    $singleTempImage = TempImage::with('media')->where('media_id', $item)->update([
                        'sorting_key' => $key
                    ]);
                });
            }

            $filesArray = explode(',', $request->uploadedFilesId);
            $tempMedia = TempImage::with('media')->whereIn('media_id', $filesArray)->orderBy('sorting_key')->get();
            $mime_type = substr($tempMedia[0]->media[0]->mime_type, 0, 5);
        } else {
            $mime_type = 'text';
        }
        try {
            $request->validated();
            // return DB::transaction(function () use ($request) {
            if ($mime_type == 'image') {
                $type = 'images';
            } elseif ($request->mediaType == 'video') {
                $type = 'video';
            } elseif ($request->mediaType == 'audio') {
                $type = 'audio';
            } elseif ($mime_type == 'text') {
                $type = 'text';
            }

            if ($type != 'text') {
                session()->put('is_uploading', 'true');
                $postValues = [
                    'user_id' => auth()->id(),
                    'title' => $request->title,
                    'description' => $request->description,
                    'is_paid' => $request->is_paid,
                    'type' => $type
                ];
                UploadPostQueue::dispatch($postValues, $tempMedia);
            } else {
                $post = UserPost::create([
                    'user_id' => auth()->id(),
                    'title' => $request->title,
                    'description' => $request->description,
                    'is_paid' => $request->is_paid,
                    'type' => $type
                ]);
            }

            $uploadPostComponent = view('components.uploadPostComponent')->render();
            $data = [
                'uploadPostComponent' => $uploadPostComponent,
                'post_type' => $type
            ];
            if ($type == 'text') {
                $user_post = UserPost::withCount('likers', 'comments', 'media')->with('media', 'user')->find($post->id);
                $html = view('components.Home.NewsFeed.feedPost', compact('user_post'))->render();
                $data += [
                    'html' => $html,
                ];
            }
            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\UserPost  $userPost
     * @return \Illuminate\Http\Response
     */
    public function show(UserPost $userPost)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\UserPost  $userPost
     * @return \Illuminate\Http\Response
     */
    public function edit(User $user, UserPost $post)
    {
        $html = view('components.sub-components.edit-post.index', ['user_post' => $post])->render();
        return response($html);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \App\Http\Requests\UpdateUserPostRequest  $request
     * @param  \App\Models\UserPost  $userPost
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateUserPostRequest $request, User $user, UserPost $post)
    {
        if (isset($request->deleteImageArray)) {
            $deleteImageArray = explode(',', $request->deleteImageArray);
        }

        if (isset($request->uploadedFilesId)) {
            $filesArray = explode(',', $request->uploadedFilesId);
        }

        if ($post->type == 'text' || $post->type == 'shared') {
            $post->update([
                'title' => $request->title,
                'description' => $request->description,
                'is_paid' => $post->type == 'shared' ? 0 : $request->is_paid,
            ]);
            $user_post = UserPost::withCount('likers', 'comments', 'media')->with('media', 'user')->find($post->id);
            $html = view('components.Home.NewsFeed.feedPost', compact('user_post'))->render();
            return response()->json([
                'success' => 'Update Successfully',
                'html' => $html,
                'uuid' => $user_post->uuid,
                // 'sortedUploadedArray' => $sortedUploadedArray
            ]);
        }

        if ($post->type != 'text') {
            if (!empty($deleteImageArray) && empty($filesArray)) {
                if (count($deleteImageArray) >=  count($post->media)) {
                    return response()->json([
                        'errors'  => ['Please Upload Atleast One'],
                    ], 422);
                }
            }
            if ($post->type == 'video' && empty($request->uploadedFilesId)) {
                return response()->json([
                    'errors'  => ['Please Upload Atleast One'],
                ], 422);
            }
            if ($post->type == 'audio' && empty($request->uploadedFilesId)) {
                return response()->json([
                    'errors'  => ['Please Upload Atleast One'],
                ], 422);
            }
            session()->put('is_updating', 'true');

            EditPostQueue::dispatch($request->validated(), $post);
        }


        return response()->json([
            'success' => 'post will be uploaded soonn',
            'inQueue' => true
        ]);

        // if (isset($request->deleteImageArray)) {
        //     $deleteImageArray = explode(',', $request->deleteImageArray);
        // }

        // if (isset($request->uploadedFilesId)) {
        //     $filesArray = explode(',', $request->uploadedFilesId);
        // }

        // if ($post->type == 'text') {
        //     $post->update([
        //         'title' => $request->title,
        //         'description' => $request->description,
        //         'is_paid' => $request->is_paid,
        //     ]);
        // }

        // if ($post->type != 'text') {

        //     if (!empty($deleteImageArray) && empty($filesArray)) {
        //         if (count($deleteImageArray) >=  count($post->media)) {
        //             return response()->json([
        //                 'errors'  => ['Please Upload Atleast One'],
        //             ], 422);
        //         }
        //     }
        //     if ($post->type == 'video' && empty($request->uploadedFilesId)) {
        //         return response()->json([
        //             'errors'  => ['Please Upload Atleast One'],
        //         ], 422);
        //     }
        //     if ($post->type == 'audio' && empty($request->uploadedFilesId)) {
        //         return response()->json([
        //             'errors'  => ['Please Upload Atleast One'],
        //         ], 422);
        //     }

        //     $post->update([
        //         'title' => $request->title,
        //         'description' => $request->description,
        //         'is_paid' => $request->is_paid,
        //     ]);


        //     if (isset($request->deleteImageArray)) {
        //         $deleteImageArray = explode(',', $request->deleteImageArray);
        //         foreach ($deleteImageArray as $singleID) {
        //             $media =  $post->media->find($singleID);
        //             $media->delete();
        //         }
        //     }

        //     if (isset($request->sortEditArry)) {
        //         $sortedUploadedArray = explode(',', $request->sortEditArry);
        //     }

        //     if ($post->wasChanged('is_paid')) {
        //         $oldImageUploadFiles = [];
        //         if (!isset($request->deleteImageArray)) {
        //             $deleteImageArray = [];
        //         }

        //         collect($post->media()->get())->map(function ($oldImage) use (&$sortedUploadedArray, &$oldImageUploadFiles, $deleteImageArray) {
        //             if (($key = array_search($oldImage->id, $deleteImageArray)) == false) {
        //                 $temp_image = TempImage::create([]);
        //                 $oldImageCollection = $temp_image->addMediaFromUrl($oldImage->getFullUrl())->toMediaCollection();
        //                 $temp_image->update([
        //                     'media_id' => $oldImageCollection->id
        //                 ]);

        //                 array_push($oldImageUploadFiles, (string) $oldImageCollection->id);
        //                 if (($key = array_search($oldImage->id, $sortedUploadedArray)) !== false) {
        //                     $sortedUploadedArray[$key] = (string) $oldImageCollection->id;
        //                 }
        //             }
        //         });
        //         $request->merge([
        //             'uploadedFilesId' => is_null($request->uploadedFilesId) ?
        //                 $oldImageUploadFiles :
        //                 array_merge($oldImageUploadFiles, explode(',', $request->uploadedFilesId)),
        //         ]);

        //         $post->media()->delete();
        //     }



        //     if (isset($request->uploadedFilesId)) {
        //         $filesArray = gettype($request->uploadedFilesId) == 'array' ?
        //             $request->uploadedFilesId :
        //             explode(',', $request->uploadedFilesId);

        //         $tempMedia = TempImage::with('media')->whereIn('media_id', $filesArray)->get();
        //         $tempMedia->map(function ($singleTempMedia) use ($post) {
        //             return $singleTempMedia->update([
        //                 'post_id' => $post->id
        //             ]);
        //         });

        //         foreach ($tempMedia as $key => $file) {
        //             $url = $file->getFirstMediaUrl();
        //             if ($post->type == 'images') {
        //                 if ($request->is_paid == 1) {
        //                     $permanentMedia = $post->addMediaFromUrl($url)->toMediaCollection('paidImage', 's3');
        //                     if (($key = array_search($file->media_id, $sortedUploadedArray)) !== false) {
        //                         $sortedUploadedArray[$key] = $permanentMedia->id;
        //                     }
        //                 } else {
        //                     $permanentMedia = $post->addMediaFromUrl($url)->toMediaCollection('image', 's3');
        //                     if (($key = array_search($file->media_id, $sortedUploadedArray)) !== false) {
        //                         $sortedUploadedArray[$key] = $permanentMedia->id;
        //                     }
        //                 }
        //             } elseif ($post->type == 'video') {
        //                 // $post->media()->delete();
        //                 if (!$post->wasChanged('is_paid')) {
        //                     $post->media()->delete();
        //                 }
        //                 $post->addMediaFromUrl($url)->toMediaCollection('video', 's3');
        //             } elseif ($post->type == 'audio') {
        //                 if (!$post->wasChanged('is_paid')) {
        //                     $post->media()->delete();
        //                 }
        //                 $post->addMediaFromUrl($url)->toMediaCollection('audio', 's3');
        //             }
        //         }
        //     }

        //     $keyArray = [];
        //     if ($post->type == 'images') {
        //         collect($post->media()->get())->map(function ($media) use (&$sortedUploadedArray, &$keyArray) {
        //             $key = array_search($media->id, $sortedUploadedArray);
        //             $media->update([
        //                 'order_column' =>  $key
        //             ]);
        //             // unset($sortedUploadedArray[$key]);
        //         });
        //     }
        // }

        // $user_post = UserPost::withCount('likers', 'comments', 'media')->with('media', 'user')->find($post->id);
        // $html = view('components.Home.NewsFeed.feedPost', compact('user_post'))->render();
        // return response()->json([
        //     'success' => 'uploaded',
        //     // 'html' => $html,
        //     // 'uuid' => $user_post->uuid,
        //     // 'sortedUploadedArray' => $sortedUploadedArray
        // ]);
    }



    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\UserPost  $userPost
     * @return \Illuminate\Http\Response
     */
    public function destroy(User $user,UserPost $post)
    {
        DeletePostQueue::dispatch($post);
        return response()->json([
            'success' => true
        ]);
    }

    // to like post
    public function likePost(Request $request)
    {
        try {
            $post = UserPost::with('user')->find($request->id);
            $user = $request->user();
            $canLike = false;

            if ($post->is_paid == 0) {
                if ($user->isFollowingUser($post->user) || $user->id == $post->user->id) {
                    $canLike = true;
                }
            }
            if ($post->is_paid == 1) {
                if ($post->user->isSubscribedBy($user) || $user->id == $post->user->id) {
                    $canLike = true;
                }
            }

            if ($canLike) {
                $user->toggleLike($post);
                $status = $user->hasLiked($post);
                if ($post->user->id != auth()->id()) {
                    if ($status) {
                        $post->user->sendNotification('post_like', [
                            'post_id' => $request->id,
                            'receiver_id' => $post->user->id,
                            'sender_id' => $user->id
                        ]);
                    } else {
                        $notification = $post->user->notifications()
                            ->where('data->type', 'post_like')
                            ->where('data->receiver_id', $post->user->id)
                            ->where('data->sender_id', $user->id)
                            ->where('data->post_id', $request->id)
                            ->first();
                        event(new NotificationDeleteEvent(Notification::find($notification->id), $notification->data['receiver_id'], 'HGDisgisgjkGUIgiiyGShdsfdyu'));
                        if (!is_null($notification)) {
                            $notification->delete();
                        }
                    }
                }
                event(new LikePostEvent($post));

                return response()->json([
                    'status' => $status
                ]);
            } else {
                return response()->json([
                    'status' => false
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function likeComment(Request $request)
    {
        try {
            $comment = Comment::with('user')->find($request->id);
            $user = $request->user();
            $user->toggleLike($comment);
            $status = $user->hasLiked($comment);
            if ($comment->user->id != auth()->id()) {
                if ($status) {
                    $comment->user->sendNotification('comment_like', [
                        'comment_id' => $request->id,
                        'post_id' => $comment->post->id,
                        'receiver_id' => $comment->user->id,
                        'sender_id' => $user->id
                    ]);
                } else {
                    $notification = $comment->user->notifications()
                        ->where('data->type', 'comment_like')
                        ->where('data->receiver_id', $comment->user->id)
                        ->where('data->sender_id', $user->id)
                        ->where('data->post_id', $comment->post->id)
                        ->where('data->comment_id', $request->id)
                        ->first();
                    event(new NotificationDeleteEvent(Notification::find($notification->id), $notification->data['receiver_id'], 'HGDisgisgjkGUIgiiyGShdsfdyu'));
                    if (!is_null($notification)) {
                        $notification->delete();
                    }
                }
            }

            event(new LikeCommentEvent($comment));
            return response()->json([
                'status' => $status
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function commentPost(Request $request)
    {
        try {
            if ($request->message != null || $request->message != " ") {
                $user = $request->user();

                $comment = Comment::create([
                    'user_id' => $user->id,
                    'post_id' => $request->post_id,
                    'message' => $request->message,
                ]);


                $post = UserPost::withCount('comments')->find($request->post_id);
                if ($post->user->id != auth()->id()) {
                    $post->user->sendNotification('post_comment', [
                        'post_id' => $request->post_id,
                        'receiver_id' => $post->user->id,
                        'sender_id' => $user->id
                    ]);
                }

                event(new CommentPostEvent($post, $comment));
                return true;
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }


    public function singlePostRender(Request $request)
    {
        if (UserPost::find($request->id)) {
            try {
                $authUser = auth()->user();
                if ($request->page) {
                    $user_post_comments = Comment::withCount('likers')
                        ->with('user.detail', 'user.media')
                        ->where('post_id', $request->id)
                        ->orderBy('created_at', 'desc')
                        ->paginate(8);
                    $user_post_comments->each(function ($user_post_comment) use ($authUser) {
                        $authUser->attachLikeStatus($user_post_comment);
                    });

                    $html = view('components.SinglePost.comment.commentLoop', compact('user_post_comments'))->render();
                    return response()->json([
                        'html' => $html,
                        'lastPage' => (string) $user_post_comments->lastPage()
                    ]);
                }

                $user_post = UserPost::withCount('likers', 'comments')
                    ->with('media', 'user.media')
                    ->find($request->id);
                $user_post = $authUser->attachLikeStatus($user_post);
                $user_post_comments = Comment::withCount('likers')
                    ->with('user.detail', 'user.media')
                    ->where('post_id', $user_post->id)
                    ->orderBy('created_at', 'desc')
                    ->paginate(8);

                $user_post_comments->each(function ($user_post_comment) use ($authUser) {
                    $authUser->attachLikeStatus($user_post_comment);
                });


                // free condition
                if ($user_post->is_paid == 0 && $user_post->user_id != $authUser->id) {
                    if ($user_post->user->isSubscribedBy($authUser) || $authUser->isFollowingUser($user_post->user)) {
                        return response()->json([
                            'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
                        ]);
                        // return view('components.SinglePost.index', compact('user_post', 'user_post_comments'));
                    } else {
                        $array = [
                            'should_redirect' => true,
                            'redirect_path' => route('frontend.single.profile', $user_post->user),
                            'heading' => 'Follow User',
                            'content' => 'You Have To follow this User ' . $user_post->user->full_name,
                            'redirect_time' => 5000
                        ];
                        return response()->json([
                            'html' => view('components.sub-components.successPopup', ['array' => $array])->render()
                        ]);
                        // return view('components.sub-components.successPopup', ['array' => $array]);
                    }
                }

                // paid condition
                if ($user_post->is_paid == 1 && $user_post->user_id != $authUser->id) {
                    if ($user_post->user->isSubscribedBy($authUser)) {
                        return response()->json([
                            'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
                        ]);
                        // return view('components.SinglePost.index', compact('user_post', 'user_post_comments'));
                    } else {
                        return response()->json([
                            'html' => view('components.sub-components.subscribeToPaidContent', [
                                'user' => $user_post->user
                            ])->render(),
                            'subToView' => true
                        ]);
                        // return view('components.sub-components.subscribeToPaidContent', [
                        //     'user' => $user_post->user
                        // ]);
                    }
                }
                return response()->json([
                    'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
                ]);
                // return view('components.SinglePost.index', compact(['user_post', 'user_post_comments']));
            } catch (\Exception $e) {
                return response()->json([
                    'message' => $e->getMessage(),
                ]);
            }
        } else {
            return response()->json([
                'postNotAvailable' => true
            ]);
        }

        // try {
        //     $authUser = auth()->user();
        //     if ($request->page) {
        //         $user_post_comments = Comment::withCount('likers')
        //             ->with('user.detail', 'user.media')
        //             ->where('post_id', $request->id)
        //             ->orderBy('created_at', 'desc')
        //             ->paginate(8);
        //         $user_post_comments->each(function ($user_post_comment) use ($authUser) {
        //             $authUser->attachLikeStatus($user_post_comment);
        //         });

        //         $html = view('components.SinglePost.comment.commentLoop', compact('user_post_comments'))->render();
        //         return response()->json([
        //             'html' => $html,
        //             'lastPage' => (string) $user_post_comments->lastPage()
        //         ]);
        //     }

        //     $user_post = UserPost::withCount('likers', 'comments')
        //         ->with('media', 'user.media')
        //         ->find($request->id);
        //     $user_post = $authUser->attachLikeStatus($user_post);
        //     $user_post_comments = Comment::withCount('likers')
        //         ->with('user.detail', 'user.media')
        //         ->where('post_id', $user_post->id)
        //         ->orderBy('created_at', 'desc')
        //         ->paginate(8);

        //     $user_post_comments->each(function ($user_post_comment) use ($authUser) {
        //         $authUser->attachLikeStatus($user_post_comment);
        //     });


        //     // free condition
        //     if ($user_post->is_paid == 0 && $user_post->user_id != $authUser->id) {
        //         if ($user_post->user->isSubscribedBy($authUser) || $authUser->isFollowingUser($user_post->user)) {
        //             return response()->json([
        //                 'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
        //             ]);
        //             // return view('components.SinglePost.index', compact('user_post', 'user_post_comments'));
        //         } else {
        //             $array = [
        //                 'should_redirect' => true,
        //                 'redirect_path' => route('frontend.single.profile', $user_post->user),
        //                 'heading' => 'Follow User',
        //                 'content' => 'You Have To follow this User ' . $user_post->user->full_name,
        //                 'redirect_time' => 5000
        //             ];
        //             return response()->json([
        //                 'html' => view('components.sub-components.successPopup', ['array' => $array])->render()
        //             ]);
        //             // return view('components.sub-components.successPopup', ['array' => $array]);
        //         }
        //     }

        //     // paid condition
        //     if ($user_post->is_paid == 1 && $user_post->user_id != $authUser->id) {
        //         if ($user_post->user->isSubscribedBy($authUser)) {
        //             return response()->json([
        //                 'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
        //             ]);
        //             // return view('components.SinglePost.index', compact('user_post', 'user_post_comments'));
        //         } else {
        //             return response()->json([
        //                 'html' => view('components.sub-components.subscribeToPaidContent', [
        //                     'user' => $user_post->user
        //                 ])->render(),
        //                 'subToView' => true
        //             ]);
        //             // return view('components.sub-components.subscribeToPaidContent', [
        //             //     'user' => $user_post->user
        //             // ]);
        //         }
        //     }
        //     return response()->json([
        //         'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
        //     ]);
        //     // return view('components.SinglePost.index', compact(['user_post', 'user_post_comments']));
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'message' => $e->getMessage(),
        //     ]);
        // }
    }

    public function postLikesRender(UserPost $post, Request $request)
    {
        try {
            if ($request->page) {
                $authId = auth()->id();
                $users = $post->likers()->latest()->paginate(8);
                $typeOf = 'like';
                $html = view('components.SinglePost.like.feedpost.foreach', compact('users', 'authId', 'typeOf'))->render();
                return response()->json([
                    'html' => $html,
                    'lastPage' => (string) $users->lastPage()
                ]);
            }
            $authId = auth()->id();
            $users = $post->likers()->latest()->paginate(8);
            $typeOf = 'like';
            return view('components.SinglePost.like.feedpost.index', compact('users', 'authId', 'typeOf'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function postSharesRender(UserPost $post, Request $request)
    {
        try {
            if ($request->page) {
                $authId = auth()->id();
                $users = $post->shares()->latest()->paginate(8);
                $typeOf = 'share';
                $html = view('components.SinglePost.like.feedpost.foreach', compact('users', 'authId', 'typeOf'))->render();
                return response()->json([
                    'html' => $html,
                    'lastPage' => (string) $users->lastPage()
                ]);
            }
            $authId = auth()->id();
            $users = $post->shares()->latest()->paginate(8);
            $typeOf = 'share';
            return view('components.SinglePost.like.feedpost.index', compact('users', 'authId', 'typeOf'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function commentLikesRender(Comment $comment, Request $request)
    {
        try {
            if ($request->page) {
                $authId = auth()->id();
                $users = $comment->likers()->latest()->paginate(8);
                $typeOf = 'like';
                $html = view('components.SinglePost.like.feedpost.foreach', compact('users', 'authId', 'typeOf'))->render();
                return response()->json([
                    'html' => $html,
                    'lastPage' => (string) $users->lastPage()
                ]);
            }
            $authId = auth()->id();
            $users = $comment->likers()->latest()->paginate(8);
            $typeOf = 'like';
            return view('components.SinglePost.like.feedpost.index', compact('users', 'authId', 'typeOf'));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }


    public function singlePost(User $user, UserPost $post, Request $request)
    {

        // try {
        //     $authUser = auth()->user();
        //     if ($request->page) {
        //         $user_post_comments = Comment::withCount('likers')
        //             ->with('user.detail', 'user.media')
        //             ->where('post_id', $request->id)
        //             ->orderBy('created_at', 'desc')
        //             ->paginate(8);
        //         $user_post_comments->each(function ($user_post_comment) use ($authUser) {
        //             $authUser->attachLikeStatus($user_post_comment);
        //         });

        //         $html = view('components.SinglePost.comment.commentLoop', compact('user_post_comments'))->render();
        //         return response()->json([
        //             'html' => $html,
        //             'lastPage' => (string) $user_post_comments->lastPage()
        //         ]);
        //     }

        //     $user_post = UserPost::withCount('likers', 'comments')
        //         ->with('media', 'user.media')
        //         ->find($request->id);
        //     $user_post = $authUser->attachLikeStatus($user_post);
        //     $user_post_comments = Comment::withCount('likers')
        //         ->with('user.detail', 'user.media')
        //         ->where('post_id', $user_post->id)
        //         ->orderBy('created_at', 'desc')
        //         ->paginate(8);

        //     $user_post_comments->each(function ($user_post_comment) use ($authUser) {
        //         $authUser->attachLikeStatus($user_post_comment);
        //     });


        //     // free condition
        //     if ($user_post->is_paid == 0 && $user_post->user_id != $authUser->id) {
        //         if ($user_post->user->isSubscribedBy($authUser) || $authUser->isFollowingUser($user_post->user)) {
        //             return response()->json([
        //                 'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
        //             ]);
        //             // return view('components.SinglePost.index', compact('user_post', 'user_post_comments'));
        //         } else {
        //             $array = [
        //                 'should_redirect' => true,
        //                 'redirect_path' => route('frontend.single.profile', $user_post->user),
        //                 'heading' => 'Follow User',
        //                 'content' => 'You Have To follow this User ' . $user_post->user->full_name,
        //                 'redirect_time' => 5000
        //             ];
        //             return response()->json([
        //                 'html' => view('components.sub-components.successPopup', ['array' => $array])->render()
        //             ]);
        //             // return view('components.sub-components.successPopup', ['array' => $array]);
        //         }
        //     }

        //     // paid condition
        //     if ($user_post->is_paid == 1 && $user_post->user_id != $authUser->id) {
        //         if ($user_post->user->isSubscribedBy($authUser)) {
        //             return response()->json([
        //                 'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
        //             ]);
        //             // return view('components.SinglePost.index', compact('user_post', 'user_post_comments'));
        //         } else {
        //             return response()->json([
        //                 'html' => view('components.sub-components.subscribeToPaidContent', [
        //                     'user' => $user_post->user
        //                 ])->render(),
        //                 'subToView' => true
        //             ]);
        //             // return view('components.sub-components.subscribeToPaidContent', [
        //             //     'user' => $user_post->user
        //             // ]);
        //         }
        //     }
        //     return response()->json([
        //         'html' => view('components.SinglePost.index', compact('user_post', 'user_post_comments'))->render()
        //     ]);
        //     // return view('components.SinglePost.index', compact(['user_post', 'user_post_comments']));
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'message' => $e->getMessage(),
        //     ]);
        // }

        return view('frontend.pages.single-post', compact('post'));
    }


    public function tempUpload(Request $request)
    {
        // session()->put('media_uploading', 'true');
        Session::put('media_uploading', 'true');

        $file = $request->file('file');
        $mimeType =  $file->getMimeType();
        $type = substr($mimeType, 0, 5);

        $temp_image =  TempImage::create([]);
        $mediaCollection = $temp_image->addMedia($file)->toMediaCollection();
        $temp_image->update([
            'media_id' => $mediaCollection->id
        ]);
        // session()->put('media_uploading', 'false');
        Session::put('media_uploading', 'false');
        // dd(session()->get('media_uploading'));

        return  response()->json([
            'id' => $mediaCollection->id,
            'mimeType' => $type,
            'url' => $temp_image->getFirstMediaUrl()
        ]);
    }

    public function postPreviousCommentsRender(UserPost $post, Request $request)
    {
        $except = $post->comments()->latest()->pluck('id')->first();
        $comments = $post->comments()->withCount('likers')->whereNot('id', $except)->latest()->paginate(4);

        if ($request->page < $comments->lastPage()) {
            $data = [
                'html' => view('components.Home.NewsFeed.comments', [
                    'comments' => $comments,
                    'authUser' => auth()->user()
                ])->render(),
                'lastPage' => (string) $comments->lastPage()
            ];
        }

        if ($request->page >= $comments->lastPage()) {
            $data = [
                'html' => view('components.Home.NewsFeed.comments', [
                    'comments' => $comments,
                    'authUser' => auth()->user()
                ])->render(),
                'lastPage' => (string) $comments->lastPage(),
                'has_finish' => false,
            ];
        }


        return response()->json($data);
    }

    public function report(User $user, UserPost $post,  Request $request)
    {
        try {
            $validatedData = $request->validate([
                'problem' => ['required'],
                'feedback' => ['required', 'min:10'],
            ]);
            if ($user->id != auth()->id()) {
                $array = [
                    'just_close_model' => true,
                    // 'redirect_path' => route('frontend.single.profile', $user_post->user),
                    'heading' => 'Report Succesfull',
                    'content' => 'Thank You',
                    'redirect_time' => 3000
                ];
                $successHtml = view('components.sub-components.successPopup', ['array' => $array])->render();
                return response()->json([
                    // 'html' => $html,
                    'successHtml' => $successHtml
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function viewSharePost(Request $request)
    {
        try {
            $authUser = auth()->user();
            $user_post = UserPost::withCount('likers', 'comments')
                ->with('media', 'user.media')
                ->where('uuid', $request->post)
                ->first();


            if ($request->foruse == 'timeline') {
                $hasAlreadyShared = $user_post->shares()->where('users.id', $authUser->id)->exists();

                if ($hasAlreadyShared) {
                    $array = [
                        'icon' => 'fa fa-warning',
                        'heading' => 'Already Shared',
                        'simple-content' => 'Sorry you cannot share this post again!',
                    ];
                    return view('components.sub-components.successPopup', ['array' => $array]);
                }


                if ($user_post->is_paid == 0 && $user_post->user_id != $authUser->id) {
                    if ($user_post->user->isSubscribedBy($authUser) || $authUser->isFollowingUser($user_post->user)) {
                        return view('components.sub-components.share-post.index', compact(['user_post', 'authUser']));
                    } else {
                        $array = [
                            'should_redirect' => true,
                            'redirect_path' => route('frontend.single.profile', $user_post->user),
                            'heading' => 'Follow User',
                            'content' => 'You Have To follow this User ' . $user_post->user->full_name,
                            'redirect_time' => 5000
                        ];
                        return view('components.sub-components.successPopup', ['array' => $array]);
                    }
                }

                // paid condition
                if ($user_post->is_paid == 1 && $user_post->user_id != $authUser->id) {
                    if ($user_post->user->isSubscribedBy($authUser)) {
                        return view('components.sub-components.share-post.index', compact(['user_post', 'authUser']));
                    } else {
                        return view('components.sub-components.subscribeToPaidContent', [
                            'user' => $user_post->user
                        ]);
                    }
                }

                $array = [
                    'icon' => 'fa fa-warning',
                    'heading' => 'Cannot Share',
                    'simple-content' => 'Sorry you cannot share your own post!',
                ];
                return view('components.sub-components.successPopup', ['array' => $array]);
            }

            if ($request->foruse == 'external') {
                return view('components.sub-components.share-post.externalshare', [
                    'link' => route('frontend.single.post', ['user' => $user_post->user, 'post' => $user_post])
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function sharePost(Request $request)
    {
        $validatedData = $request->validate([
            'title' => ['required'],
            'description' => ['nullable'],
            'post' => ['required'],
        ]);
        try {
            $authUser = auth()->user();
            $post = UserPost::where('uuid', $request->post)->first();
            $share_post = UserPost::create([
                'parent_id' => $post->id,
                'user_id' => $authUser->id,
                'is_paid' => 0,
                'type' => 'shared',
                'title' => $request->title,
                'description' =>  $request->description,
            ]);

            $user_post = UserPost::withCount('likers', 'comments', 'media')->with('media', 'user')->find($share_post->id);
            $user_post = $authUser->attachLikeStatus($user_post);

            $html = view('components.Home.NewsFeed.feedPost', compact('user_post'))->render();
            return response()->json([
                'html' => $html
            ]);
            // return view('components.sub-components.share-post.index', compact(['user_post', 'authUser']));
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ]);
        }
    }
}
