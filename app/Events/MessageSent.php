<?php

namespace App\Events;

use App\Models\Messages;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Messages $message)
    {
        $this->message->loadMissing('sender:id,name');
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('App.Models.User.' . $this->message->receiver_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    public function broadcastWith(): array
    {
        return [
            'message' => [
                'id' => $this->message->id,
                'sender_id' => $this->message->sender_id,
                'receiver_id' => $this->message->receiver_id,
                'property_id' => $this->message->property_id,
                'content' => $this->message->content,
                'created_at' => optional($this->message->created_at)->toISOString(),
                'sender' => $this->message->sender ? [
                    'id' => $this->message->sender->id,
                    'name' => $this->message->sender->name,
                ] : null,
            ],
        ];
    }
}
