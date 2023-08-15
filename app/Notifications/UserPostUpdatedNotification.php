<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserPostUpdatedNotification extends Notification
{
    use Queueable;

    public $data;
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['database', 'broadcast'];
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
            // 'count' => $this->data['count'],
            'comment_id' => $this->data['comment_id'],
            'from' => $this->data['from'],
            'message' => '',
            'type' => ''
        ];
    }


    public function toDatabase($notifiable)
    {
        return [
            'post_id' => $this->data['post_id'],
            'receiver_id' => $this->data['receiver_id'],
            'type' => 'post_updated'
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
            'post_id' => $this->data['post_id'],
            'receiver_id' => $this->data['receiver_id'],
            'unreadCount' => getUserNotificationsCount($this->data['receiver_id'], 'notification'),
            'notification_id' => $this->id
        ]);
    }


    public function broadcastType()
    {
        return 'post.updated.notification';
    }
}
