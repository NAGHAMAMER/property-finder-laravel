<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Property;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $this->ensureRegularUser($request);

        $properties = $request->user()
            ->favoriteProperties()
            ->approved()
            ->with(['user:id,name', 'images', 'detailed_locations'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->orderByPivot('created_at', 'desc')
            ->get();

        $properties->each(function (Property $property) {
            $property->setAttribute('is_favorite', true);
            $property->makeHidden('pivot');
        });

        return response()->json([
            'success' => true,
            'count' => $properties->count(),
            'data' => $properties,
        ]);
    }

    public function store(Request $request, Property $property)
    {
        $this->ensureRegularUser($request);
        abort_unless($property->approval_status === 'approved', 422, 'لا يمكن إضافة عقار غير معتمد إلى المفضلة.');

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'property_id' => $property->id,
        ]);

        $property->load(['user:id,name', 'images', 'detailed_locations']);
        $property->loadAvg('ratings', 'rating');
        $property->loadCount('ratings');
        $property->setAttribute('is_favorite', true);

        return response()->json([
            'success' => true,
            'message' => $favorite->wasRecentlyCreated
                ? 'تمت إضافة العقار إلى المفضلة.'
                : 'العقار موجود في المفضلة مسبقًا.',
            'data' => $property,
        ], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Property $property)
    {
        $this->ensureRegularUser($request);

        $deleted = Favorite::query()
            ->where('user_id', $request->user()->id)
            ->where('property_id', $property->id)
            ->delete();

        abort_unless($deleted, 404, 'العقار غير موجود في المفضلة.');

        return response()->json([
            'success' => true,
            'message' => 'تمت إزالة العقار من المفضلة.',
            'property_id' => $property->id,
            'is_favorite' => false,
        ]);
    }

    private function ensureRegularUser(Request $request): void
    {
        abort_if($request->user()->isAdmin(), 403, 'المفضلة متاحة للمستخدمين العاديين فقط.');
    }
}
