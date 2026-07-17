<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تم تغيير كلمة المرور</title>
    <style>
        body { margin:0; min-height:100vh; display:grid; place-items:center; font-family:Tahoma,Arial,sans-serif; background:#f8fafc; color:#0f172a; }
        .card { width:min(92%,460px); padding:30px; border:1px solid #e2e8f0; border-radius:18px; background:#fff; text-align:center; box-shadow:0 15px 45px rgba(15,23,42,.1); }
        .icon { width:70px; height:70px; display:grid; place-items:center; margin:0 auto 16px; border-radius:50%; background:#dcfce7; color:#15803d; font-size:34px; font-weight:800; }
        h1 { margin:0 0 10px; font-size:24px; }
        p { margin:0; color:#64748b; line-height:1.8; }
    </style>
</head>
<body>
<div class="card">
    <div class="icon">✓</div>
    <h1>تم تغيير كلمة المرور بنجاح</h1>
    <p>جارٍ تسجيل دخولك وفتح التطبيق...</p>
</div>
<script>
    (() => {
        const loginToken = @json($loginToken);
        const userData = @json($userData);

        if (!loginToken || !userData) {
            window.location.replace(@json(route('user.login')));
            return;
        }

        localStorage.setItem('auth_token', loginToken);
        localStorage.setItem('auth_user', JSON.stringify(userData));
        window.location.replace(@json(route('user.dashboard')));
    })();
</script>
</body>
</html>
