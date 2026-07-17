<?php

namespace App\Http\Controllers\Admin;

use App\Events\UserNotificationCreated;
use App\Http\Controllers\Controller;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Notifications\PropertyApprovalStatusChanged;
use App\Services\PropertyMatchNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AdminPropertyController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'q' => 'nullable|string|max:255',
            'approval_status' => 'nullable|in:pending,approved,rejected',
        ]);

        $query = Property::query()
            ->with('user:id,name,email')
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'documents']);

        if ($request->filled('approval_status')) {
            $query->where('approval_status', $request->input('approval_status'));
        }

        if ($request->filled('q')) {
            $search = $request->string('q')->toString();
            $query->where(function ($builder) use ($search) {
                $builder->where('location', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        $properties = $query->latest()->paginate(15)->withQueryString();

        $stats = [
            'all' => Property::count(),
            'pending' => Property::where('approval_status', 'pending')->count(),
            'approved' => Property::where('approval_status', 'approved')->count(),
            'rejected' => Property::where('approval_status', 'rejected')->count(),
        ];

        return view('admin.properties.index', compact('properties', 'stats'));
    }

    public function show(Property $property)
    {
        $property->load([
            'user:id,name,email,created_at',
            'reviewer:id,name',
            'documents',
            'images',
            'detailed_locations',
            'ratings.user:id,name',
        ])->loadAvg('ratings', 'rating')->loadCount('ratings');

        return view('admin.properties.show', compact('property'));
    }

    public function approve(Property $property, PropertyMatchNotifier $matchNotifier)
    {
        $wasApproved = $property->approval_status === 'approved';

        $property->update([
            'approval_status' => 'approved',
            'rejection_reason' => null,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $notification = new PropertyApprovalStatusChanged($property, 'approved');
        $property->user->notify($notification);
        try {
            event(new UserNotificationCreated((int) $property->user_id, $notification->payload()));
        } catch (Throwable $exception) {
            report($exception);
        }

        if (! $wasApproved) {
            $matchNotifier->notify($property);
        }

        return back()->with('success', 'تمت الموافقة على العقار وإرسال إشعار إلى صاحبه.');
    }

    public function reject(Request $request, Property $property)
    {
        $validated = $request->validate([
            'rejection_reason' => 'required|string|min:3|max:1000',
        ]);

        $property->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $validated['rejection_reason'],
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);

        $notification = new PropertyApprovalStatusChanged(
            $property,
            'rejected',
            $validated['rejection_reason']
        );
        $property->user->notify($notification);
        try {
            event(new UserNotificationCreated((int) $property->user_id, $notification->payload()));
        } catch (Throwable $exception) {
            report($exception);
        }

        return back()->with('success', 'تم رفض العقار وإرسال السبب إلى صاحبه.');
    }

    public function destroy(Property $property)
    {
        $property->delete();

        return redirect()->route('admin.dashboard')->with('success', 'تم حذف العقار نهائيًا.');
    }

    public function downloadDocument(PropertyDocument $document)
    {
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }
}
