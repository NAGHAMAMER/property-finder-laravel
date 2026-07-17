@extends('user.layout')
@section('title', 'إنشاء حساب')
@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <h1>إنشاء حساب جديد</h1>
        <p class="subtitle" style="text-align:center;margin-bottom:22px;">أنشئ حساب مستخدم عادي وابدأ بإضافة العقارات.</p>

        <div id="alert" class="alert"></div>

        <form id="registerForm" autocomplete="on">
            <div class="form-group">
                <label for="register_name">الاسم</label>
                <input
                    id="register_name"
                    name="name"
                    type="text"
                    required
                    maxlength="255"
                    autocomplete="name"
                >
            </div>

            <div class="form-group">
                <label for="register_email">البريد الإلكتروني</label>
                <input
                    id="register_email"
                    name="email"
                    type="email"
                    required
                    maxlength="255"
                    autocomplete="email"
                >
            </div>

            <div class="form-group">
                <label for="register_password">كلمة المرور</label>
                <input
                    id="register_password"
                    name="password"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <div class="form-group">
                <label for="register_password_confirmation">تأكيد كلمة المرور</label>
                <input
                    id="register_password_confirmation"
                    name="password_confirmation"
                    type="password"
                    required
                    minlength="8"
                    autocomplete="new-password"
                >
            </div>

            <button id="registerButton" class="btn btn-primary" style="width:100%" type="submit">
                إنشاء الحساب
            </button>
        </form>

        <p style="text-align:center;margin:18px 0 0;">
            لديك حساب؟
            <a style="color:#2563eb;font-weight:700" href="{{ route('user.login') }}">تسجيل الدخول</a>
        </p>
    </div>
</div>
@endsection

@push('scripts')
<script>
    if (token()) {
        window.location.href = '{{ route('user.dashboard') }}';
    }

    const registerForm = document.getElementById('registerForm');
    const registerButton = document.getElementById('registerButton');

    registerForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const nameInput = document.getElementById('register_name');
        const emailInput = document.getElementById('register_email');
        const passwordInput = document.getElementById('register_password');
        const passwordConfirmationInput = document.getElementById('register_password_confirmation');

        const cleanName = nameInput.value.trim();
        const cleanEmail = emailInput.value.trim();

        if (!cleanName) {
            showAlert('alert', 'حقل الاسم مطلوب.', 'error');
            nameInput.focus();
            return;
        }

        if (!cleanEmail) {
            showAlert('alert', 'حقل البريد الإلكتروني مطلوب.', 'error');
            emailInput.focus();
            return;
        }

        if (passwordInput.value.length < 8) {
            showAlert('alert', 'يجب أن تتكون كلمة المرور من 8 أحرف على الأقل.', 'error');
            passwordInput.focus();
            return;
        }

        if (passwordInput.value !== passwordConfirmationInput.value) {
            showAlert('alert', 'تأكيد كلمة المرور غير مطابق.', 'error');
            passwordConfirmationInput.focus();
            return;
        }

        registerButton.disabled = true;
        registerButton.textContent = 'جارٍ إنشاء الحساب...';

        try {
            // استخدام FormData مباشرة من النموذج يضمن إرسال name/email/password
            // إلى Laravel بأسمائها الصحيحة دون الاعتماد على أسماء متغيرات JavaScript.
            const formData = new FormData(registerForm);
            formData.set('name', cleanName);
            formData.set('email', cleanEmail);
            formData.set('password', passwordInput.value);
            formData.set('password_confirmation', passwordConfirmationInput.value);

            const data = await api('/register', {
                method: 'POST',
                body: formData
            });

            saveAuth(data);
            window.location.href = '{{ route('user.dashboard') }}';
        } catch (error) {
            showAlert('alert', error.message, 'error');
        } finally {
            registerButton.disabled = false;
            registerButton.textContent = 'إنشاء الحساب';
        }
    });
</script>
@endpush
