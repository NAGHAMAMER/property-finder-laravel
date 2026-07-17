@extends('user.layout')
@section('title', 'تعديل العقار')
@section('content')
<main class="container">
    <div class="page-head">
        <div>
            <h1>تعديل العقار</h1>
            <p class="subtitle">أي تعديل من المستخدم العادي يعيد العقار إلى المراجعة.</p>
        </div>
    </div>

    <div id="alert" class="alert"></div>

    <form id="propertyForm" class="card" enctype="multipart/form-data">
        <div class="grid grid-2">
            <div class="form-group"><label for="type">نوع العقار</label><select name="type" id="type" required><option>بيت</option><option>محل</option><option>أرض</option><option>شقة</option><option>فيلا</option></select></div>
            <div class="form-group"><label for="locationField">الموقع النصي</label><input name="location" id="locationField" required></div>
            <div class="form-group"><label for="price">السعر</label><input name="price" id="price" type="number" min="0" required></div>
            <div class="form-group"><label for="area">المساحة</label><input name="area" id="area" type="number" min="0" required></div>
            <div class="form-group"><label for="badroom">غرف النوم</label><input name="badroom" id="badroom" type="number" min="0"></div>
            <div class="form-group"><label for="bathroom">الحمامات</label><input name="bathroom" id="bathroom" type="number" min="0"></div>
            <div class="form-group"><label for="status">الحالة</label><select name="status" id="status"><option>متاح</option><option>مؤجر</option><option>مباع</option></select></div>
            <div class="form-group"><label for="documents">إضافة وثائق جديدة (اختياري)</label><input name="documents[]" id="documents" type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.webp"></div>
        </div>

        <div class="hr"></div>
        <section>
            <div class="page-head" style="margin-bottom:12px;">
                <div>
                    <h2 class="section-title" style="margin:0;">الموقع الدقيق على الخريطة</h2>
                    <p class="map-help">انقر على الخريطة لتغيير الموقع أو استخدم موقعك الحالي.</p>
                </div>
                <button id="useCurrentLocation" class="btn btn-light btn-sm" type="button">📍 استخدام موقعي الحالي</button>
            </div>
            <div id="propertyLocationMap" class="map-box"></div>
            <div class="grid grid-2" style="margin-top:12px;">
                <div class="form-group"><label for="latitude">خط العرض</label><input id="latitude" type="number" step="any" min="-90" max="90" readonly></div>
                <div class="form-group"><label for="longitude">خط الطول</label><input id="longitude" type="number" step="any" min="-180" max="180" readonly></div>
            </div>
            <button id="deleteLocationButton" class="btn btn-danger btn-sm hidden" type="button">حذف الموقع الدقيق</button>
        </section>

        <div class="hr"></div>
        <button class="btn btn-primary" type="submit">حفظ التعديلات</button>
    </form>
</main>
@endsection

@push('scripts')
<script>
    const propertyId = {{ (int) $id }};
    const propertyForm = document.getElementById('propertyForm');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const deleteLocationButton = document.getElementById('deleteLocationButton');
    const locationPicker = createLocationMap({
        mapId: 'propertyLocationMap',
        latInputId: 'latitude',
        lngInputId: 'longitude',
    });

    async function loadProperty() {
        if (!requireAuth()) return;

        try {
            const { data: property } = await api(`/show_Property/${propertyId}`);
            document.getElementById('type').value = property.type;
            document.getElementById('locationField').value = property.location;
            document.getElementById('price').value = property.price;
            document.getElementById('area').value = property.area;
            document.getElementById('badroom').value = property.badroom || 0;
            document.getElementById('bathroom').value = property.bathroom || 0;
            document.getElementById('status').value = property.status;

            if (property.detailed_locations) {
                locationPicker.setPoint(
                    property.detailed_locations.latitude,
                    property.detailed_locations.longitude,
                    true
                );
                deleteLocationButton.classList.remove('hidden');
            }
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    document.getElementById('useCurrentLocation').addEventListener('click', async () => {
        try {
            await locationPicker.locateCurrent();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    });

    deleteLocationButton.addEventListener('click', async () => {
        if (!confirm('هل تريد حذف الموقع الدقيق لهذا العقار؟')) return;

        try {
            await api(`/show_My_Property/show_Property/${propertyId}/delet_detailed_locations`, { method: 'DELETE' });
            latitudeInput.value = '';
            longitudeInput.value = '';
            showAlert('alert', 'تم حذف الموقع الدقيق.');
            setTimeout(() => location.reload(), 500);
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    });

    propertyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = event.submitter;
        button.disabled = true;

        try {
            const data = await api(`/show_My_Property/show_Property/edit_property/${propertyId}`, {
                method: 'POST',
                body: new FormData(propertyForm),
            });

            const latitude = latitudeInput.value.trim();
            const longitude = longitudeInput.value.trim();
            if (latitude && longitude) {
                await api(`/show_My_Property/show_Property/${propertyId}/add_detailed_locations`, {
                    method: 'POST',
                    body: { latitude, longitude },
                });
            }

            showAlert('alert', data.message || 'تم حفظ التعديلات.');
        } catch (error) {
            showAlert('alert', error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });

    loadProperty();
</script>
@endpush
