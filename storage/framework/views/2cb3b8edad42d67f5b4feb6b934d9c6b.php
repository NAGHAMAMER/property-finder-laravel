<?php $__env->startSection('title', 'عقاراتي'); ?>
<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head">
        <div>
            <h1>عقاراتي</h1>
            <p class="subtitle">العقارات المعلقة والمعتمدة والمرفوضة الخاصة بك.</p>
        </div>
        <a class="btn btn-primary" href="<?php echo e(route('user.properties.create')); ?>">+ إضافة عقار</a>
    </div>

    <div id="alert" class="alert"></div>
    <div id="myPropertiesList" class="grid grid-3">
        <div class="loading">جارٍ التحميل...</div>
    </div>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    async function loadMyProperties() {
        if (!requireAuth()) return;

        const container = document.getElementById('myPropertiesList');
        try {
            const data = await api('/my-properties');
            container.innerHTML = data.data?.length
                ? data.data.map(propertyCard).join('')
                : '<div class="card empty" style="grid-column:1/-1">لم تضف أي عقار بعد.</div>';
        } catch (error) {
            container.innerHTML = '<div class="card empty" style="grid-column:1/-1">تعذر تحميل عقاراتك.</div>';
            showAlert('alert', error.message, 'error');
        }
    }

    document.addEventListener('favorite:changed', loadMyProperties);
    loadMyProperties();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/properties/my.blade.php ENDPATH**/ ?>