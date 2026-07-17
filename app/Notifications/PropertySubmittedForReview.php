<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertySubmittedForReview extends Notification
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
            'title' => 'طلب إضافة عقار جديد',
            'message' => "أرسل {$this->property->user?->name} عقارًا جديدًا للمراجعة.",
            'property_id' => $this->property->id,
            'status' => 'pending',
        ];
    }
}
