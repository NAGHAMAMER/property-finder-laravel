<?php $__env->startSection('title', 'تسجيل الدخول'); ?>
<?php $__env->startSection('content'); ?>
<div class="auth-shell">
    <div class="card auth-card">
        <h1>تسجيل الدخول</h1>
        <p class="subtitle" style="text-align:center;margin-bottom:22px;">ادخل إلى حسابك لإدارة العقارات والبحث والمفضلة.</p>
        <?php if(session('success')): ?>
            <div class="alert success" style="display:block;"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <div id="alert" class="alert"></div>
        <form id="loginForm">
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" required autocomplete="email">
            </div>
            <div class="form-group">
                <label for="password">كلمة المرور</label>
                <input id="password" name="password" type="password" required autocomplete="current-password">
            </div>
            <button class="btn btn-primary" style="width:100%" type="submit">دخول</button>
        </form>
        <p style="text-align:center;margin:14px 0 0;"><a style="color:#2563eb;font-weight:700" href="<?php echo e(route('user.password.forgot')); ?>">نسيت كلمة المرور؟</a></p>
        <p style="text-align:center;margin:14px 0 0;">ليس لديك حساب؟ <a style="color:#2563eb;font-weight:700" href="<?php echo e(route('user.register')); ?>">إنشاء حساب</a></p>
        <p style="text-align:center;margin-top:12px;"><a class="muted" href="<?php echo e(route('admin.login')); ?>" onclick="switchToAdmin(event)">دخول الأدمن</a></p>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startPush('scripts'); ?>
<script>
    async function switchToAdmin(event) {
        event.preventDefault();
        const target = event.currentTarget.href;
        try {
            if (token()) await api('/logout', { method: 'POST' });
        } catch (_) {
            // Continue to the isolated admin login even if the old user token expired.
        }
        clearAuth();
        window.location.href = target;
    }

    document.getElementById('loginForm').addEventListener('submit', async (e) => {
        e.preventDefault();
        const button = e.submitter;
        button.disabled = true;

        const emailInput = document.getElementById('email');
        const passwordInput = document.getElementById('password');

        try {
            const data = await api('/login', {
                method: 'POST',
                body: {
                    email: emailInput.value.trim(),
                    password: passwordInput.value
                }
            });
            saveAuth(data);
            location.href = '<?php echo e(route('user.dashboard')); ?>';
        } catch (err) {
            showAlert('alert', err.message, 'error');
        } finally {
            button.disabled = false;
        }
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/auth/login.blade.php ENDPATH**/ ?>