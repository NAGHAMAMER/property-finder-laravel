@extends('user.layout')
@section('title', 'جميع العقارات')
@section('content')
<main class="container">
    <div class="page-head">
        <div><h1>العقارات المعتمدة</h1><p class="subtitle">جميع العقارات التي وافق عليها الأدمن.</p></div>
        <a class="btn btn-primary" href="{{ route('user.properties.create') }}">+ إضافة عقار</a>
    </div>
    <div id="alert" class="alert"></div>
    <div id="properties" class="grid grid-3"><div class="loading">جارٍ التحميل...</div></div>
</main>
@endsection
@push('scripts')
<script>
    const propertiesElement = document.getElementById('properties');

    async function loadProperties() {
        if (!requireAuth()) return;
        try {
            const data = await api('/all_property');
            propertiesElement.innerHTML = data.data?.length ? data.data.map(propertyCard).join('') : '<div class="card empty">لا توجد عقارات معتمدة حاليًا.</div>';
        } catch (e) { showAlert('alert', e.message, 'error'); }
    }
    loadProperties();
    document.addEventListener('favorite:changed', loadProperties);
</script>
@endpush
