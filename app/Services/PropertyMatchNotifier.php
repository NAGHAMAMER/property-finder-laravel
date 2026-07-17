<?php

namespace App\Services;

use App\Events\UserNotificationCreated;
use App\Models\Property;
use App\Models\Search;
use App\Models\User;
use App\Notifications\PropertyMatched;
use Illuminate\Support\Str;
use Throwable;

class PropertyMatchNotifier
{
    public function notify(Property $property): void
    {
        $searches = Search::query()->get()->filter(function (Search $search) use ($property): bool {
            if ($search->type && ! Str::contains(Str::lower($property->type), Str::lower($search->type))) {
                return false;
            }

            if ($search->location && ! Str::contains(Str::lower($property->location), Str::lower($search->location))) {
                return false;
            }

            if ($search->price_min !== null && $property->price < $search->price_min) {
                return false;
            }

            if ($search->price_max !== null && $property->price > $search->price_max) {
                return false;
            }

            if ($search->area_min !== null && $property->area < $search->area_min) {
                return false;
            }

            if ($search->area_max !== null && $property->area > $search->area_max) {
                return false;
            }

            return true;
        });

        $userIds = $searches->pluck('user_id')
            ->reject(fn ($id) => (int) $id === (int) $property->user_id)
            ->unique();

        User::query()->whereIn('id', $userIds)->get()->each(function (User $user) use ($property): void {
            $notification = new PropertyMatched($property);
            $user->notify($notification);

            try {
                event(new UserNotificationCreated((int) $user->id, [
                    'type' => 'property_match',
                    'title' => 'تمت إضافة عقار جديد يناسب بحثك',
                    'property_id' => $property->id,
                    'location' => $property->location,
                    'price' => $property->price,
                ]));
            } catch (Throwable $exception) {
                report($exception);
            }
        });
    }
}
