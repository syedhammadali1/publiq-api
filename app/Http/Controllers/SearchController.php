<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SearchController extends Controller
{

    public function onlineSearch(Request $request)
    {
        // $onlineUsers = User::where('id', auth()->id())
        //     ->where('name', 'LIKE', '%' . $request->keyword . '%')
        //     ->get();

        $onlineUsers = auth()->user()->followings()->whereNotNull('accepted_at')
            ->whereHas('followable', function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->keyword . '%')
                    ->orWhere('email', 'like', '%' . $request->keyword . '%');
            })->take(8)->get()->map(function ($q) {
                return $q->followable;
            });

        return view('components.RightSideBar.loopOnlinePeople', [
            'onlineUsers' => $onlineUsers
        ]);
    }

    public function globalSearch(Request $request)
    {


        if ($request->suggestion) {
            if ($request->global_search_filter == 'Member') {
                $users = User::with('detail')
                    ->whereRelation('detail', DB::raw('CONCAT_WS(" ", first_name, last_name)'), 'like', '%' . $request->suggestion . '%')
                    ->take(10)
                    ->get()
                    ->pluck('full_name');
                return response()->json([
                    'data' => $users,
                ]);
            } elseif ($request->global_search_filter == 'Post') {
                $user_posts = UserPost::where('title', 'like',  '%' . $request->suggestion . '%')
                    ->take(5)->get()
                    ->pluck('title');
                return response()->json([
                    'data' => $user_posts
                ]);
            }
        }

        if ($request->global_search_filter == 'Member') {
            if ($request->page) {
                $users = User::where(function ($query) use ($request) {
                    $query->orWhereRelation('detail', DB::raw('CONCAT_WS(" ", first_name, last_name)'), 'like', '%' . $request->global_search_keyword . '%')
                        ->orWhere('name', 'like', '%' . $request->global_search_keyword . '%');
                })->whereNot('id', auth()->id())
                    ->paginate(8);

                $pagination = $users->appends(array(
                    'global_search_keyword' => $request->global_search_keyword,
                    'global_search_filter' => $request->global_search_filter
                ));

                $html = view('components.GlobalSearch.Member.member-loop', compact(['users']))->render();

                return response()->json([
                    'html' => $html,
                    'lastPage' => (string) $users->lastPage()
                ]);
            }

            $users = User::where(function ($query) use ($request) {
                $query->orWhereRelation('detail', DB::raw('CONCAT_WS(" ", first_name, last_name)'), 'like', '%' . $request->global_search_keyword . '%')
                    ->orWhere('name', 'like', '%' . $request->global_search_keyword . '%');
            })->whereNot('id', auth()->id())
                ->paginate(8);


            return view('frontend.pages.global-search', [
                'for' => 'member',
                'users' => $users
            ]);
        }

        if ($request->global_search_filter == 'Post') {


            if ($request->page) {
                $user_posts = UserPost::withCount('comments', 'likers')
                    ->with('media', 'user')
                    ->where('title', 'like',  '%' . $request->global_search_keyword . '%')
                    ->orderBy('created_at', 'desc')
                    ->paginate(8);

                $pagination = $user_posts->appends(array(
                    'global_search_keyword' => $request->global_search_keyword,
                    'global_search_filter' => $request->global_search_filter
                ));





                $html = view('components.Home.NewsFeed.foreachFeedPost', compact(['user_posts']))->render();

                return response()->json([
                    'html' => $html,
                    'lastPage' => (string) $user_posts->lastPage()
                ]);
            }

            $user_posts = UserPost::withCount('comments', 'likers')
                ->with('media', 'user')
                ->where('title', 'like',  '%' . $request->global_search_keyword . '%')
                ->orderBy('created_at', 'desc')
                ->paginate(8);
            return view('frontend.pages.global-search', [
                'for' => 'post',
                'user_posts' => $user_posts,
            ]);
        }
    }
}
