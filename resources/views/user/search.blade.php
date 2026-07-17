@extends('user.layout')
@section('title', 'البحث عن عقار')
@section('content')
<main class="container">
    <div class="page-head"><div><h1>البحث المتقدم</h1><p class="subtitle">ابحث حسب النوع والموقع والسعر والمساحة.</p></div></div>
    <div id="alert" class="alert"></div>
    <form id="searchForm" class="card">
        <div class="grid grid-3">
           <div class="form-group">
    <label for="type">نوع العقار</label>

    <select id="type" name="type">
        <option value="">كل أنواع العقارات</option>
      <option>بيت</option><option>محل</option><option>أرض</option><option>شقة</option><option>فيلا</option>
    </select>
</div>
            <div class="form-group"><label>الموقع</label><input name="location"></div>
            <div class="form-group"><label>أقل سعر</label><input name="price_min" type="number" min="0"></div>
            <div class="form-group"><label>أعلى سعر</label><input name="price_max" type="number" min="0"></div>
            <div class="form-group"><label>أقل مساحة</label><input name="area_min" type="number" min="0"></div>
            <div class="form-group"><label>أعلى مساحة</label><input name="area_max" type="number" min="0"></div>
        </div>
        <button class="btn btn-primary" type="submit">بحث</button>
    </form>
    <div id="results" class="grid grid-3" style="margin-top:18px;"></div>
</main>
@endsection
@push('scripts')
<script>
    requireAuth();
    const searchFormElement = document.getElementById('searchForm');
    const searchResultsElement = document.getElementById('results');

    searchFormElement.addEventListener('submit', async (e) => {
        e.preventDefault();
        try {
            const params=new URLSearchParams(); new FormData(searchFormElement).forEach((v,k)=>{if(v!=='')params.append(k,v)});
            const data=await api('/search?'+params.toString());
            searchResultsElement.innerHTML=data.results?.length?data.results.map(propertyCard).join(''):'<div class="card empty">لا توجد نتائج مطابقة.</div>';
        } catch(err){showAlert('alert',err.message,'error');}
    });
</script>
@endpush
