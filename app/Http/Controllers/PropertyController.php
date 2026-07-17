<?php

namespace App\Http\Controllers;

use App\Models\Image;
use App\Models\Property;
use App\Models\PropertyDocument;
use App\Models\User;
use App\Notifications\PropertySubmittedForReview;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Throwable;

class PropertyController extends Controller
{
    public function all_property(Request $request)
    {
        $properties = Property::query()
            ->approved()
            ->withFavoriteState($request->user()->id)
            ->with(['user:id,name', 'images', 'detailed_locations'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    public function add_property(Request $request)
    {
        $validated = $request->validate($this->propertyRules(true));
        $storedPaths = [];

        try {
            $property = DB::transaction(function () use ($request, $validated, &$storedPaths) {
                $property = Property::create([
                    'type' => $validated['type'],
                    'location' => $validated['location'],
                    'price' => $validated['price'],
                    'badroom' => $validated['badroom'] ?? 0,
                    'bathroom' => $validated['bathroom'] ?? 0,
                    'area' => $validated['area'],
                    'status' => $validated['status'],
                    'user_id' => $request->user()->id,
                    'approval_status' => 'pending',
                ]);

                foreach ($request->file('documents', []) as $file) {
                    $path = $file->store("property-documents/{$property->id}", 'local');
                    $storedPaths[] = $path;

                    $property->documents()->create([
                        'original_name' => $file->getClientOriginalName(),
                        'file_path' => $path,
                        'mime_type' => $file->getMimeType(),
                        'file_size' => $file->getSize(),
                    ]);
                }

                if (isset($validated['latitude'], $validated['longitude'])) {
                    $property->detailed_locations()->create([
                        'latitude' => $validated['latitude'],
                        'longitude' => $validated['longitude'],
                    ]);
                }

                return $property;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                Storage::disk('local')->delete($path);
            }

            throw $exception;
        }

        $this->notifyAdmins($property);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال العقار والوثائق إلى الأدمن للمراجعة. لن يظهر العقار قبل الموافقة.',
            'data' => $property->load(['documents', 'detailed_locations']),
        ], 201);
    }

    public function show_Property(Request $request, string $id)
    {
        $property = Property::with([
                'user:id,name',
                'images',
                'detailed_locations',
                'ratings.user:id,name',
            ])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->withFavoriteState((int) $request->user()->id)
            ->findOrFail($id);

        $user = $request->user();
        if ($property->approval_status !== 'approved' && ! $this->canManage($property, $user)) {
            abort(404);
        }

        if ($this->canManage($property, $user)) {
            $property->load('documents');
        }

        return response()->json([
            'success' => true,
            'data' => $property,
        ]);
    }

    public function edit_property(Request $request, string $id)
    {
        $validated = $request->validate($this->propertyRules(false));
        $property = Property::findOrFail($id);
        $this->ensureCanManage($property, $request->user());

        $data = [
            'type' => $validated['type'],
            'location' => $validated['location'],
            'price' => $validated['price'],
            'badroom' => $validated['badroom'] ?? 0,
            'bathroom' => $validated['bathroom'] ?? 0,
            'area' => $validated['area'],
            'status' => $validated['status'],
        ];

        if (! $request->user()->isAdmin()) {
            $data = array_merge($data, [
                'approval_status' => 'pending',
                'rejection_reason' => null,
                'reviewed_by' => null,
                'reviewed_at' => null,
            ]);
        }

        $property->update($data);

        foreach ($request->file('documents', []) as $file) {
            $path = $file->store("property-documents/{$property->id}", 'local');
            $property->documents()->create([
                'original_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        if (! $request->user()->isAdmin()) {
            $this->notifyAdmins($property);
        }

        return response()->json([
            'success' => true,
            'message' => $request->user()->isAdmin()
                ? 'تم تعديل العقار بنجاح.'
                : 'تم تعديل العقار وإرساله مجددًا إلى الأدمن للموافقة.',
            'data' => $property->fresh(),
        ]);
    }

    public function delete_Property(Request $request, string $id)
    {
        $property = Property::findOrFail($id);
        $this->ensureCanManage($property, $request->user());
        $property->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العقار بنجاح.',
        ]);
    }

    public function edit_property_status(Request $request, string $id)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(['متاح', 'مؤجر', 'مباع'])],
        ]);

        $property = Property::findOrFail($id);
        $this->ensureCanManage($property, $request->user());
        $property->update(['status' => $validated['status']]);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث حالة العقار بنجاح.',
            'data' => $property->fresh(),
        ]);
    }

    public function add_property_images(Request $request, $id)
    {
        $property = Property::findOrFail($id);
        $this->ensureCanManage($property, $request->user());

        $request->validate([
            'images' => 'required|array|min:1|max:12',
            'images.*' => 'required|image|mimes:jpg,jpeg,png,webp|max:4096',
        ]);

        foreach ($request->file('images', []) as $image) {
            $path = $image->store('properties', 'public');
            $property->images()->create(['image_path' => $path]);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم رفع الصور بنجاح.',
            'data' => $property->images()->get(),
        ]);
    }

    public function delete_property_image(Request $request, $id)
    {
        $image = Image::with('Property')->findOrFail($id);
        $this->ensureCanManage($image->Property, $request->user());

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الصورة بنجاح.',
        ]);
    }

    public function myProperties(Request $request)
    {
        $properties = Property::query()
            ->where('user_id', $request->user()->id)
            ->withFavoriteState($request->user()->id)
            ->with(['images', 'documents', 'detailed_locations'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    public function show_My_Property(Request $request, $id)
    {
        if ((int) $id !== (int) $request->user()->id && ! $request->user()->isAdmin()) {
            abort(403, 'لا يمكنك مشاهدة عقارات مستخدم آخر.');
        }

        $properties = Property::query()
            ->where('user_id', $id)
            ->withFavoriteState($request->user()->id)
            ->with(['images', 'documents', 'detailed_locations'])
            ->withAvg('ratings', 'rating')
            ->withCount('ratings')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'data' => $properties,
        ]);
    }

    public function downloadDocument(Request $request, Property $property, PropertyDocument $document)
    {
        abort_unless((int) $document->property_id === (int) $property->id, 404);
        $this->ensureCanManage($property, $request->user());
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->download($document->file_path, $document->original_name);
    }

    private function propertyRules(bool $documentsRequired): array
    {
        return [
            'type' => ['required', 'string', Rule::in(['بيت', 'محل', 'أرض', 'شقة', 'فيلا'])],
            'location' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'badroom' => 'nullable|integer|min:0',
            'bathroom' => 'nullable|integer|min:0',
            'area' => 'required|numeric|min:0',
            'status' => ['required', 'string', Rule::in(['متاح', 'مؤجر', 'مباع'])],
            'documents' => $documentsRequired ? 'required|array|min:1|max:10' : 'sometimes|array|max:10',
            'documents.*' => 'file|mimes:pdf,jpg,jpeg,png,webp|max:10240',
            'latitude' => 'nullable|numeric|between:-90,90|required_with:longitude',
            'longitude' => 'nullable|numeric|between:-180,180|required_with:latitude',
        ];
    }

    private function canManage(Property $property, ?User $user): bool
    {
        return $user && ($user->isAdmin() || (int) $property->user_id === (int) $user->id);
    }

    private function ensureCanManage(Property $property, ?User $user): void
    {
        abort_unless($this->canManage($property, $user), 403, 'لا تملك صلاحية إدارة هذا العقار.');
    }

    private function notifyAdmins(Property $property): void
    {
        $property->loadMissing('user');
        User::where('role', 'admin')->get()->each(
            fn (User $admin) => $admin->notify(new PropertySubmittedForReview($property))
        );
    }
}
