@extends('layouts.admin')

@section('title', 'مراجعة العقار #' . $property->id)
@section('page-title', 'مراجعة العقار #' . $property->id)

@section('content')
<div style="margin-bottom:16px"><a class="btn btn-light" href="{{ route('admin.dashboard') }}">العودة إلى جميع العقارات</a></div>

<div class="grid details-grid">
    <div class="grid">
        <section class="card"><div class="card-body">
            <h2 class="section-title">بيانات العقار</h2>
            <div class="grid info-grid">
                <div class="info-item"><strong>النوع</strong>{{ $property->type }}</div>
                <div class="info-item"><strong>الموقع</strong>{{ $property->location }}</div>
                <div class="info-item"><strong>السعر</strong>{{ number_format($property->price) }}</div>
                <div class="info-item"><strong>المساحة</strong>{{ $property->area }}</div>
                <div class="info-item"><strong>غرف النوم</strong>{{ $property->badroom }}</div>
                <div class="info-item"><strong>الحمامات</strong>{{ $property->bathroom }}</div>
                <div class="info-item"><strong>حالة العقار</strong>{{ $property->status }}</div>
                <div class="info-item"><strong>التقييم</strong>{{ $property->ratings_count ? number_format($property->ratings_avg_rating, 1) . ' / 5 (' . $property->ratings_count . ')' : 'لا يوجد' }}</div>
            </div>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="section-title">أوراق الإثبات الخاصة</h2>
            <p class="muted">هذه الملفات لا تظهر إلا للأدمن وصاحب العقار.</p>
            @forelse($property->documents as $document)
                <div class="document">
                    <div><strong>{{ $document->original_name }}</strong><div class="muted">{{ $document->mime_type ?: 'ملف' }} · {{ $document->file_size ? number_format($document->file_size / 1024, 1) . ' KB' : '' }}</div></div>
                    <a class="btn btn-primary" href="{{ route('admin.documents.download', $document) }}">تنزيل آمن</a>
                </div>
            @empty
                <div class="alert alert-error">لم يرفع صاحب العقار أي وثيقة.</div>
            @endforelse
        </div></section>

        @if($property->images->isNotEmpty())
            <section class="card"><div class="card-body">
                <h2 class="section-title">صور العقار</h2>
                <div class="images">
                    @foreach($property->images as $image)
                        <img src="{{ asset('storage/' . $image->image_path) }}" alt="صورة العقار">
                    @endforeach
                </div>
            </div></section>
        @endif

        <section class="card"><div class="card-body">
            <h2 class="section-title">تقييمات المستخدمين</h2>
            @forelse($property->ratings as $rating)
                <div class="review">
                    <strong>{{ $rating->user->name }}</strong>
                    <div class="stars">{{ str_repeat('★', $rating->rating) }}{{ str_repeat('☆', 5 - $rating->rating) }}</div>
                    @if($rating->comment)<div>{{ $rating->comment }}</div>@endif
                </div>
            @empty
                <div class="muted">لا توجد تقييمات حتى الآن.</div>
            @endforelse
        </div></section>
    </div>

    <aside class="grid">
        @if($property->detailed_locations)
            <section class="card"><div class="card-body">
                <h2 class="section-title">الموقع الدقيق على الخريطة</h2>
                <div id="adminPropertyMap" class="map-box"></div>
                <div class="muted" style="margin-top:10px">
                    {{ $property->detailed_locations->latitude }}, {{ $property->detailed_locations->longitude }}
                </div>
            </div></section>
        @endif

        <section class="card"><div class="card-body">
            <h2 class="section-title">صاحب العقار</h2>
            <div><strong>{{ $property->user->name }}</strong></div>
            <div class="muted">{{ $property->user->email }}</div>
            <div class="muted" style="margin-top:8px">مسجل منذ {{ $property->user->created_at->format('Y-m-d') }}</div>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="section-title">قرار الأدمن</h2>
            <div style="margin-bottom:15px">
                @if($property->approval_status === 'approved')
                    <span class="badge badge-approved">العقار مقبول</span>
                @elseif($property->approval_status === 'rejected')
                    <span class="badge badge-rejected">العقار مرفوض</span>
                @else
                    <span class="badge badge-pending">بانتظار القرار</span>
                @endif
            </div>

            @if($property->rejection_reason)
                <div class="alert alert-error"><strong>سبب الرفض:</strong><br>{{ $property->rejection_reason }}</div>
            @endif

            @if($property->reviewer)
                <div class="muted" style="margin-bottom:14px">آخر مراجعة بواسطة {{ $property->reviewer->name }} بتاريخ {{ optional($property->reviewed_at)->format('Y-m-d H:i') }}</div>
            @endif

            <form method="POST" action="{{ route('admin.properties.approve', $property) }}" style="margin-bottom:16px">
                @csrf @method('PATCH')
                <button class="btn btn-success" style="width:100%" type="submit">الموافقة وإشعار المالك</button>
            </form>

            <form method="POST" action="{{ route('admin.properties.reject', $property) }}">
                @csrf @method('PATCH')
                <label for="rejection_reason">سبب الرفض</label>
                <textarea id="rejection_reason" name="rejection_reason" required placeholder="اكتب سببًا واضحًا ليصل إلى صاحب العقار...">{{ old('rejection_reason', $property->rejection_reason) }}</textarea>
                <button class="btn btn-danger" style="width:100%; margin-top:10px" type="submit">رفض العقار وإرسال السبب</button>
            </form>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="section-title">حذف العقار</h2>
            <p class="muted">الحذف نهائي ويشمل الصور والوثائق والتقييمات.</p>
            <form method="POST" action="{{ route('admin.properties.destroy', $property) }}" onsubmit="return confirm('هل أنت متأكد من حذف العقار نهائيًا؟')">
                @csrf @method('DELETE')
                <button class="btn btn-danger" style="width:100%" type="submit">حذف العقار نهائيًا</button>
            </form>
        </div></section>
    </aside>
</div>
@endsection


@if($property->detailed_locations)
@push('scripts')
<script>
    const adminPropertyMap = L.map('adminPropertyMap').setView([{{ $property->detailed_locations->latitude }}, {{ $property->detailed_locations->longitude }}], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(adminPropertyMap);
    L.marker([{{ $property->detailed_locations->latitude }}, {{ $property->detailed_locations->longitude }}]).addTo(adminPropertyMap);
</script>
@endpush
@endif
