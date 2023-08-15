<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class MemberController extends Controller
{
    public function allMembersSearch(Request $request)
    {
        $users = User::withCount('subscribers')->with('media')
            ->where(function ($query) use ($request) {
                $query->orWhere('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('email', 'like', '%' . $request->keyword . '%');
            })->whereNot('id', auth()->id())->orderByDesc('subscribers_count')->paginate(8);

        $pagination = $users->appends(array(
            'keyword' => $request->keyword
        ));

        $html = view('components.sub-components.member.member-lop', compact('users'))->render();
        // dd($user_free_photos->lastPage());

        return response()->json([
            'html' => $html,
            'lastPage' => (string) $users->lastPage(),
            'total' => (string) $users->total()
        ]);
    }

    // to show all members page
    public function members()
    {
        $users = User::withCount('subscribers')->with('media')->whereNot('id',auth()->id())->orderByDesc('subscribers_count')->paginate(8);
        return view('frontend.pages.member', compact('users'));
    }
}
