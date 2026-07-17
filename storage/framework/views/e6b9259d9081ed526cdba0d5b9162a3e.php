<?php $__env->startSection('title', 'العقارات القريبة'); ?>
<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head">
        <div>
            <h1>العقارات القريبة</h1>
            <p class="subtitle">حدد موقعك على الخريطة ثم اختر المسافة القصوى بالكيلومتر.</p>
        </div>
        <button id="geoBtn" class="btn btn-light" type="button">📍 استخدام موقعي الحالي</button>
    </div>

    <div id="alert" class="alert"></div>

    <form id="nearbyForm" class="card">
        <div id="nearbyMap" class="map-box"></div>
        <p class="map-help">انقر على أي نقطة في الخريطة لاستخدامها كنقطة انطلاق للبحث.</p>

        <div class="grid grid-3" style="margin-top:14px;">
            <div class="form-group"><label for="latitude">خط العرض</label><input id="latitude" type="number" step="any" min="-90" max="90" readonly required></div>
            <div class="form-group"><label for="longitude">خط الطول</label><input id="longitude" type="number" step="any" min="-180" max="180" readonly required></div>
            <div class="form-group"><label for="maxDistance">المسافة القصوى (كم)</label><input id="maxDistance" type="number" min="1" max="1000" value="50" required></div>
        </div>

        <button class="btn btn-primary" type="submit">عرض العقارات القريبة</button>
    </form>

    <div id="results" class="card" style="margin-top:18px;">
        <div class="empty">حدد موقعك على الخريطة للبدء.</div>
    </div>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    requireAuth();

    const nearbyForm = document.getElementById('nearbyForm');
    const latitudeInput = document.getElementById('latitude');
    const longitudeInput = document.getElementById('longitude');
    const maxDistanceInput = document.getElementById('maxDistance');
    const resultsElement = document.getElementById('results');
    const nearbyPicker = createLocationMap({
        mapId: 'nearbyMap',
        latInputId: 'latitude',
        lngInputId: 'longitude',
    });
    const resultMarkers = L.layerGroup().addTo(nearbyPicker.map);

    document.getElementById('geoBtn').addEventListener('click', async () => {
        try {
            await nearbyPicker.locateCurrent();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    });

    nearbyForm.addEventListener('submit', async (event) => {
        event.preventDefault();

        if (!latitudeInput.value || !longitudeInput.value) {
            showAlert('alert', 'حدد موقعك على الخريطة أولًا.', 'error');
            return;
        }

        try {
            const data = await api('/nearby-properties', {
                method: 'POST',
                body: {
                    latitude: latitudeInput.value,
                    longitude: longitudeInput.value,
                    max_distance: maxDistanceInput.value,
                },
            });

            const properties = data.data || [];
            resultMarkers.clearLayers();

            properties.forEach((property) => {
                L.marker([Number(property.latitude), Number(property.longitude)])
                    .bindPopup(`<a href="/app/properties/${property.id}">عرض العقار #${property.id}</a><br>${Number(property.distance).toFixed(2)} كم`)
                    .addTo(resultMarkers);
            });

            if (properties.length) {
                const bounds = resultMarkers.getBounds();
                if (bounds.isValid()) nearbyPicker.map.fitBounds(bounds.pad(0.2));
            }

            resultsElement.innerHTML = properties.length
                ? `<div class="table-wrap"><table><thead><tr><th>رقم العقار</th><th>خط العرض</th><th>خط الطول</th><th>المسافة</th><th></th></tr></thead><tbody>${properties.map(property => `<tr><td>${property.id}</td><td>${esc(property.latitude)}</td><td>${esc(property.longitude)}</td><td>${Number(property.distance).toFixed(2)} كم</td><td><a class="btn btn-primary btn-sm" href="/app/properties/${property.id}">التفاصيل</a></td></tr>`).join('')}</tbody></table></div>`
                : '<div class="empty">لا توجد عقارات ضمن هذه المسافة.</div>';
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/nearby.blade.php ENDPATH**/ ?>