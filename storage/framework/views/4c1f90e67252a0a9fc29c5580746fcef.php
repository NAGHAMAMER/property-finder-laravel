<?php $__env->startSection('title', 'مراجعة العقار #' . $property->id); ?>
<?php $__env->startSection('page-title', 'مراجعة العقار #' . $property->id); ?>

<?php $__env->startSection('content'); ?>
<div style="margin-bottom:16px"><a class="btn btn-light" href="<?php echo e(route('admin.dashboard')); ?>">العودة إلى جميع العقارات</a></div>

<div class="grid details-grid">
    <div class="grid">
        <section class="card"><div class="card-body">
            <h2 class="section-title">بيانات العقار</h2>
            <div class="grid info-grid">
                <div class="info-item"><strong>النوع</strong><?php echo e($property->type); ?></div>
                <div class="info-item"><strong>الموقع</strong><?php echo e($property->location); ?></div>
                <div class="info-item"><strong>السعر</strong><?php echo e(number_format($property->price)); ?></div>
                <div class="info-item"><strong>المساحة</strong><?php echo e($property->area); ?></div>
                <div class="info-item"><strong>غرف النوم</strong><?php echo e($property->badroom); ?></div>
                <div class="info-item"><strong>الحمامات</strong><?php echo e($property->bathroom); ?></div>
                <div class="info-item"><strong>حالة العقار</strong><?php echo e($property->status); ?></div>
                <div class="info-item"><strong>التقييم</strong><?php echo e($property->ratings_count ? number_format($property->ratings_avg_rating, 1) . ' / 5 (' . $property->ratings_count . ')' : 'لا يوجد'); ?></div>
            </div>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="section-title">أوراق الإثبات الخاصة</h2>
            <p class="muted">هذه الملفات لا تظهر إلا للأدمن وصاحب العقار.</p>
            <?php $__empty_1 = true; $__currentLoopData = $property->documents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $document): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="document">
                    <div><strong><?php echo e($document->original_name); ?></strong><div class="muted"><?php echo e($document->mime_type ?: 'ملف'); ?> · <?php echo e($document->file_size ? number_format($document->file_size / 1024, 1) . ' KB' : ''); ?></div></div>
                    <a class="btn btn-primary" href="<?php echo e(route('admin.documents.download', $document)); ?>">تنزيل آمن</a>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="alert alert-error">لم يرفع صاحب العقار أي وثيقة.</div>
            <?php endif; ?>
        </div></section>

        <?php if($property->images->isNotEmpty()): ?>
            <section class="card"><div class="card-body">
                <h2 class="section-title">صور العقار</h2>
                <div class="images">
                    <?php $__currentLoopData = $property->images; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $image): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <img src="<?php echo e(asset('storage/' . $image->image_path)); ?>" alt="صورة العقار">
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </div>
            </div></section>
        <?php endif; ?>

        <section class="card"><div class="card-body">
            <h2 class="section-title">تقييمات المستخدمين</h2>
            <?php $__empty_1 = true; $__currentLoopData = $property->ratings; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rating): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="review">
                    <strong><?php echo e($rating->user->name); ?></strong>
                    <div class="stars"><?php echo e(str_repeat('★', $rating->rating)); ?><?php echo e(str_repeat('☆', 5 - $rating->rating)); ?></div>
                    <?php if($rating->comment): ?><div><?php echo e($rating->comment); ?></div><?php endif; ?>
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="muted">لا توجد تقييمات حتى الآن.</div>
            <?php endif; ?>
        </div></section>
    </div>

    <aside class="grid">
        <?php if($property->detailed_locations): ?>
            <section class="card"><div class="card-body">
                <h2 class="section-title">الموقع الدقيق على الخريطة</h2>
                <div id="adminPropertyMap" class="map-box"></div>
                <div class="muted" style="margin-top:10px">
                    <?php echo e($property->detailed_locations->latitude); ?>, <?php echo e($property->detailed_locations->longitude); ?>

                </div>
            </div></section>
        <?php endif; ?>

        <section class="card"><div class="card-body">
            <h2 class="section-title">صاحب العقار</h2>
            <div><strong><?php echo e($property->user->name); ?></strong></div>
            <div class="muted"><?php echo e($property->user->email); ?></div>
            <div class="muted" style="margin-top:8px">مسجل منذ <?php echo e($property->user->created_at->format('Y-m-d')); ?></div>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="section-title">قرار الأدمن</h2>
            <div style="margin-bottom:15px">
                <?php if($property->approval_status === 'approved'): ?>
                    <span class="badge badge-approved">العقار مقبول</span>
                <?php elseif($property->approval_status === 'rejected'): ?>
                    <span class="badge badge-rejected">العقار مرفوض</span>
                <?php else: ?>
                    <span class="badge badge-pending">بانتظار القرار</span>
                <?php endif; ?>
            </div>

            <?php if($property->rejection_reason): ?>
                <div class="alert alert-error"><strong>سبب الرفض:</strong><br><?php echo e($property->rejection_reason); ?></div>
            <?php endif; ?>

            <?php if($property->reviewer): ?>
                <div class="muted" style="margin-bottom:14px">آخر مراجعة بواسطة <?php echo e($property->reviewer->name); ?> بتاريخ <?php echo e(optional($property->reviewed_at)->format('Y-m-d H:i')); ?></div>
            <?php endif; ?>

            <form method="POST" action="<?php echo e(route('admin.properties.approve', $property)); ?>" style="margin-bottom:16px">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <button class="btn btn-success" style="width:100%" type="submit">الموافقة وإشعار المالك</button>
            </form>

            <form method="POST" action="<?php echo e(route('admin.properties.reject', $property)); ?>">
                <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                <label for="rejection_reason">سبب الرفض</label>
                <textarea id="rejection_reason" name="rejection_reason" required placeholder="اكتب سببًا واضحًا ليصل إلى صاحب العقار..."><?php echo e(old('rejection_reason', $property->rejection_reason)); ?></textarea>
                <button class="btn btn-danger" style="width:100%; margin-top:10px" type="submit">رفض العقار وإرسال السبب</button>
            </form>
        </div></section>

        <section class="card"><div class="card-body">
            <h2 class="section-title">حذف العقار</h2>
            <p class="muted">الحذف نهائي ويشمل الصور والوثائق والتقييمات.</p>
            <form method="POST" action="<?php echo e(route('admin.properties.destroy', $property)); ?>" onsubmit="return confirm('هل أنت متأكد من حذف العقار نهائيًا؟')">
                <?php echo csrf_field(); ?> <?php echo method_field('DELETE'); ?>
                <button class="btn btn-danger" style="width:100%" type="submit">حذف العقار نهائيًا</button>
            </form>
        </div></section>
    </aside>
</div>
<?php $__env->stopSection(); ?>


<?php if($property->detailed_locations): ?>
<?php $__env->startPush('scripts'); ?>
<script>
    const adminPropertyMap = L.map('adminPropertyMap').setView([<?php echo e($property->detailed_locations->latitude); ?>, <?php echo e($property->detailed_locations->longitude); ?>], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(adminPropertyMap);
    L.marker([<?php echo e($property->detailed_locations->latitude); ?>, <?php echo e($property->detailed_locations->longitude); ?>]).addTo(adminPropertyMap);
</script>
<?php $__env->stopPush(); ?>
<?php endif; ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/admin/properties/show.blade.php ENDPATH**/ ?>