@extends('user.layout')
@section('title', 'الحساب')
@section('content')
<main class="container">
    <div class="page-head">
        <div>
            <h1>حسابي</h1>
            <p class="subtitle">عرض بيانات الحساب وتغيير كلمة المرور من داخل التطبيق.</p>
        </div>
    </div>

    <div id="alert" class="alert"></div>

    <div class="grid grid-2">
        <section class="card">
            <h2 class="section-title">بيانات المستخدم</h2>
            <div id="profileInfo"><div class="loading">جارٍ تحميل البيانات...</div></div>
        </section>

        <section class="card">
            <h2 class="section-title">تغيير كلمة المرور</h2>
            <form id="changePasswordForm">
                <div class="form-group">
                    <label for="currentPassword">كلمة المرور الحالية</label>
                    <input id="currentPassword" type="password" required autocomplete="current-password">
                </div>
                <div class="form-group">
                    <label for="newPassword">كلمة المرور الجديدة</label>
                    <input id="newPassword" type="password" minlength="8" required autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="newPasswordConfirmation">تأكيد كلمة المرور الجديدة</label>
                    <input id="newPasswordConfirmation" type="password" minlength="8" required autocomplete="new-password">
                </div>
                <button id="changePasswordButton" class="btn btn-primary" type="submit">تغيير كلمة المرور</button>
            </form>
        </section>
    </div>
</main>
@endsection

@push('scripts')
<script>
    const profileInfo = document.getElementById('profileInfo');
    const changePasswordForm = document.getElementById('changePasswordForm');
    const changePasswordButton = document.getElementById('changePasswordButton');

    async function loadAccount() {
        if (!requireAuth()) return;
        try {
            const user = await api('/user');
            profileInfo.innerHTML = `
                <p><strong>الاسم:</strong> ${esc(user.name)}</p>
                <p><strong>البريد:</strong> ${esc(user.email)}</p>
                <p><strong>نوع الحساب:</strong> مستخدم عادي</p>`;
        } catch (error) {
            profileInfo.innerHTML = '';
            showAlert('alert', error.message, 'error');
        }
    }

    changePasswordForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const password = document.getElementById('newPassword').value;
        const confirmation = document.getElementById('newPasswordConfirmation').value;
        if (password !== confirmation) {
            showAlert('alert', 'تأكيد كلمة المرور غير مطابق.', 'error');
            return;
        }

        try {
            changePasswordButton.disabled = true;
            changePasswordButton.textContent = 'جارٍ التغيير...';
            const response = await api('/change-password', {
                method: 'POST',
                body: {
                    current_password: document.getElementById('currentPassword').value,
                    password,
                    password_confirmation: confirmation,
                },
            });

            saveAuth(response);
            changePasswordForm.reset();
            showAlert('alert', response.message || 'تم تغيير كلمة المرور بنجاح.');
        } catch (error) {
            showAlert('alert', error.message, 'error');
        } finally {
            changePasswordButton.disabled = false;
            changePasswordButton.textContent = 'تغيير كلمة المرور';
        }
    });

    loadAccount();
</script>
@endpush
