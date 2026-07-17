@extends('user.layout')
@section('title', 'نسيت كلمة المرور')
@section('content')
<div class="auth-shell">
    <div class="card auth-card">
        <h1>نسيت كلمة المرور؟</h1>
        <p class="subtitle" style="text-align:center;margin-bottom:22px;">أدخل بريدك الإلكتروني وسنرسل لك رمز تحقق من 6 أرقام.</p>

        @if (session('success'))
            <div class="alert success" style="display:block;">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert error" style="display:block;">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('user.password.send-code') }}">
            @csrf
            <div class="form-group">
                <label for="email">البريد الإلكتروني</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email">
            </div>
            <button class="btn btn-primary" style="width:100%" type="submit">إرسال الرمز</button>
        </form>

        <p style="text-align:center;margin:18px 0 0;">
            <a style="color:#2563eb;font-weight:700" href="{{ route('user.login') }}">العودة لتسجيل الدخول</a>
        </p>
    </div>
</div>
@endsection
