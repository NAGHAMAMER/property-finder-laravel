@extends('layouts.admin')

@section('title', 'إدارة العقارات')
@section('page-title', 'إدارة العقارات')

@section('content')
<div class="grid stats">
    <div class="card stat"><div class="stat-label">كل العقارات</div><div class="stat-number">{{ $stats['all'] }}</div></div>
    <div class="card stat"><div class="stat-label">بانتظار المراجعة</div><div class="stat-number">{{ $stats['pending'] }}</div></div>
    <div class="card stat"><div class="stat-label">تمت الموافقة</div><div class="stat-number">{{ $stats['approved'] }}</div></div>
    <div class="card stat"><div class="stat-label">مرفوضة</div><div class="stat-number">{{ $stats['rejected'] }}</div></div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" class="filters">
            <div style="flex:1; min-width:230px">
                <label>بحث بالمالك أو الموقع أو النوع</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="اكتب كلمة البحث...">
            </div>
            <div style="min-width:190px">
                <label>حالة الموافقة</label>
                <select name="approval_status">
                    <option value="">كل الحالات</option>
                    <option value="pending" @selected(request('approval_status') === 'pending')>بانتظار المراجعة</option>
                    <option value="approved" @selected(request('approval_status') === 'approved')>مقبول</option>
                    <option value="rejected" @selected(request('approval_status') === 'rejected')>مرفوض</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">تطبيق</button>
            <a class="btn btn-light" href="{{ route('admin.dashboard') }}">إلغاء التصفية</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>العقار</th>
                <th>المالك</th>
                <th>السعر</th>
                <th>الوثائق</th>
                <th>التقييم</th>
                <th>الموافقة</th>
                <th>تاريخ الإرسال</th>
                <th>إجراءات</th>
            </tr>
            </thead>
            <tbody>
            @forelse($properties as $property)
                <tr>
                    <td>{{ $property->id }}</td>
                    <td><strong>{{ $property->type }}</strong><div class="muted">{{ $property->location }}</div></td>
                    <td>{{ $property->user->name }}<div class="muted">{{ $property->user->email }}</div></td>
                    <td>{{ number_format($property->price) }}</td>
                    <td>{{ $property->documents_count }}</td>
                    <td>{{ $property->ratings_count ? number_format($property->ratings_avg_rating, 1) . ' / 5' : 'لا يوجد' }}</td>
                    <td>
                        @if($property->approval_status === 'approved')
                            <span class="badge badge-approved">مقبول</span>
                        @elseif($property->approval_status === 'rejected')
                            <span class="badge badge-rejected">مرفوض</span>
                        @else
                            <span class="badge badge-pending">قيد المراجعة</span>
                        @endif
                    </td>
                    <td>{{ $property->created_at->format('Y-m-d H:i') }}</td>
                    <td><a class="btn btn-primary" href="{{ route('admin.properties.show', $property) }}">عرض ومراجعة</a></td>
                </tr>
            @empty
                <tr><td colspan="9" style="text-align:center; padding:35px">لا توجد عقارات مطابقة.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="pagination">{{ $properties->links() }}</div>
@endsection
