<?php $__env->startSection('title', 'إدارة العقارات'); ?>
<?php $__env->startSection('page-title', 'إدارة العقارات'); ?>

<?php $__env->startSection('content'); ?>
<div class="grid stats">
    <div class="card stat"><div class="stat-label">كل العقارات</div><div class="stat-number"><?php echo e($stats['all']); ?></div></div>
    <div class="card stat"><div class="stat-label">بانتظار المراجعة</div><div class="stat-number"><?php echo e($stats['pending']); ?></div></div>
    <div class="card stat"><div class="stat-label">تمت الموافقة</div><div class="stat-number"><?php echo e($stats['approved']); ?></div></div>
    <div class="card stat"><div class="stat-label">مرفوضة</div><div class="stat-number"><?php echo e($stats['rejected']); ?></div></div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-body">
        <form method="GET" class="filters">
            <div style="flex:1; min-width:230px">
                <label>بحث بالمالك أو الموقع أو النوع</label>
                <input type="text" name="q" value="<?php echo e(request('q')); ?>" placeholder="اكتب كلمة البحث...">
            </div>
            <div style="min-width:190px">
                <label>حالة الموافقة</label>
                <select name="approval_status">
                    <option value="">كل الحالات</option>
                    <option value="pending" <?php if(request('approval_status') === 'pending'): echo 'selected'; endif; ?>>بانتظار المراجعة</option>
                    <option value="approved" <?php if(request('approval_status') === 'approved'): echo 'selected'; endif; ?>>مقبول</option>
                    <option value="rejected" <?php if(request('approval_status') === 'rejected'): echo 'selected'; endif; ?>>مرفوض</option>
                </select>
            </div>
            <button class="btn btn-primary" type="submit">تطبيق</button>
            <a class="btn btn-light" href="<?php echo e(route('admin.dashboard')); ?>">إلغاء التصفية</a>
        </form>
    </div>
</div>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
            <tr>
                <th>#</th>
                <th>العقار</th>
                <th>المالك</th>
                <th>السعر</th>
                <th>الوثائق</th>
                <th>التقييم</th>
                <th>الموافقة</th>
                <th>تاريخ الإرسال</th>
                <th>إجراءات</th>
            </tr>
            </thead>
            <tbody>
            <?php $__empty_1 = true; $__currentLoopData = $properties; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $property): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <tr>
                    <td><?php echo e($property->id); ?></td>
                    <td><strong><?php echo e($property->type); ?></strong><div class="muted"><?php echo e($property->location); ?></div></td>
                    <td><?php echo e($property->user->name); ?><div class="muted"><?php echo e($property->user->email); ?></div></td>
                    <td><?php echo e(number_format($property->price)); ?></td>
                    <td><?php echo e($property->documents_count); ?></td>
                    <td><?php echo e($property->ratings_count ? number_format($property->ratings_avg_rating, 1) . ' / 5' : 'لا يوجد'); ?></td>
                    <td>
                        <?php if($property->approval_status === 'approved'): ?>
                            <span class="badge badge-approved">مقبول</span>
                        <?php elseif($property->approval_status === 'rejected'): ?>
                            <span class="badge badge-rejected">مرفوض</span>
                        <?php else: ?>
                            <span class="badge badge-pending">قيد المراجعة</span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo e($property->created_at->format('Y-m-d H:i')); ?></td>
                    <td><a class="btn btn-primary" href="<?php echo e(route('admin.properties.show', $property)); ?>">عرض ومراجعة</a></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <tr><td colspan="9" style="text-align:center; padding:35px">لا توجد عقارات مطابقة.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="pagination"><?php echo e($properties->links()); ?></div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.admin', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/admin/properties/index.blade.php ENDPATH**/ ?>