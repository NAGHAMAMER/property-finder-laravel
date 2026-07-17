<?php

namespace App\Http\Controllers;

use App\Helpers\Distance;
use App\Models\DetailedLocation;
use App\Models\Property;
use Illuminate\Http\Request;

class DetailedLocations extends Controller
{
    public function add_detailed_locations(Request $request, $property_id)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $property = Property::findOrFail($property_id);
        $this->ensureCanManage($property, $request);

        DetailedLocation::updateOrCreate(
            ['property_id' => $property->id],
            $validated
        );

        return response()->json([
            'message' => 'تم حفظ موقع العقار.',
            'data' => $property->load('detailed_locations'),
        ]);
    }

    public function delet_detailed_locations(Request $request, $property_id)
    {
        $property = Property::findOrFail($property_id);
        $this->ensureCanManage($property, $request);
        $property->detailed_locations()->delete();

        return response()->json([
            'message' => 'تم حذف الموقع التفصيلي.',
            'data' => $property->load('detailed_locations'),
        ]);
    }

    public function nearby_properties(Request $request)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'max_distance' => 'nullable|numeric|min:1|max:1000',
        ]);

        $maxDistance = (float) ($validated['max_distance'] ?? 1000);
        $properties = Property::approved()
            ->withFavoriteState($request->user()->id)
            ->with('detailed_locations')
            ->get();
        $coordinates = collect();

        foreach ($properties as $property) {
            if (! $property->detailed_locations) {
                continue;
            }

            $distance = Distance::distance(
                $validated['latitude'],
                $validated['longitude'],
                $property->detailed_locations->latitude,
                $property->detailed_locations->longitude
            );

            if ($distance <= $maxDistance) {
                $coordinates->push([
                    'id' => $property->id,
                    'latitude' => $property->detailed_locations->latitude,
                    'longitude' => $property->detailed_locations->longitude,
                    'distance' => $distance,
                    'is_favorite' => (bool) $property->is_favorite,
                ]);
            }
        }

        return response()->json([
            'message' => 'تم جلب العقارات القريبة المعتمدة.',
            'data' => $coordinates->sortBy('distance')->values(),
        ]);
    }

    private function ensureCanManage(Property $property, Request $request): void
    {
        $user = $request->user();
        abort_unless($user->isAdmin() || (int) $property->user_id === (int) $user->id, 403);
    }
}
