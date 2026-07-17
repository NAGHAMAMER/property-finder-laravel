<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class ReceiveMessage extends Notification
{
    use Queueable;

    public function __construct(protected $message)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'رسالة جديدة',
            'message_id' => $this->message->id,
            'sender_id' => $this->message->sender_id,
            'property_id' => $this->message->property_id,
            'content' => $this->message->content,
        ];
    }
}
