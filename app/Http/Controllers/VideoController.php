<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserPost;


class VideoController extends Controller
{
    // to search videos in profile page video section
    public function videoSearch(Request $request)
    {
        $user = whichUserIsBeingViewed();
        if ($request->video_search_for == 'paid-added') {
            $user_paid_videos = $user->posts()->where('type', 'video')->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%')
                    ->where('is_paid', 1);
            })->paginate(8);

            $pagination = $user_paid_videos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-video.paid-video', compact(['user_paid_videos']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_paid_videos->lastPage()
            ]);
        }
        if ($request->video_search_for == 'all-free') {
            $user_free_videos = $user->posts()->where('type', 'video')->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%')
                    ->where('is_paid', 0);
            })->paginate(8);

            $pagination = $user_free_videos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-video.free-video', compact(['user_free_videos']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_free_videos->lastPage()
            ]);
        }
    }

    public function allVideosSearch(Request $request)
    {
        if ($request->video_search_for == 'member-paid-videos') {
            $user_paid_videos = UserPost::where('type', 'video')
                ->where('is_paid', 1)
                ->where(function ($query) use ($request) {
                    $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                })->orderBy('count_likes', 'desc')->paginate(8);

            $pagination = $user_paid_videos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-video.paid-video', compact(['user_paid_videos']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_paid_videos->lastPage(),
                'total' => (string) $user_paid_videos->total()

            ]);
        }
        if ($request->video_search_for == 'member-free-videos') {
            $user_free_videos = UserPost::where('type', 'video')
                ->where('is_paid', 0)
                ->where(function ($query) use ($request) {
                    $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                })->orderBy('count_likes', 'desc')->paginate(8);

            $pagination = $user_free_videos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-video.free-video', compact(['user_free_videos']))->render();
            // dd($user_free_videos->lastPage());

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_free_videos->lastPage(),
                'total' => (string) $user_free_videos->total()

            ]);
        }
    }
}
