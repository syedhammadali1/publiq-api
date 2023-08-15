<?php

namespace App\Notifications;

use App\Models\Notification as ModelsNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ChatNotification extends Notification
{
    // use Queueable;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public $notification;
    public function __construct(public $data)
    {
        $this->data = $data;
        $this->notification = ModelsNotification::where('data->sender_id', $this->data['sender_id'])
            ->where('data->receiver_id', $this->data['receiver_id'])
            ->where('data->type', 'chat_room')
            ->first();

        if ($this->notification) {
            $this->notification->update([
                'data' => [
                    'sender_id' => $this->data['sender_id'],
                    'receiver_id' => $this->data['receiver_id'],
                    'last_message' => $this->data['last_message'],
                    'unseen' => $this->data['unseen'],
                    'type' => 'chat_room'
                ],
                'created_at' => now(),
                'read_at' => null
            ]);
        }
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return (!$this->notification) ? ['database', 'broadcast'] : ['broadcast'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->line('The introduction to the notification.')
            ->action('Notification Action', url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            'message' => 'how are you dear'
        ];
    }


    public function toDatabase($notifiable)
    {
        return [
            'sender_id' => $this->data['sender_id'],
            'receiver_id' => $this->data['receiver_id'],
            'last_message' => $this->data['last_message'],
            'unseen' => $this->data['unseen'],
            'type' => 'chat_room'
        ];
    }


    /**
     * Get the broadcastable representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return BroadcastMessage
     */
    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'notification_id' => (!$this->notification) ? $this->id : $this->notification->id,
            // 'sender_id' => $this->data['sender_id'],
            // 'receiver_id' => $this->data['receiver_id'],
            // 'last_message' => $this->data['last_message'],
            // 'unseen' => $this->data['unseen'],
            'unreadCount' => getUserNotificationsCount($this->data['receiver_id'], 'message'),
            'type' => 'chat_room'
        ]);
    }


    public function broadcastType()
    {
        return 'chat.room';
    }
}
