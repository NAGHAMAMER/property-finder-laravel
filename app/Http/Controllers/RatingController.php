<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Rating;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function index(Property $property)
    {
        abort_unless($property->approval_status === 'approved', 404);

        return response()->json([
            'success' => true,
            'average_rating' => round((float) $property->ratings()->avg('rating'), 2),
            'ratings_count' => $property->ratings()->count(),
            'data' => $property->ratings()->with('user:id,name')->latest()->get(),
        ]);
    }

    public function store(Request $request, Property $property)
    {
        abort_unless($property->approval_status === 'approved', 422, 'لا يمكن تقييم عقار غير معتمد.');
        abort_if((int) $property->user_id === (int) $request->user()->id, 422, 'لا يمكنك تقييم عقارك الخاص.');

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $rating = Rating::updateOrCreate(
            [
                'property_id' => $property->id,
                'user_id' => $request->user()->id,
            ],
            $validated
        );

        return response()->json([
            'success' => true,
            'message' => $rating->wasRecentlyCreated ? 'تمت إضافة التقييم.' : 'تم تحديث تقييمك.',
            'data' => $rating->load('user:id,name'),
            'average_rating' => round((float) $property->ratings()->avg('rating'), 2),
            'ratings_count' => $property->ratings()->count(),
        ], $rating->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Request $request, Property $property)
    {
        $deleted = Rating::where('property_id', $property->id)
            ->where('user_id', $request->user()->id)
            ->delete();

        abort_unless($deleted, 404, 'لا يوجد تقييم لحذفه.');

        return response()->json([
            'success' => true,
            'message' => 'تم حذف تقييمك.',
        ]);
    }
}
