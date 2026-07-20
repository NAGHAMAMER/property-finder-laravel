<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NotificationController extends Controller
{
    public function all_notifications(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->get();

        // عند فتح صفحة الإشعارات نعتبر الإشعارات غير المقروءة مقروءة.
        $user->unreadNotifications->each->markAsRead();

        return response()->json([
            'success' => true,
            'data' => $this->serializeNotifications($notifications, (int) $user->id),
        ]);
    }

    /**
     * مسار خفيف للتحديث الدوري والاتصال اللحظي، ولا يغيّر حالة القراءة.
     */
    public function live(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()->latest()->limit(50)->get();

        return response()->json([
            'success' => true,
            'data' => $this->serializeNotifications($notifications, (int) $user->id),
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    /**
     * توحيد شكل استجابة الإشعارات للويب وتطبيق الموبايل.
     *
     * بدل أن تضطر الواجهة للبحث داخل data بأكثر من شكل، نعيد حقولًا
     * واضحة مثل property_id وproperty_url وchat_url في المستوى الأعلى.
     */
    private function serializeNotifications(Collection|EloquentCollection $notifications, int $currentUserId): array
    {
        $normalizedData = $notifications->mapWithKeys(function (DatabaseNotification $notification): array {
            return [$notification->id => $this->normalizeData($notification->data)];
        });

        $propertyIds = $normalizedData
            ->map(fn (array $data): ?int => $this->extractPropertyId($data))
            ->filter()
            ->unique()
            ->values();

        $properties = Property::query()
            ->whereIn('id', $propertyIds)
            ->get(['id', 'user_id', 'type', 'location', 'approval_status'])
            ->keyBy('id');

        return $notifications
            ->map(function (DatabaseNotification $notification) use ($normalizedData, $properties, $currentUserId): array {
                $data = $normalizedData->get($notification->id, []);
                $propertyId = $this->extractPropertyId($data);
                $senderId = $this->extractSenderId($data);
                $property = $propertyId ? $properties->get($propertyId) : null;

                // العقار المعتمد متاح للجميع، وغير المعتمد متاح لصاحبه فقط.
                $canOpenProperty = $property
                    && (
                        $property->approval_status === 'approved'
                        || (int) $property->user_id === $currentUserId
                    );

                $title = (string) ($data['title'] ?? $this->defaultTitle($notification));
                $message = (string) (
                    $data['message']
                    ?? $data['body']
                    ?? $data['content']
                    ?? $data['title']
                    ?? 'إشعار جديد'
                );

                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'notification_type' => $data['notification_type'] ?? $this->notificationType($notification),
                    'title' => $title,
                    'message' => $message,
                    'data' => $data,
                    'property_id' => $propertyId,
                    'sender_id' => $senderId,
                    'property_url' => $canOpenProperty
                        ? route('user.properties.show', ['id' => $propertyId], false)
                        : null,
                    'chat_url' => ($property && $senderId)
                        ? route('user.chats.show', [
                            'propertyId' => $propertyId,
                            'otherUserId' => $senderId,
                        ], false)
                        : null,
                    'property' => $property ? [
                        'id' => $property->id,
                        'type' => $property->type,
                        'location' => $property->location,
                        'approval_status' => $property->approval_status,
                    ] : null,
                    'read_at' => $notification->read_at?->toISOString(),
                    'created_at' => $notification->created_at?->toISOString(),
                ];
            })
            ->values()
            ->all();
    }

    private function normalizeData(mixed $data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            return (array) $data;
        }

        if (is_string($data)) {
            $decoded = json_decode($data, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    private function extractPropertyId(array $data): ?int
    {
        $value = $data['property_id']
            ?? $data['propertyId']
            ?? data_get($data, 'property.id');

        if (is_numeric($value) && (int) $value > 0) {
            return (int) $value;
        }

        // دعم الإشعارات القديمة التي كان رقم العقار موجودًا داخل النص فقط.
        $text = implode(' ', array_filter([
            $data['title'] ?? null,
            $data['message'] ?? null,
            $data['body'] ?? null,
            $data['content'] ?? null,
        ], fn ($item) => is_string($item)));

        if (preg_match('/(?:العقار|عقار)\s*(?:رقم|#)?\s*(\d+)/u', $text, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function extractSenderId(array $data): ?int
    {
        $value = $data['sender_id']
            ?? $data['senderId']
            ?? data_get($data, 'sender.id');

        return is_numeric($value) && (int) $value > 0
            ? (int) $value
            : null;
    }

    private function notificationType(DatabaseNotification $notification): string
    {
        return match (class_basename($notification->type)) {
            'PropertyMatched' => 'property_match',
            'PropertyApprovalStatusChanged' => 'property_approval',
            'ReceiveMessage' => 'message',
            default => Str::snake(class_basename($notification->type)),
        };
    }

    private function defaultTitle(DatabaseNotification $notification): string
    {
        return match ($this->notificationType($notification)) {
            'property_match' => 'عقار جديد مطابق لبحثك',
            'property_approval' => 'تحديث حالة العقار',
            'message' => 'رسالة جديدة',
            default => 'إشعار جديد',
        };
    }
}
