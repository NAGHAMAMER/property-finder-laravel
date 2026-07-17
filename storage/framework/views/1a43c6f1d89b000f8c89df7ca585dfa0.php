<?php $__env->startSection('title', 'إعادة تعيين كلمة المرور'); ?>
<?php $__env->startSection('content'); ?>
<div class="auth-shell">
    <div class="card auth-card">
        <h1>إعادة تعيين كلمة المرور</h1>
        <p class="subtitle" style="text-align:center;margin-bottom:22px;">أدخل الرمز المرسل إلى بريدك ثم اختر كلمة مرور جديدة.</p>

        <?php if(session('success')): ?>
            <div class="alert success" style="display:block;"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert error" style="display:block;"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('user.password.reset')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="<?php echo e(old('email', $email ?? '')); ?>" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="code">رمز التحقق</label>
                <input id="code" name="code" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" value="<?php echo e(old('code')); ?>" required placeholder="123456">
            </div>

            <div class="form-group">
                <label for="password">كلمة المرور الجديدة</label>
                <input id="password" name="password" type="password" minlength="8" required autocomplete="new-password">
            </div>

            <div class="form-group">
                <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
                <input id="password_confirmation" name="password_confirmation" type="password" minlength="8" required autocomplete="new-password">
            </div>

            <button class="btn btn-primary" style="width:100%" type="submit">تغيير كلمة المرور</button>
        </form>

        <p style="text-align:center;margin:18px 0 0;">
            <a style="color:#2563eb;font-weight:700" href="<?php echo e(route('user.password.forgot')); ?>">إرسال رمز جديد</a>
        </p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/auth/reset-password.blade.php ENDPATH**/ ?>