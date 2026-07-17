<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Search;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request)
    {
        $validated = $request->validate([
            'type' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'price_min' => 'nullable|numeric|min:0',
            'price_max' => 'nullable|numeric|min:0|gte:price_min',
            'area_min' => 'nullable|numeric|min:0',
            'area_max' => 'nullable|numeric|min:0|gte:area_min',
        ]);

        $query = Property::query()
            ->approved()
            ->withFavoriteState($request->user()->id);

        if ($request->filled('type')) {
            $query->where('type', 'like', '%' . $validated['type'] . '%');
        }

        if ($request->filled('location')) {
            $query->where('location', 'like', '%' . $validated['location'] . '%');
        }

        if ($request->filled('price_min')) {
            $query->where('price', '>=', $validated['price_min']);
        }

        if ($request->filled('price_max')) {
            $query->where('price', '<=', $validated['price_max']);
        }

        if ($request->filled('area_min')) {
            $query->where('area', '>=', $validated['area_min']);
        }

        if ($request->filled('area_max')) {
            $query->where('area', '<=', $validated['area_max']);
        }

        $results = $query
            ->with(['images', 'user:id,name'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->orderByDesc('price')
            ->get();

        $hasAnySearchFilter = collect(['type', 'location', 'price_min', 'price_max', 'area_min', 'area_max'])
            ->contains(fn (string $field) => $request->filled($field));

        if ($hasAnySearchFilter) {
            Search::create([
                'user_id' => $request->user()->id,
                'type' => $validated['type'] ?? null,
                'location' => $validated['location'] ?? null,
                'price_max' => $validated['price_max'] ?? null,
                'price_min' => $validated['price_min'] ?? null,
                'area_max' => $validated['area_max'] ?? null,
                'area_min' => $validated['area_min'] ?? null,
            ]);
        }

        return response()->json(['results' => $results]);
    }
}
