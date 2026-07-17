<?php $__env->startSection('title', 'جميع العقارات'); ?>
<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head">
        <div><h1>العقارات المعتمدة</h1><p class="subtitle">جميع العقارات التي وافق عليها الأدمن.</p></div>
        <a class="btn btn-primary" href="<?php echo e(route('user.properties.create')); ?>">+ إضافة عقار</a>
    </div>
    <div id="alert" class="alert"></div>
    <div id="properties" class="grid grid-3"><div class="loading">جارٍ التحميل...</div></div>
</main>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
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
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/properties/index.blade.php ENDPATH**/ ?>