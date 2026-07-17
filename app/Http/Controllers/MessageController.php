<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\UserNotificationCreated;
use App\Models\Messages;
use App\Models\Property;
use App\Models\User;
use App\Notifications\ReceiveMessage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class MessageController extends Controller
{
    /**
     * Start a property-specific conversation. The receiver is always the
     * approved property's owner. Normal conversations without a property are
     * intentionally not supported.
     */
    public function send_message(Request $request, int|string $property_id): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $property = Property::query()->approved()->findOrFail((int) $property_id);
        $senderId = (int) $request->user()->id;
        $receiverId = (int) $property->user_id;

        abort_if($senderId === $receiverId, 422, 'لا يمكنك إرسال رسالة إلى نفسك.');

        $message = $this->createAndDeliverMessage(
            senderId: $senderId,
            receiverId: $receiverId,
            property: $property,
            content: $validated['content'],
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرسالة وفتح محادثة مرتبطة بالعقار.',
            'data' => $message,
            'thread' => [
                'property_id' => (int) $property->id,
                'other_user_id' => $receiverId,
            ],
        ], 201);
    }

    /** Reply to an existing property-specific conversation. */
    public function reply(Request $request, int|string $propertyId, int|string $otherUserId): JsonResponse
    {
        $validated = $request->validate([
            'content' => ['required', 'string', 'max:5000'],
        ]);

        $currentUserId = (int) $request->user()->id;
        $otherUserId = (int) $otherUserId;
        $property = Property::query()->findOrFail((int) $propertyId);

        User::query()->findOrFail($otherUserId);
        $this->authorizePropertyConversation($property, $currentUserId, $otherUserId, true);

        $message = $this->createAndDeliverMessage(
            senderId: $currentUserId,
            receiverId: $otherUserId,
            property: $property,
            content: $validated['content'],
        );

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرد.',
            'data' => $message,
        ], 201);
    }

    /**
     * Return one thread per (property + other user). This keeps conversations
     * about different properties separate even when the participants match.
     */
    public function show_chats(Request $request): JsonResponse
    {
        $userId = (int) $request->user()->id;

        $messages = Messages::query()
            ->with([
                'sender:id,name',
                'receiver:id,name',
                'property:id,type,location,price,status,approval_status,user_id',
            ])
            ->where(function (Builder $query) use ($userId): void {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->latest('id')
            ->get();

        $threads = $messages
            ->groupBy(function (Messages $message) use ($userId): string {
                $otherUserId = (int) $message->sender_id === $userId
                    ? (int) $message->receiver_id
                    : (int) $message->sender_id;

                return (int) $message->property_id . ':' . $otherUserId;
            })
            ->map(function (Collection $threadMessages) use ($userId): array {
                /** @var Messages $lastMessage */
                $lastMessage = $threadMessages->first();
                $otherUser = (int) $lastMessage->sender_id === $userId
                    ? $lastMessage->receiver
                    : $lastMessage->sender;

                $unreadCount = $threadMessages->filter(
                    fn (Messages $message): bool =>
                        (int) $message->sender_id === (int) $otherUser?->id
                        && (int) $message->receiver_id === $userId
                        && ! (bool) $message->is_read
                )->count();

                return [
                    'property_id' => (int) $lastMessage->property_id,
                    'property' => $this->propertySummary($lastMessage->property),
                    'other_user' => [
                        'id' => (int) $otherUser?->id,
                        'name' => $otherUser?->name ?? 'مستخدم',
                    ],
                    // Compatibility with older clients.
                    'id' => (int) $otherUser?->id,
                    'name' => $otherUser?->name ?? 'مستخدم',
                    'unread_count' => $unreadCount,
                    'last_message' => $lastMessage->content,
                    'last_message_at' => optional($lastMessage->created_at)->toISOString(),
                ];
            })
            ->sortByDesc('last_message_at')
            ->values();

        return response()->json([
            'success' => true,
            'data' => $threads,
        ]);
    }

    /** Display one property-specific conversation and mark received messages read. */
    public function showPropertyChat(Request $request, int|string $propertyId, int|string $otherUserId): JsonResponse
    {
        $currentUserId = (int) $request->user()->id;
        $otherUserId = (int) $otherUserId;
        $property = Property::query()->findOrFail((int) $propertyId);
        $otherUser = User::query()->select('id', 'name')->findOrFail($otherUserId);

        $this->authorizePropertyConversation($property, $currentUserId, $otherUserId, true);

        $messages = $this->conversationQuery(
            propertyId: (int) $property->id,
            firstUserId: $currentUserId,
            secondUserId: $otherUserId,
        )
            ->select('id', 'sender_id', 'receiver_id', 'property_id', 'content', 'created_at', 'is_read')
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        Messages::query()
            ->where('property_id', $property->id)
            ->where('sender_id', $otherUserId)
            ->where('receiver_id', $currentUserId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json([
            'success' => true,
            'data' => [
                'property' => $this->propertySummary($property),
                'other_user' => [
                    'id' => (int) $otherUser->id,
                    'name' => $otherUser->name,
                ],
                'messages' => $messages,
            ],
        ]);
    }

    /** Backward-compatible endpoint: open the latest property thread with a user. */
    public function show_one_chat(Request $request, int|string $receiver): JsonResponse
    {
        $currentUserId = (int) $request->user()->id;
        $otherUserId = (int) str_replace(['{', '}'], '', (string) $receiver);
        User::query()->findOrFail($otherUserId);

        $latestMessage = Messages::query()
            ->where(function (Builder $query) use ($currentUserId, $otherUserId): void {
                $query->where(function (Builder $pair) use ($currentUserId, $otherUserId): void {
                    $pair->where('sender_id', $currentUserId)->where('receiver_id', $otherUserId);
                })->orWhere(function (Builder $pair) use ($currentUserId, $otherUserId): void {
                    $pair->where('sender_id', $otherUserId)->where('receiver_id', $currentUserId);
                });
            })
            ->latest('id')
            ->firstOrFail();

        return $this->showPropertyChat($request, (int) $latestMessage->property_id, $otherUserId);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'unread_count' => Messages::query()
                    ->where('receiver_id', $request->user()->id)
                    ->where('is_read', false)
                    ->count(),
            ],
        ]);
    }

    private function conversationQuery(int $propertyId, int $firstUserId, int $secondUserId): Builder
    {
        return Messages::query()
            ->where('property_id', $propertyId)
            ->where(function (Builder $query) use ($firstUserId, $secondUserId): void {
                $query->where(function (Builder $pair) use ($firstUserId, $secondUserId): void {
                    $pair->where('sender_id', $firstUserId)->where('receiver_id', $secondUserId);
                })->orWhere(function (Builder $pair) use ($firstUserId, $secondUserId): void {
                    $pair->where('sender_id', $secondUserId)->where('receiver_id', $firstUserId);
                });
            });
    }

    private function authorizePropertyConversation(
        Property $property,
        int $currentUserId,
        int $otherUserId,
        bool $requireExistingThread,
    ): void {
        abort_if($currentUserId === $otherUserId, 422, 'لا يمكنك فتح محادثة مع نفسك.');

        $ownerId = (int) $property->user_id;
        $validPair = ($currentUserId === $ownerId && $otherUserId !== $ownerId)
            || ($otherUserId === $ownerId && $currentUserId !== $ownerId);

        abort_unless($validPair, 403, 'هذه المحادثة لا تخص صاحب العقار والمستخدم المهتم به.');

        if ($requireExistingThread) {
            abort_unless(
                $this->conversationQuery((int) $property->id, $currentUserId, $otherUserId)->exists(),
                404,
                'لا توجد محادثة سابقة مرتبطة بهذا العقار.'
            );
        }
    }

    private function createAndDeliverMessage(
        int $senderId,
        int $receiverId,
        Property $property,
        string $content,
    ): Messages {
        $message = Messages::query()->create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'property_id' => $property->id,
            'content' => trim($content),
        ])->load([
            'sender:id,name',
            'receiver:id,name',
            'property:id,type,location,price,status,approval_status,user_id',
        ]);

        $receiver = User::query()->findOrFail($receiverId);
        $receiver->notify(new ReceiveMessage($message));

        // A Pusher outage must never prevent the database message/notification.
        try {
            event(new MessageSent($message));
            event(new UserNotificationCreated($receiverId, [
                'type' => 'message',
                'title' => 'رسالة جديدة حول عقار',
                'message_id' => $message->id,
                'sender_id' => $message->sender_id,
                'sender_name' => $message->sender?->name,
                'property_id' => $message->property_id,
                'property_type' => $message->property?->type,
                'property_location' => $message->property?->location,
                'content' => $message->content,
                'created_at' => optional($message->created_at)->toISOString(),
            ]));
        } catch (Throwable $exception) {
            report($exception);
        }

        return $message;
    }

    private function propertySummary(?Property $property): ?array
    {
        if (! $property) {
            return null;
        }

        return [
            'id' => (int) $property->id,
            'type' => $property->type,
            'location' => $property->location,
            'price' => $property->price,
            'status' => $property->status,
            'approval_status' => $property->approval_status,
            'user_id' => (int) $property->user_id,
        ];
    }
}
