<?php

namespace App\Http\Controllers;

use App\Events\NewChatMessage;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\Notification;
use App\Models\User;
use App\Notifications\ChatNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ChatMessageController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        $user =  User::whereName($request->username)->first();
        return view('frontend.pages.message.messages', [
            'user' => $user
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function send()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        // dd($urlArray);
        $sender = auth()->user();
        $receiver = User::find($request->chat_with);

        $conversation_id = ChatConversation::updateOrCreate([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ], [
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
        ]);
        $chat = null;



        if ($request->message) {
            $chat = ChatMessage::create([
                'chat_conversations_id' => $conversation_id->id,
                'message' => $request->message,
                'user_id' => $sender->id,
                'type' => 'text',
            ]);

            // user ki noti foran change kerni hai jab vo msg seen keray
            $data = [
                'sender_id' => $sender->id,
                'receiver_id' => $receiver->id,
                'last_message' => $request->message,
                'unseen' => 0
            ];



            broadcast(new NewChatMessage($receiver, $sender, $chat, $data));
        }



        $chat_attachment_url = null;
        $chat_attachment_url_temp = [];
        if ($request->urlArray) {
            $urlArray = explode(',', $request->urlArray);

            foreach ($urlArray as $key => $value) {
                $chat_attachment = ChatMessage::create([
                    'chat_conversations_id' => $conversation_id->id,
                    'message' => '',
                    'user_id' => $sender->id,
                    'type' => 'media',
                ]);

                $chat_attachment_url =  $chat_attachment->addMediaFromUrl($value)->toMediaCollection('chat', 's3');
                $chat_attachment_url = ($chat_attachment_url) ? $chat_attachment_url->getUrl() : '';
                $data = [
                    'sender_id' => $sender->id,
                    'receiver_id' => $receiver->id,
                    'last_message' => 'Attachment',
                    'unseen' => 0
                ];
                broadcast(new NewChatMessage($receiver, $sender, $chat, $data, $chat_attachment_url));
                $chat_attachment_url_temp[] = $chat_attachment_url;
            }
        }


        return response()->json([
            'attachment_url' => $chat_attachment_url_temp
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ChatMessage  $chatMessage
     * @return \Illuminate\Http\Response
     */
    public function show(ChatMessage $chatMessage)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\ChatMessage  $chatMessage
     * @return \Illuminate\Http\Response
     */
    public function edit(ChatMessage $chatMessage, Request $request)
    {
        $chat_with = User::whereName($request->username)->first();
        if (!$chat_with) {
            return response()->json([
                'success' => false,
                "message" => 'Invalid users chat.'
            ]);
        }

        $authUser = auth()->user();

        $id_user = $chat_with->id; // use auth user_id
        $id = $authUser->id; // use auth user_id
        if (!$authUser->isFollowingUser($chat_with)) {
            $array = [
                'icon' => 'fa fa-warning',
                'heading' => 'Follow this user',
                'simple-content' => 'Sorry you cannot chat because your are not following this user',
            ];
            return response()->json([
                'html' => view('components.sub-components.successPopup', ['array' => $array])->render(),
                'chat_with' => $chat_with,
                'cannot_chat' => true
            ]);
        }


        $conversation_ids = $message = ChatConversation::where(function ($query) use ($id, $id_user) {
            $query->where('sender_id', '=', $id_user)
                ->where('receiver_id', '=', $id);
        })->orWhere(function ($query) use ($id, $id_user) {
            $query->where('sender_id', '=', $id)
                ->where('receiver_id', '=', $id_user);
        })->get()->pluck('id');


        $messages = ChatMessage::whereIn('chat_conversations_id', $conversation_ids)->orderBy('created_at', 'asc')->get();
        $unseenMessages = ChatConversation::UnseenMessages($id_user, $id)->toArray();
        $noti = Notification::where('data->receiver_id', $id)
            ->where('data->sender_id', $id_user)
            ->where('data->type', 'chat_room')
            ->first();
        if ($noti) {
            $last_message =  $noti->data['last_message'];
            $noti->update([
                'data' => [
                    'sender_id' => $id_user,
                    'receiver_id' => $id,
                    'last_message' => $last_message,
                    'unseen' => 0,
                    'type' => 'chat_room'
                ]
            ]);
        }

        // to update seen_at
        ChatMessage::whereIn('id', $unseenMessages)->update([
            'seen_at' => now()
        ]);



        return response()->json([
            'html' => view('frontend.pages.chatroom.chat-room-single', [
                'chat_with' => $chat_with,
                'messages' => $messages
            ])->render(),
            'chat_with' => $chat_with
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\ChatMessage  $chatMessage
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ChatMessage $chatMessage)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\ChatMessage  $chatMessage
     * @return \Illuminate\Http\Response
     */
    public function destroy(ChatMessage $chatMessage)
    {
        //
    }


    public function markAsSeen(Request $request)
    {
        if (!$request->username) {
            return response()->json([
                'success' => false,
                "message" => 'Invalid users chat'
            ]);
        }

        $sender = User::whereName($request->username)->first();
        if (!$sender) {
            return response()->json([
                'success' => false,
                "message" => 'Invalid users'
            ]);
        }

        ChatMessage::whereIn('id', ChatConversation::UnseenMessages($sender->id, auth()->id()))->update([
            'seen_at' => now()
        ]);

        return response()->json([
            'success' => true,
        ]);
    }


    public function sendNotification(Request $request)
    {
        if (!$request->username) {
            return response()->json([
                'success' => false,
                "message" => 'Invalid users chat'
            ]);
        }

        $sender = User::whereName($request->username)->first();
        if (!$sender) {
            return response()->json([
                'success' => false,
                "message" => 'Invalid users'
            ]);
        }

        $authUser = auth()->user();
        if (!$authUser->isFollowingUser($sender)) {
            return response()->json([
                'success' => false,
            ]);
        }

        $unseenMessages = ChatConversation::UnseenMessages($sender->id, $authUser->id)->toArray();

        $message = ChatMessage::where('id', end($unseenMessages))->first();

        $authUser->sendNotification('chat_room', [
            'sender_id' => $sender->id,
            'receiver_id' => $authUser->id,
            'last_message' => $message->type == 'text' ? $message->message : 'Attachment',
            'unseen' => count($unseenMessages)
        ]);

        return response()->json([
            'success' => true,
        ]);
    }
}
