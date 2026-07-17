@extends('user.layout')
@section('title', 'إضافة عقار')
@section('content')
<main class="container">
    <div class="page-head">
        <div>
            <h1>إضافة عقار جديد</h1>
            <p class="subtitle">سيتم إرسال العقار والوثائق إلى الأدمن للمراجعة قبل النشر.</p>
        </div>
    </div>

    <div id="alert" class="alert"></div>

    <form id="propertyForm" class="card" enctype="multipart/form-data">
        <div class="grid grid-2">
            <div class="form-group">
                <label for="type">نوع العقار</label>
                <select name="type" id="type" required>
                    <option value="">اختر</option>
                    <option>بيت</option><option>محل</option><option>أرض</option><option>شقة</option><option>فيلا</option>
                </select>
            </div>
            <div class="form-group"><label for="location">الموقع النصي</label><input name="location" id="location" required maxlength="255" placeholder="مثال: دمشق - المزة"></div>
            <div class="form-group"><label for="price">السعر</label><input name="price" id="price" type="number" min="0" step="1" required></div>
            <div class="form-group"><label for="area">المساحة (م²)</label><input name="area" id="area" type="number" min="0" step="1" required></div>
            <div class="form-group"><label for="badroom">عدد غرف النوم</label><input name="badroom" id="badroom" type="number" min="0" value="0"></div>
            <div class="form-group"><label for="bathroom">عدد الحمامات</label><input name="bathroom" id="bathroom" type="number" min="0" value="0"></div>
            <div class="form-group">
                <label for="status">حالة العقار</label>
                <select name="status" id="status" required><option>متاح</option><option>مؤجر</option><option>مباع</option></select>
            </div>
            <div class="form-group">
                <label for="documents">وثائق الإثبات (إلزامي)</label>
                <input name="documents[]" id="documents" type="file" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="muted">حتى 10 ملفات، 10MB لكل ملف.</small>
            </div>
        </div>

        <div class="hr"></div>
        <section>
            <div class="page-head" style="margin-bottom:12px;">
                <div>
                    <h2 class="section-title" style="margin:0;">حدد موقع العقار على الخريطة</h2>
                    <p class="map-help">انقر على الخريطة لاختيار الموقع، أو استخدم موقعك الحالي. ستُحفظ الإحداثيات تلقائيًا مع العقار.</p>
                </div>
                <button id="useCurrentLocation" class="btn btn-light btn-sm" type="button">📍 استخدام موقعي الحالي</button>
            </div>
            <div id="propertyLocationMap" class="map-box"></div>
            <div class="grid grid-2" style="margin-top:12px;">
                <div class="form-group"><label for="latitude">خط العرض</label><input id="latitude" type="number" step="any" min="-90" max="90" readonly placeholder="يُحدد من الخريطة"></div>
                <div class="form-group"><label for="longitude">خط الطول</label><input id="longitude" type="number" step="any" min="-180" max="180" readonly placeholder="يُحدد من الخريطة"></div>
            </div>
        </section>

        <button class="btn btn-primary" type="submit">إرسال العقار للمراجعة</button>
    </form>
</main>
@endsection

@push('scripts')
<script>
    requireAuth();

    const propertyForm = document.getElementById('propertyForm');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const locationPicker = createLocationMap({
        mapId: 'propertyLocationMap',
        latInputId: 'latitude',
        lngInputId: 'longitude',
    });

    document.getElementById('useCurrentLocation').addEventListener('click', async () => {
        try {
            await locationPicker.locateCurrent();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    });

    propertyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = event.submitter;
        button.disabled = true;

        try {
            const latitude = latitudeInput.value.trim();
            const longitude = longitudeInput.value.trim();
            if (!latitude || !longitude) {
                throw new Error('حدد موقع العقار على الخريطة قبل الإرسال.');
            }

            const formData = new FormData(propertyForm);
            formData.set('latitude', latitude);
            formData.set('longitude', longitude);

            const data = await api('/add-property', {
                method: 'POST',
                body: formData,
            });

            showAlert('alert', data.message || 'تمت إضافة العقار وإرساله للمراجعة.');
            setTimeout(() => location.href = '{{ route('user.properties.my') }}', 900);
        } catch (error) {
            showAlert('alert', error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
</script>
@endpush
