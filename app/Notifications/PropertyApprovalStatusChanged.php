<?php

namespace App\Notifications;

use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropertyApprovalStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        protected Property $property,
        protected string $status,
        protected ?string $reason = null,
    ) {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return $this->payload();
    }

    public function payload(): array
    {
        $approved = $this->status === 'approved';

        return [
            'title' => $approved ? 'تمت الموافقة على العقار' : 'تم رفض إضافة العقار',
            'message' => $approved
                ? "تمت الموافقة على العقار رقم {$this->property->id} وأصبح ظاهرًا للمستخدمين."
                : "تم رفض العقار رقم {$this->property->id}." . ($this->reason ? " السبب: {$this->reason}" : ''),
            'property_id' => $this->property->id,
            'status' => $this->status,
            'reason' => $this->reason,
        ];
    }
}
