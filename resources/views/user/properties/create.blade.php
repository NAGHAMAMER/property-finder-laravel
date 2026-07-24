@extends('user.layout')
@section('title', 'إضافة عقار')
@section('content')
<main class="container">
    <div class="page-head">
        <div>
            <h1>إضافة عقار جديد</h1>
            <p class="subtitle">أضف صور العقار التي ستظهر للمستخدمين بعد الموافقة، وأرفق وثائق الإثبات الخاصة التي يراجعها الأدمن فقط.</p>
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
                <label for="images">صور العقار المعروضة (إلزامي)</label>
                <input name="images[]" id="images" type="file" multiple required accept="image/jpeg,image/png,image/webp">
                <small class="muted">يمكنك اختيار عدة صور دفعة واحدة، أو اختيار صورة ثم فتح الاختيار مجددًا لإضافة صور أخرى. الحد الأقصى 12 صورة و4MB لكل صورة.</small>
            </div>
            <div class="form-group">
                <label for="documents">وثائق الإثبات الخاصة (إلزامي)</label>
                <input name="documents[]" id="documents" type="file" multiple required accept=".pdf,.jpg,.jpeg,.png,.webp">
                <small class="muted">حتى 10 ملفات، 10MB لكل ملف. هذه الملفات خاصة ولا يراها إلا صاحب العقار والأدمن.</small>
            </div>
        </div>

        <div id="imagesPreviewSection" style="display:none; margin-top:18px;">
            <div class="page-head" style="margin-bottom:10px;">
                <h3 class="section-title" style="font-size:16px; margin:0;">معاينة صور العقار</h3>
                <span id="imagesCount" class="badge badge-neutral">0 / 12</span>
            </div>
            <div id="imagesPreview" style="display:grid; grid-template-columns:repeat(auto-fill,minmax(145px,1fr)); gap:12px;"></div>
        </div>

        <div class="hr"></div>
        <section>
            <div class="page-head" style="margin-bottom:12px;">
                <div>
                    <h2 class="section-title" style="margin:0;">حدد الموقع المفصل على الخريطة <span class="muted" style="font-size:14px;">(اختياري)</span></h2>
                    <p class="map-help">يمكنك ترك الخريطة دون تحديد. عند اختيار نقطة، تُحفظ الإحداثيات مع العقار وتُستخدم في البحث القريب.</p>
                </div>
                <button id="useCurrentLocation" class="btn btn-light btn-sm" type="button">📍 استخدام موقعي الحالي</button>
            </div>
            <div id="propertyLocationMap" class="map-box"></div>
            <div class="grid grid-2" style="margin-top:12px;">
                <div class="form-group"><label for="latitude">خط العرض (اختياري)</label><input id="latitude" type="number" step="any" min="-90" max="90" readonly placeholder="يُحدد من الخريطة"></div>
                <div class="form-group"><label for="longitude">خط الطول (اختياري)</label><input id="longitude" type="number" step="any" min="-180" max="180" readonly placeholder="يُحدد من الخريطة"></div>
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
    const imagesInput = document.getElementById('images');
    const imagesPreview = document.getElementById('imagesPreview');
    const imagesPreviewSection = document.getElementById('imagesPreviewSection');
    const imagesCount = document.getElementById('imagesCount');

    const allowedImageTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const maxImageSize = 4 * 1024 * 1024;
    const maxImages = 12;

    let selectedImageFiles = [];
    let imagePreviewUrls = [];

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

    function fileIdentity(file) {
        return `${file.name}-${file.size}-${file.lastModified}`;
    }

    function syncImagesInput() {
        const transfer = new DataTransfer();
        selectedImageFiles.forEach(file => transfer.items.add(file));
        imagesInput.files = transfer.files;
    }

    function revokePreviewUrls() {
        imagePreviewUrls.forEach(url => URL.revokeObjectURL(url));
        imagePreviewUrls = [];
    }

    function removeSelectedImage(index) {
        selectedImageFiles.splice(index, 1);
        syncImagesInput();
        renderImagePreviews();
    }

    function renderImagePreviews() {
        revokePreviewUrls();
        imagesPreview.innerHTML = '';
        imagesCount.textContent = `${selectedImageFiles.length} / ${maxImages}`;
        imagesPreviewSection.style.display = selectedImageFiles.length ? 'block' : 'none';

        selectedImageFiles.forEach((file, index) => {
            const url = URL.createObjectURL(file);
            imagePreviewUrls.push(url);

            const item = document.createElement('div');
            item.style.cssText = 'position:relative;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;background:#fff;';

            const image = document.createElement('img');
            image.src = url;
            image.alt = `صورة العقار ${index + 1}`;
            image.style.cssText = 'width:100%;height:125px;object-fit:cover;display:block;';

            const label = document.createElement('div');
            label.textContent = index === 0 ? 'الصورة الرئيسية' : `صورة ${index + 1}`;
            label.style.cssText = 'padding:8px 38px 8px 8px;text-align:center;font-size:13px;color:#475569;';

            const removeButton = document.createElement('button');
            removeButton.type = 'button';
            removeButton.textContent = '×';
            removeButton.title = 'حذف الصورة';
            removeButton.setAttribute('aria-label', `حذف صورة العقار ${index + 1}`);
            removeButton.style.cssText = 'position:absolute;top:7px;left:7px;width:28px;height:28px;border:0;border-radius:50%;background:rgba(15,23,42,.82);color:#fff;font-size:20px;line-height:26px;cursor:pointer;';
            removeButton.addEventListener('click', () => removeSelectedImage(index));

            item.append(image, label, removeButton);
            imagesPreview.appendChild(item);
        });
    }

    imagesInput.addEventListener('change', () => {
        const newlyChosenFiles = Array.from(imagesInput.files || []);
        const knownFiles = new Set(selectedImageFiles.map(fileIdentity));

        for (const file of newlyChosenFiles) {
            if (!allowedImageTypes.includes(file.type) || file.size > maxImageSize) {
                syncImagesInput();
                showAlert('alert', 'الصور المسموحة JPG أو PNG أو WEBP، وبحد أقصى 4MB للصورة.', 'error');
                return;
            }

            const identity = fileIdentity(file);
            if (!knownFiles.has(identity)) {
                selectedImageFiles.push(file);
                knownFiles.add(identity);
            }
        }

        if (selectedImageFiles.length > maxImages) {
            selectedImageFiles = selectedImageFiles.slice(0, maxImages);
            showAlert('alert', 'يمكنك رفع 12 صورة كحد أقصى. تم الاحتفاظ بأول 12 صورة فقط.', 'error');
        }

        syncImagesInput();
        renderImagePreviews();
    });

    propertyForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const button = event.submitter;
        button.disabled = true;

        try {
            const latitude = latitudeInput.value.trim();
            const longitude = longitudeInput.value.trim();

            if (selectedImageFiles.length < 1) {
                throw new Error('أضف صورة واحدة على الأقل للعقار. يمكنك إضافة حتى 12 صورة.');
            }

            if (selectedImageFiles.length > maxImages) {
                throw new Error('يمكنك رفع 12 صورة كحد أقصى.');
            }

            if ((latitude && !longitude) || (!latitude && longitude)) {
                throw new Error('عند تحديد الموقع المفصل يجب حفظ خط العرض وخط الطول معًا.');
            }

            syncImagesInput();
            const formData = new FormData(propertyForm);

            // الموقع المفصل اختياري بالكامل.
            if (latitude && longitude) {
                formData.set('latitude', latitude);
                formData.set('longitude', longitude);
            }

            const data = await api('/add-property', {
                method: 'POST',
                body: formData,
            });

            showAlert('alert', data.message || 'تمت إضافة العقار بصوره ووثائقه وإرساله للمراجعة.');
            setTimeout(() => location.href = '{{ route('user.properties.my') }}', 900);
        } catch (error) {
            showAlert('alert', error.message, 'error');
        } finally {
            button.disabled = false;
        }
    });

    window.addEventListener('beforeunload', revokePreviewUrls);
</script>
@endpush
