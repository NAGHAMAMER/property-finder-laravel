<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تعيين كلمة مرور جديدة للأدمن</title>
    <style>
        * { box-sizing: border-box; }
        body { margin:0; min-height:100vh; display:grid; place-items:center; padding:20px; background:linear-gradient(135deg,#0f172a,#1d4ed8); font-family:Tahoma,Arial,sans-serif; }
        .box { width:min(480px,100%); background:#fff; border-radius:18px; padding:32px; box-shadow:0 25px 70px rgba(0,0,0,.25); }
        h1 { margin:0 0 8px; color:#0f172a; }
        p { color:#64748b; line-height:1.8; }
        label { display:block; font-size:13px; font-weight:700; margin:14px 0 7px; color:#334155; }
        input { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:12px; font:inherit; }
        button { width:100%; border:0; border-radius:10px; background:#2563eb; color:#fff; font:inherit; font-weight:800; padding:13px; cursor:pointer; margin-top:18px; }
        .errors { background:#fee2e2; border:1px solid #fecaca; color:#991b1b; padding:12px; border-radius:9px; margin:14px 0; }
        .success { background:#dcfce7; border:1px solid #86efac; color:#166534; padding:12px; border-radius:9px; margin:14px 0; }
        .link { display:block; text-align:center; margin-top:16px; color:#2563eb; font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
<div class="box">
    <h1>كلمة مرور جديدة للأدمن</h1>
    <p>أدخل الرمز الذي وصلك على البريد، ثم اختر كلمة مرور جديدة.</p>

    @if(session('success'))<div class="success">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="errors">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    @endif

    <form method="POST" action="{{ route('admin.password.reset') }}">
        @csrf
        <label for="email">البريد الإلكتروني</label>
        <input id="email" type="email" name="email" value="{{ old('email', $email) }}" required autocomplete="email">

        <label for="code">رمز التحقق</label>
        <input id="code" type="text" name="code" value="{{ old('code') }}" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" placeholder="123456">

        <label for="password">كلمة المرور الجديدة</label>
        <input id="password" type="password" name="password" required minlength="8" autocomplete="new-password">

        <label for="password_confirmation">تأكيد كلمة المرور الجديدة</label>
        <input id="password_confirmation" type="password" name="password_confirmation" required minlength="8" autocomplete="new-password">

        <button type="submit">تغيير كلمة المرور</button>
    </form>

    <a class="link" href="{{ route('admin.password.forgot') }}">إرسال رمز جديد</a>
</div>
</body>
</html>
