<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertyMatched extends Notification
{
    use Queueable;

    public function __construct(protected Property $property)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'notification_type' => 'property_match',
            'title' => 'تمت إضافة عقار جديد يناسب بحثك',
            'message' => "العقار رقم {$this->property->id} في {$this->property->location} يطابق معايير بحثك.",
            'property_id' => $this->property->id,
            'property_type' => $this->property->type,
            'location' => $this->property->location,
            'price' => $this->property->price,
        ];
    }
}
