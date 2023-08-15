<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = ['sender_id', 'receiver_id'];

    /**
     * Get all of the messages for the ChatConversation
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class, 'chat_conversations_id', 'id');
    }

    /**
     * Scope a query to only include unseen messages.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return void
     */
    public function scopeUnseenMessages($query, $sender_id, $reciver_id)
    {
        return $query->join('chat_messages', 'chat_conversations_id', '=', 'chat_conversations.id')
            ->where(function ($query) use ($sender_id, $reciver_id) {
                $query->where('sender_id', '=', $sender_id)
                    ->where('receiver_id', '=', $reciver_id);
            })->whereNull('seen_at')->pluck('chat_messages.id');
    }
}
