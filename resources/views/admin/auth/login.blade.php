<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>دخول الأدمن</title>
    <style>
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: linear-gradient(135deg, #0f172a, #1d4ed8); font-family: Tahoma, Arial, sans-serif; padding: 20px; }
        .box { width: min(440px, 100%); background: white; border-radius: 18px; padding: 32px; box-shadow: 0 25px 70px rgba(0,0,0,.25); }
        h1 { margin: 0 0 8px; color: #0f172a; }
        p { color: #64748b; margin: 0 0 25px; }
        label { display: block; font-size: 13px; font-weight: 700; margin: 14px 0 7px; color: #334155; }
        input[type="email"], input[type="password"] { width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px; font: inherit; }
        .remember { display: flex; gap: 8px; align-items: center; margin: 15px 0; color: #475569; font-size: 14px; }
        button { width: 100%; border: 0; border-radius: 10px; background: #2563eb; color: white; font: inherit; font-weight: 800; padding: 13px; cursor: pointer; }
        button:hover { background: #1d4ed8; }
        .errors { background: #fee2e2; border: 1px solid #fecaca; color: #991b1b; padding: 12px; border-radius: 9px; margin-bottom: 12px; }
        .success { background: #dcfce7; border: 1px solid #86efac; color: #166534; padding: 12px; border-radius: 9px; margin-bottom: 12px; }
        .link { display:block; text-align:center; margin-top:14px; color:#2563eb; font-weight:700; text-decoration:none; }
    </style>
</head>
<body>
<div class="box">
    <h1>تسجيل دخول الأدمن</h1>
    <p>أدخل بيانات حساب الإدارة لمراجعة طلبات العقارات.</p>

    @if(session('success'))
        <div class="success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="errors">
            @foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('admin.login.store') }}">
        @csrf
        <label for="email">البريد الإلكتروني</label>
        <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email">

        <label for="password">كلمة المرور</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">

        <label class="remember"><input type="checkbox" name="remember" value="1"> تذكرني</label>
        <button type="submit">دخول إلى لوحة التحكم</button>
    </form>

    <a class="link" href="{{ route('admin.password.forgot') }}">نسيت كلمة مرور الأدمن؟</a>
</div>
</body>
</html>
