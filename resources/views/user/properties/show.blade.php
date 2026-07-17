@extends('user.layout')
@section('title', 'تفاصيل العقار')
@section('content')
<main class="container">
    <div id="alert" class="alert"></div>
    <div id="content" class="loading">جارٍ تحميل تفاصيل العقار...</div>
</main>
@endsection

@push('scripts')
<script>
    const propertyId = {{ (int) $id }};
    const contentElement = document.getElementById('content');
    let currentProperty = null;
    let currentUser = null;
    let propertyMapPicker = null;

    async function loadPage() {
        if (!requireAuth()) return;

        try {
            const [userResponse, propertyResponse] = await Promise.all([
                api('/user'),
                api(`/show_Property/${propertyId}`),
            ]);
            currentUser = userResponse;
            currentProperty = propertyResponse.data;
            await renderPage();
        } catch (error) {
            contentElement.innerHTML = '';
            showAlert('alert', error.message, 'error');
        }
    }

    async function renderPage() {
        if (propertyMapPicker?.map) {
            try { propertyMapPicker.map.remove(); } catch (_) {}
            propertyMapPicker = null;
        }

        const property = currentProperty;
        const isOwner = Number(property.user_id) === Number(currentUser.id);
        const images = property.images || [];
        const documents = property.documents || [];
        const preciseLocation = property.detailed_locations || null;

        let ratingsData = {
            data: [],
            average_rating: property.ratings_avg_rating || 0,
            ratings_count: property.ratings_count || 0,
        };

        if (property.approval_status === 'approved') {
            try {
                ratingsData = await api(`/properties/${propertyId}/ratings`);
            } catch (_) {}
        }

        contentElement.className = '';
        contentElement.innerHTML = `
            <div class="page-head">
                <div>
                    <h1>${esc(property.type)} في ${esc(property.location)}</h1>
                    <div class="actions" style="margin-top:8px;">
                        ${approvalBadge(property.approval_status)}
                        <span class="badge badge-neutral">${esc(property.status)}</span>
                    </div>
                </div>
                <div class="actions">
                    ${property.approval_status === 'approved' ? `<button class="btn btn-light" onclick="toggleFavorite(${property.id}, this, ${Boolean(property.is_favorite)})">${property.is_favorite ? '♥ إزالة من المفضلة' : '♡ إضافة للمفضلة'}</button>` : ''}
                    ${isOwner ? `<a class="btn btn-primary" href="/app/properties/${property.id}/edit">تعديل العقار</a>` : ''}
                </div>
            </div>

            <div class="split">
                <div class="grid" style="gap:18px;">
                    <section class="card">
                        <div class="gallery">
                            ${images.length
                                ? images.map(image => `<div class="gallery-item"><img src="/storage/${esc(image.image_path)}" alt="صورة العقار">${isOwner ? `<button class="btn btn-danger btn-sm delete-image" onclick="deleteImage(${image.id})">حذف</button>` : ''}</div>`).join('')
                                : '<div class="empty" style="grid-column:1/-1">لا توجد صور مرفوعة.</div>'}
                        </div>
                    </section>

                    <section class="card">
                        <h2 class="section-title">تفاصيل العقار</h2>
                        <div class="grid grid-3">
                            <div><span class="muted">السعر</span><div class="price">${money(property.price)} $</div></div>
                            <div><span class="muted">المساحة</span><p><strong>${esc(property.area)} م²</strong></p></div>
                            <div><span class="muted">المالك</span><p><strong>${esc(property.user?.name || '—')}</strong></p></div>
                            <div><span class="muted">غرف النوم</span><p><strong>${esc(property.badroom || 0)}</strong></p></div>
                            <div><span class="muted">الحمامات</span><p><strong>${esc(property.bathroom || 0)}</strong></p></div>
                            <div><span class="muted">الموقع النصي</span><p><strong>${esc(property.location)}</strong></p></div>
                        </div>
                        ${property.rejection_reason ? `<div class="alert alert-error show">سبب الرفض: ${esc(property.rejection_reason)}</div>` : ''}
                    </section>

                    ${isOwner ? ownerTools(property, documents) : ''}
                    ${property.approval_status === 'approved' ? ratingsSection(ratingsData, isOwner) : ''}
                </div>

                <aside class="grid" style="gap:18px;">
                    ${!isOwner && property.approval_status === 'approved' ? messageSection() : ''}
                    <section class="card">
                        <h3 class="section-title">الموقع الجغرافي على الخريطة</h3>
                        ${preciseLocation || isOwner ? '<div id="propertyMap" class="map-box"></div>' : '<p class="muted">لم يتم تحديد موقع دقيق لهذا العقار.</p>'}
                        ${preciseLocation ? `<p class="map-help">الإحداثيات: ${esc(preciseLocation.latitude)}, ${esc(preciseLocation.longitude)}</p>` : ''}
                        ${isOwner ? '<p class="map-help">يمكنك النقر على الخريطة لتحديد موقع جديد ثم الضغط على «حفظ الموقع» ضمن أدوات الإدارة.</p>' : ''}
                    </section>
                </aside>
            </div>`;

        initializePropertyMap(isOwner);
    }

    function initializePropertyMap(isOwner) {
        const location = currentProperty?.detailed_locations;
        const mapElement = document.getElementById('propertyMap');
        if (!mapElement) return;

        propertyMapPicker = createLocationMap({
            mapId: 'propertyMap',
            latInputId: isOwner ? 'locationLatitude' : null,
            lngInputId: isOwner ? 'locationLongitude' : null,
            latitude: location?.latitude,
            longitude: location?.longitude,
            readOnly: !isOwner,
        });
    }

    function ownerTools(property, documents) {
        return `<section class="card">
            <h2 class="section-title">إدارة العقار</h2>
            <div class="grid grid-2">
                <form id="statusForm">
                    <div class="form-group">
                        <label for="newStatus">تغيير الحالة</label>
                        <select id="newStatus">
                            <option ${property.status === 'متاح' ? 'selected' : ''}>متاح</option>
                            <option ${property.status === 'مؤجر' ? 'selected' : ''}>مؤجر</option>
                            <option ${property.status === 'مباع' ? 'selected' : ''}>مباع</option>
                        </select>
                    </div>
                    <button class="btn btn-primary btn-sm" type="submit">حفظ الحالة</button>
                </form>

                <form id="imagesForm">
                    <div class="form-group"><label for="newImages">رفع صور</label><input id="newImages" type="file" multiple accept="image/*" required></div>
                    <button class="btn btn-primary btn-sm" type="submit">رفع الصور</button>
                </form>

                <form id="locationForm">
                    <h3 class="section-title" style="font-size:16px;">الموقع الدقيق</h3>
                    <div class="form-group"><label for="locationLatitude">خط العرض</label><input id="locationLatitude" type="number" step="any" min="-90" max="90" value="${esc(property.detailed_locations?.latitude || '')}" readonly required></div>
                    <div class="form-group"><label for="locationLongitude">خط الطول</label><input id="locationLongitude" type="number" step="any" min="-180" max="180" value="${esc(property.detailed_locations?.longitude || '')}" readonly required></div>
                    <div class="actions">
                        <button class="btn btn-light btn-sm" type="button" onclick="useCurrentPropertyLocation()">📍 موقعي الحالي</button>
                        <button class="btn btn-primary btn-sm" type="submit">حفظ الموقع</button>
                        ${property.detailed_locations ? '<button class="btn btn-danger btn-sm" type="button" onclick="deleteLocation()">حذف الموقع</button>' : ''}
                    </div>
                </form>

                <div>
                    <label>وثائق الإثبات</label>
                    ${documents.length
                        ? documents.map(document => `<p><button class="btn btn-light btn-sm" onclick="downloadDocument(${document.id}, decodeURIComponent('${encodeURIComponent(document.original_name)}'))">⬇ ${esc(document.original_name)}</button></p>`).join('')
                        : '<p class="muted">لا توجد وثائق.</p>'}
                </div>
            </div>
            <div class="hr"></div>
            <button class="btn btn-danger" onclick="deleteProperty()">حذف العقار نهائيًا</button>
        </section>`;
    }

    function ratingsSection(ratings, isOwner) {
        return `<section class="card">
            <h2 class="section-title">التقييمات</h2>
            <p><span class="stars" style="font-size:22px">${stars(ratings.average_rating)}</span> <strong>${esc(ratings.average_rating || 0)}</strong> من 5 — ${esc(ratings.ratings_count || 0)} تقييم</p>
            ${!isOwner ? `<form id="ratingForm" class="card" style="box-shadow:none;background:#f8fafc;margin:14px 0;">
                <div class="grid grid-2">
                    <div class="form-group"><label for="ratingValue">تقييمك</label><select id="ratingValue"><option value="5">5 - ممتاز</option><option value="4">4 - جيد جدًا</option><option value="3">3 - جيد</option><option value="2">2 - مقبول</option><option value="1">1 - ضعيف</option></select></div>
                    <div class="form-group"><label for="ratingComment">تعليق</label><textarea id="ratingComment" maxlength="1000"></textarea></div>
                </div>
                <div class="actions"><button class="btn btn-primary btn-sm" type="submit">حفظ/تحديث التقييم</button><button class="btn btn-danger btn-sm" type="button" onclick="deleteRating()">حذف تقييمي</button></div>
            </form>` : ''}
            <div>${ratings.data?.length
                ? ratings.data.map(item => `<div style="padding:12px 0;border-bottom:1px solid #e2e8f0"><div class="actions"><strong>${esc(item.user?.name || 'مستخدم')}</strong><span class="stars">${stars(item.rating)}</span></div><p>${esc(item.comment || 'بدون تعليق')}</p></div>`).join('')
                : '<p class="muted">لا توجد تقييمات بعد.</p>'}</div>
        </section>`;
    }

    function messageSection() {
        return `<section class="card">
            <h3 class="section-title">تواصل مع المالك</h3>
            <form id="messageForm">
                <div class="form-group"><textarea id="messageContent" maxlength="5000" required placeholder="اكتب رسالتك..."></textarea></div>
                <button class="btn btn-primary" type="submit">إرسال الرسالة</button>
            </form>
        </section>`;
    }

    async function useCurrentPropertyLocation() {
        try {
            if (!propertyMapPicker) throw new Error('الخريطة غير جاهزة.');
            await propertyMapPicker.locateCurrent();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    document.addEventListener('submit', async (event) => {
        try {
            if (event.target.id === 'statusForm') {
                event.preventDefault();
                const status = document.getElementById('newStatus').value;
                await api(`/show_My_Property/show_Property/edit_property_status/${propertyId}`, { method: 'POST', body: { status } });
                showAlert('alert', 'تم تحديث الحالة.');
                await loadPage();
            }

            if (event.target.id === 'imagesForm') {
                event.preventDefault();
                const files = document.getElementById('newImages').files;
                const formData = new FormData();
                [...files].forEach(file => formData.append('images[]', file));
                await api(`/show_My_Property/show_Property/${propertyId}/add_property_images`, { method: 'POST', body: formData });
                showAlert('alert', 'تم رفع الصور.');
                await loadPage();
            }

            if (event.target.id === 'locationForm') {
                event.preventDefault();
                const latitude = document.getElementById('locationLatitude').value;
                const longitude = document.getElementById('locationLongitude').value;
                if (!latitude || !longitude) throw new Error('حدد موقع العقار بالنقر على الخريطة أولًا.');
                await api(`/show_My_Property/show_Property/${propertyId}/add_detailed_locations`, {
                    method: 'POST',
                    body: { latitude, longitude },
                });
                showAlert('alert', 'تم حفظ الموقع.');
                await loadPage();
            }

            if (event.target.id === 'ratingForm') {
                event.preventDefault();
                await api(`/properties/${propertyId}/ratings`, {
                    method: 'POST',
                    body: {
                        rating: Number(document.getElementById('ratingValue').value),
                        comment: document.getElementById('ratingComment').value,
                    },
                });
                showAlert('alert', 'تم حفظ التقييم.');
                await loadPage();
            }

            if (event.target.id === 'messageForm') {
                event.preventDefault();
                const messageInput = document.getElementById('messageContent');
                const response = await api(`/properties/${propertyId}/messages`, {
                    method: 'POST',
                    body: { content: messageInput.value },
                });
                messageInput.value = '';

                const receiverId = Number(response.data?.receiver_id || currentProperty.user_id);
                location.href = `/app/chats/${propertyId}/${receiverId}`;
            }
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    });

    async function deleteImage(id) {
        if (!confirm('حذف هذه الصورة؟')) return;
        try {
            await api(`/show_My_Property/show_Property/delete_property_image/${id}`, { method: 'DELETE' });
            await loadPage();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    async function deleteLocation() {
        if (!confirm('حذف الموقع التفصيلي؟')) return;
        try {
            await api(`/show_My_Property/show_Property/${propertyId}/delet_detailed_locations`, { method: 'DELETE' });
            showAlert('alert', 'تم حذف الموقع.');
            await loadPage();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    async function deleteProperty() {
        if (!confirm('سيتم حذف العقار نهائيًا. هل أنت متأكد؟')) return;
        try {
            await api(`/show_My_Property/show_Property/delete_Property/${propertyId}`, { method: 'DELETE' });
            location.href = '{{ route('user.properties.my') }}';
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    async function deleteRating() {
        if (!confirm('حذف تقييمك؟')) return;
        try {
            await api(`/properties/${propertyId}/ratings`, { method: 'DELETE' });
            showAlert('alert', 'تم حذف التقييم.');
            await loadPage();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    async function downloadDocument(id, name) {
        try {
            await downloadProtected(`/properties/${propertyId}/documents/${id}/download`, name);
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    document.addEventListener('favorite:changed', (event) => {
        if (event.detail.id === propertyId) {
            currentProperty.is_favorite = event.detail.isFavorite;
            renderPage();
        }
    });

    loadPage();
</script>
@endpush
