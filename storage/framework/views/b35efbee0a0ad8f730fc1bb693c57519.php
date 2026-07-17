<?php $__env->startSection('title', 'نسيت كلمة المرور'); ?>
<?php $__env->startSection('content'); ?>
<div class="auth-shell">
    <div class="card auth-card">
        <h1>نسيت كلمة المرور؟</h1>
        <p class="subtitle" style="text-align:center;margin-bottom:22px;">أدخل بريدك الإلكتروني وسنرسل لك رمز تحقق من 6 أرقام.</p>

        <?php if(session('success')): ?>
            <div class="alert success" style="display:block;"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert error" style="display:block;"><?php echo e($errors->first()); ?></div>
        <?php endif; ?>

        <form method="POST" action="<?php echo e(route('user.password.send-code')); ?>">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="<?php echo e(old('email')); ?>" required autocomplete="email">
            </div>
            <button class="btn btn-primary" style="width:100%" type="submit">إرسال الرمز</button>
        </form>

        <p style="text-align:center;margin:18px 0 0;">
            <a style="color:#2563eb;font-weight:700" href="<?php echo e(route('user.login')); ?>">العودة لتسجيل الدخول</a>
        </p>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/auth/forgot-password.blade.php ENDPATH**/ ?>