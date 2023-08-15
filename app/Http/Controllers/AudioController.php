<?php

namespace App\Http\Controllers;

use App\Models\UserPost;
use Illuminate\Http\Request;

class AudioController extends Controller
{

    // to search in profile audio page
    public function audioSearch(Request $request)
    {
        $user = whichUserIsBeingViewed();

        if ($request->audio_search_for == 'audio-albums') {
            $user_paid_audios = $user->posts()->where('type', 'audio')->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%')
                    ->where('is_paid', 1);
            })->paginate(8);

            $pagination = $user_paid_audios->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-audio.paid-audio', compact(['user_paid_audios']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_paid_audios->lastPage()
            ]);
        }
        if ($request->audio_search_for == 'all-audio') {
            $user_free_audios = $user->posts()->where('type', 'audio')->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%')
                    ->where('is_paid', 0);
            })->paginate(8);

            $pagination = $user_free_audios->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-audio.free-audio', compact(['user_free_audios']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_free_audios->lastPage()
            ]);
        }
    }

    public function allAudiosSearch(Request $request)
    {

        if ($request->audio_search_for == 'member-paid-audio') {
            $user_paid_audios = UserPost::withCount('likers','comments')
            ->where('type', 'audio')
                ->where('is_paid', 1)
                ->where(function ($query) use ($request) {
                    $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                })->orderByDesc('likers_count')->paginate(8);

            $pagination = $user_paid_audios->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-audio.paid-audio', compact(['user_paid_audios']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_paid_audios->lastPage(),
                'total' => (string) $user_paid_audios->total()

            ]);
        }

        if ($request->audio_search_for == 'member-free-audio') {
            $user_free_audios = UserPost::withCount('likers','comments')
            ->where('type', 'audio')
                ->where('is_paid', 0)
                ->where(function ($query) use ($request) {
                    $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                })->orderByDesc('likers_count')->paginate(8);

            $pagination = $user_free_audios->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-audio.free-audio', compact(['user_free_audios']))->render();
            // dd($user_free_audios->lastPage());

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_free_audios->lastPage(),
                'total' => (string) $user_free_audios->total()

            ]);
        }

    }
}
