@extends('user.layout')
@section('title', 'لوحة المستخدم')
@section('content')
<main class="container">
    <div class="page-head">
        <div><h1 id="welcome">مرحبًا 👋</h1><p class="subtitle">ملخص سريع لحسابك والعقارات.</p></div>
        <a class="btn btn-primary" href="{{ route('user.properties.create') }}">+ إضافة عقار</a>
    </div>
    <div id="alert" class="alert"></div>
    <div class="grid grid-4 stats">
        <div class="card"><span class="muted">العقارات المعتمدة</span><strong id="allCount">—</strong></div>
        <div class="card"><span class="muted">عقاراتي</span><strong id="myCount">—</strong></div>
        <div class="card"><span class="muted">المفضلة</span><strong id="favCount">—</strong></div>
        <div class="card"><span class="muted">المحادثات</span><strong id="chatCount">—</strong></div>
    </div>
    <div class="card" style="margin-top:18px;">
        <h2 class="section-title">بيانات الحساب</h2>
        <div id="profile" class="loading">جارٍ التحميل...</div>
    </div>
</main>
@endsection

@push('scripts')
<script>
    async function loadDashboard() {
        if (!requireAuth()) return;

        try {
            const userData = await api('/user');
            localStorage.setItem('auth_user', JSON.stringify(userData));

            document.getElementById('welcome').textContent = `مرحبًا، ${userData.name} 👋`;
            document.getElementById('profile').innerHTML = `
                <div class="grid grid-3">
                    <div><strong>الاسم</strong><p>${esc(userData.name)}</p></div>
                    <div><strong>البريد</strong><p>${esc(userData.email)}</p></div>
                    <div><strong>نوع الحساب</strong><p>${esc(userData.role || 'user')}</p></div>
                </div>`;

            const [all, mine, favorites, chats] = await Promise.all([
                api('/all_property'),
                api('/my-properties'),
                api('/favorites'),
                api('/show_chats'),
            ]);

            document.getElementById('allCount').textContent = all.data?.length || 0;
            document.getElementById('myCount').textContent = mine.data?.length || 0;
            document.getElementById('favCount').textContent = favorites.data?.length || 0;
            document.getElementById('chatCount').textContent = chats.data?.length || 0;
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    document.addEventListener('realtime:notification', loadDashboard);
    loadDashboard();
</script>
@endpush
