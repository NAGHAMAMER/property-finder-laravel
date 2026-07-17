<?php $__env->startSection('title', 'المفضلة'); ?>
<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head"><div><h1>العقارات المفضلة</h1><p class="subtitle">العقارات التي حفظتها للرجوع إليها لاحقًا.</p></div></div>
    <div id="alert" class="alert"></div>
    <div id="properties" class="grid grid-3"><div class="loading">جارٍ التحميل...</div></div>
</main>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
    const favoritePropertiesElement = document.getElementById('properties');

    async function loadFavorites() {
        if (!requireAuth()) return;
        try { const data=await api('/favorites'); favoritePropertiesElement.innerHTML=data.data?.length?data.data.map(propertyCard).join(''):'<div class="card empty">لا توجد عقارات في المفضلة.</div>'; }
        catch(e){showAlert('alert',e.message,'error');}
    }
    loadFavorites(); document.addEventListener('favorite:changed', loadFavorites);
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/favorites.blade.php ENDPATH**/ ?>