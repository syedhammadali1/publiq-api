<?php

namespace App\Http\Controllers;

use App\Models\UserPost;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    // to search photo in photo section in profile page
    public function photoSearch(Request $request)
    {
        $user = whichUserIsBeingViewed();

        if ($request->photo_search_for == 'photos-albums') {
            $user_paid_photos = $user->posts()->where('type', 'images')->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%')
                    ->where('is_paid', 1);
            })->paginate(8);

            $pagination = $user_paid_photos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-photo.paid-photo', compact(['user_paid_photos']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_paid_photos->lastPage(),
                'total' => (string) $user_paid_photos->total()
            ]);
        }
        if ($request->photo_search_for == 'all-photos') {
            $user_free_photos = $user->free_posts('images')->where('type', 'images')->where(function ($query) use ($request) {
                $query->orWhere('title', 'like', '%' . $request->keyword . '%');
            })->paginate(8);

            $pagination = $user_free_photos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-photo.free-photo', compact(['user_free_photos']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_free_photos->lastPage(),
                'total' => (string) $user_free_photos->total()
            ]);
        }
    }

    // to search photos in all photos page
    public function allPhotosSearch(Request $request)
    {


        if ($request->photo_search_for == 'member-paid-photos') {
            $user_paid_photos = UserPost::withCount('likers','comments')
                ->where('type', 'images')
                ->where('is_paid', 1)
                ->where(function ($query) use ($request) {
                    $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                })->orderByDesc('likers_count')->paginate(8);

            $pagination = $user_paid_photos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-photo.paid-photo', compact(['user_paid_photos']))->render();

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_paid_photos->lastPage(),
                'total' => (string) $user_paid_photos->total()
            ]);
        }
        if ($request->photo_search_for == 'member-free-photos') {
            $user_free_photos = UserPost::withCount('likers','comments')
            ->where('type', 'images')
                ->where('is_paid', 0)
                ->where(function ($query) use ($request) {
                    $query->orWhere('title', 'like', '%' . $request->keyword . '%');
                })->orderByDesc('likers_count')->paginate(8);

            $pagination = $user_free_photos->appends(array(
                'keyword' => $request->keyword
            ));

            $html = view('components.sub-components.profile-photo.free-photo', compact(['user_free_photos']))->render();
            // dd($user_free_photos->lastPage());

            return response()->json([
                'html' => $html,
                'lastPage' => (string) $user_free_photos->lastPage(),
                'total' => (string) $user_free_photos->total()
            ]);
        }
    }
}
