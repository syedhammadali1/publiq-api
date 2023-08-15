<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class ChatMessage extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected $fillable = ['chat_conversations_id', 'message', 'user_id', 'type', 'seen_at'];

    protected $appends = ['attachment_url'];

    protected $hidden = ['media'];

    public function getAttachmentUrlAttribute()
    {

        if ($this->type == 'media')
            return $this->getFirstMediaUrl('chat');
    }
}
