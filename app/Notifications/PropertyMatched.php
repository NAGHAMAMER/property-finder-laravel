<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertyMatched extends Notification
{
    use Queueable;

    public function __construct(protected $property)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'تمت إضافة عقار جديد يناسب بحثك',
            'property_id' => $this->property->id,
            'location' => $this->property->location,
            'price' => $this->property->price,
        ];
    }
}
