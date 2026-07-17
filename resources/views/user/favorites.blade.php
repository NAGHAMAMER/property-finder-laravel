@extends('user.layout')
@section('title', 'المفضلة')
@section('content')
<main class="container">
    <div class="page-head"><div><h1>العقارات المفضلة</h1><p class="subtitle">العقارات التي حفظتها للرجوع إليها لاحقًا.</p></div></div>
    <div id="alert" class="alert"></div>
    <div id="properties" class="grid grid-3"><div class="loading">جارٍ التحميل...</div></div>
</main>
@endsection
@push('scripts')
<script>
    const favoritePropertiesElement = document.getElementById('properties');

    async function loadFavorites() {
        if (!requireAuth()) return;
        try { const data=await api('/favorites'); favoritePropertiesElement.innerHTML=data.data?.length?data.data.map(propertyCard).join(''):'<div class="card empty">لا توجد عقارات في المفضلة.</div>'; }
        catch(e){showAlert('alert',e.message,'error');}
    }
    loadFavorites(); document.addEventListener('favorite:changed', loadFavorites);
</script>
@endpush
