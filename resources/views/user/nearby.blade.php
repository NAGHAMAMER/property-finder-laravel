@extends('user.layout')

@section('title', 'العقارات القريبة')

@section('content')
<main class="container">
    <div class="page-head">
        <div>
            <h1>العقارات القريبة</h1>

            <p class="subtitle">
                حدد موقعك على الخريطة ثم اختر المسافة القصوى بالكيلومتر.
            </p>
        </div>

        <button
            id="geoBtn"
            class="btn btn-light"
            type="button"
        >
            📍 استخدام موقعي الحالي
        </button>
    </div>

    <div id="alert" class="alert"></div>

    <form id="nearbyForm" class="card">
        <div id="nearbyMap" class="map-box"></div>

        <p class="map-help">
            انقر على أي نقطة في الخريطة لاستخدامها كنقطة انطلاق للبحث.
        </p>

        <div class="grid grid-3" style="margin-top:14px;">
            <div class="form-group">
                <label for="latitude">خط العرض</label>

                <input
                    id="latitude"
                    type="number"
                    step="any"
                    min="-90"
                    max="90"
                    readonly
                    required
                >
            </div>

            <div class="form-group">
                <label for="longitude">خط الطول</label>

                <input
                    id="longitude"
                    type="number"
                    step="any"
                    min="-180"
                    max="180"
                    readonly
                    required
                >
            </div>

            <div class="form-group">
                <label for="maxDistance">
                    المسافة القصوى (كم)
                </label>

                <input
                    id="maxDistance"
                    type="number"
                    min="1"
                    max="1000"
                    value="50"
                    required
                >
            </div>
        </div>

        <button
            class="btn btn-primary"
            type="submit"
        >
            عرض العقارات القريبة
        </button>
    </form>

    <div
        id="results"
        class="card"
        style="margin-top:18px;"
    >
        <div class="empty">
            حدد موقعك على الخريطة للبدء.
        </div>
    </div>
</main>
@endsection

@push('scripts')
<script>
    requireAuth();

    const nearbyForm = document.getElementById('nearbyForm');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const maxDistanceInput = document.getElementById('maxDistance');
    const resultsElement = document.getElementById('results');
    const geoButton = document.getElementById('geoBtn');

    const nearbyPicker = createLocationMap({
        mapId: 'nearbyMap',
        latInputId: 'latitude',
        lngInputId: 'longitude',
    });

    /*
     * يجب استخدام featureGroup وليس layerGroup
     * لأننا نحتاج إلى getBounds() لتكبير الخريطة
     * بحيث تشمل جميع نتائج العقارات.
     */
    const resultMarkers = L.featureGroup()
        .addTo(nearbyPicker.map);

    geoButton.addEventListener('click', async () => {
        try {
            await nearbyPicker.locateCurrent();
        } catch (error) {
            showAlert(
                'alert',
                error.message || 'تعذر تحديد موقعك الحالي.',
                'error'
            );
        }
    });

    nearbyForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        const latitude = Number(latitudeInput.value);
        const longitude = Number(longitudeInput.value);
        const maxDistance = Number(maxDistanceInput.value);

        if (
            !Number.isFinite(latitude) ||
            !Number.isFinite(longitude)
        ) {
            showAlert(
                'alert',
                'حدد موقعك على الخريطة أولًا.',
                'error'
            );

            return;
        }

        if (
            !Number.isFinite(maxDistance) ||
            maxDistance <= 0
        ) {
            showAlert(
                'alert',
                'يرجى إدخال مسافة صحيحة.',
                'error'
            );

            return;
        }

        try {
            resultsElement.innerHTML = `
                <div class="empty">
                    جارٍ البحث عن العقارات القريبة...
                </div>
            `;

            const data = await api('/nearby-properties', {
                method: 'POST',
                body: {
                    latitude: latitude,
                    longitude: longitude,
                    max_distance: maxDistance,
                },
            });

            const properties = Array.isArray(data.data)
                ? data.data
                : [];

            /*
             * حذف علامات نتائج البحث السابقة قبل عرض النتائج الجديدة.
             */
            resultMarkers.clearLayers();

            properties.forEach((property) => {
                const propertyLatitude = Number(property.latitude);
                const propertyLongitude = Number(property.longitude);
                const propertyDistance = Number(property.distance);

                /*
                 * تجاهل أي عقار لا يحتوي إحداثيات صالحة.
                 */
                if (
                    !Number.isFinite(propertyLatitude) ||
                    !Number.isFinite(propertyLongitude)
                ) {
                    return;
                }

                const distanceText = Number.isFinite(propertyDistance)
                    ? `${propertyDistance.toFixed(2)} كم`
                    : 'المسافة غير متوفرة';

                const marker = L.marker([
                    propertyLatitude,
                    propertyLongitude,
                ]);

                marker.bindPopup(`
                    <div style="text-align:right;">
                        <strong>
                            ${esc(property.type || `عقار #${property.id}`)}
                        </strong>

                        <br>

                        ${
                            property.location
                                ? `${esc(property.location)}<br>`
                                : ''
                        }

                        المسافة: ${distanceText}

                        <br>

                        <a href="/app/properties/${property.id}">
                            عرض تفاصيل العقار
                        </a>
                    </div>
                `);

                resultMarkers.addLayer(marker);
            });

            /*
             * تحريك وتكبير الخريطة لعرض جميع علامات النتائج.
             */
            if (resultMarkers.getLayers().length > 0) {
                const bounds = resultMarkers.getBounds();

                if (bounds.isValid()) {
                    nearbyPicker.map.fitBounds(bounds, {
                        padding: [35, 35],
                        maxZoom: 15,
                    });
                }
            }

            if (properties.length === 0) {
                resultsElement.innerHTML = `
                    <div class="empty">
                        لا توجد عقارات ضمن هذه المسافة.
                    </div>
                `;

                return;
            }

            resultsElement.innerHTML = `
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>رقم العقار</th>
                                <th>النوع</th>
                                <th>الموقع</th>
                                <th>خط العرض</th>
                                <th>خط الطول</th>
                                <th>المسافة</th>
                                <th></th>
                            </tr>
                        </thead>

                        <tbody>
                            ${properties.map((property) => {
                                const distance = Number(property.distance);

                                const distanceText = Number.isFinite(distance)
                                    ? `${distance.toFixed(2)} كم`
                                    : '-';

                                return `
                                    <tr>
                                        <td>
                                            ${esc(property.id)}
                                        </td>

                                        <td>
                                            ${esc(property.type || '-')}
                                        </td>

                                        <td>
                                            ${esc(property.location || '-')}
                                        </td>

                                        <td>
                                            ${esc(property.latitude ?? '-')}
                                        </td>

                                        <td>
                                            ${esc(property.longitude ?? '-')}
                                        </td>

                                        <td>
                                            ${distanceText}
                                        </td>

                                        <td>
                                            <a
                                                class="btn btn-primary btn-sm"
                                                href="/app/properties/${property.id}"
                                            >
                                                التفاصيل
                                            </a>
                                        </td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
            `;
        } catch (error) {
            resultsElement.innerHTML = `
                <div class="empty">
                    تعذر تحميل العقارات القريبة.
                </div>
            `;

            showAlert(
                'alert',
                error.message || 'حدث خطأ أثناء البحث حسب الموقع.',
                'error'
            );
        }
    });
</script>
@endpush
