<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'عقاري')</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <style>
        * { box-sizing: border-box; }
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --secondary: #0f172a;
            --muted: #64748b;
            --bg: #f8fafc;
            --card: #ffffff;
            --border: #e2e8f0;
            --success: #15803d;
            --danger: #dc2626;
            --warning: #d97706;
        }
        body { margin: 0; font-family: Tahoma, Arial, sans-serif; background: var(--bg); color: #0f172a; }
        a { color: inherit; text-decoration: none; }
        button, input, select, textarea { font: inherit; }
        .topbar { position: sticky; top: 0; z-index: 50; background: #fff; border-bottom: 1px solid var(--border); box-shadow: 0 4px 18px rgba(15,23,42,.05); }
        .nav { max-width: 1220px; margin: auto; padding: 12px 20px; display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
        .brand { font-size: 22px; font-weight: 800; color: var(--primary); margin-left: 10px; }
        .nav-links { display: flex; align-items: center; gap: 8px; flex: 1; flex-wrap: wrap; }
        .nav-link { padding: 9px 11px; border-radius: 10px; color: #334155; font-size: 14px; }
        .nav-link:hover, .nav-link.active { background: #eff6ff; color: var(--primary); }
        .container { max-width: 1220px; margin: 28px auto; padding: 0 20px 45px; }
        .page-head { display: flex; align-items: center; justify-content: space-between; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; }
        .page-head h1 { margin: 0; font-size: 28px; }
        .subtitle { color: var(--muted); margin: 6px 0 0; }
        .card { background: var(--card); border: 1px solid var(--border); border-radius: 16px; padding: 18px; box-shadow: 0 8px 28px rgba(15,23,42,.05); }
        .grid { display: grid; gap: 18px; }
        .grid-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .grid-3 { grid-template-columns: repeat(3, minmax(0, 1fr)); }
        .grid-4 { grid-template-columns: repeat(4, minmax(0, 1fr)); }
        .stats .card strong { display: block; font-size: 30px; margin-top: 8px; }
        .muted { color: var(--muted); }
        .btn { display: inline-flex; align-items: center; justify-content: center; gap: 7px; border: 0; border-radius: 10px; padding: 10px 14px; cursor: pointer; font-weight: 700; transition: .15s; }
        .btn:hover { transform: translateY(-1px); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn-light { background: #f1f5f9; color: #334155; }
        .btn-danger { background: #fee2e2; color: #991b1b; }
        .btn-success { background: #dcfce7; color: #166534; }
        .btn-warning { background: #fef3c7; color: #92400e; }
        .btn-sm { padding: 7px 10px; font-size: 13px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; font-weight: 700; margin-bottom: 7px; color: #334155; }
        input, select, textarea { width: 100%; border: 1px solid #cbd5e1; background: #fff; border-radius: 10px; padding: 11px 12px; outline: none; }
        input:focus, select:focus, textarea:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(37,99,235,.12); }
        textarea { min-height: 105px; resize: vertical; }
        .actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }
        .alert { border-radius: 12px; padding: 12px 14px; margin-bottom: 16px; display: none; }
        .alert.show { display: block; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #86efac; }
        .alert-error { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
        .badge { display: inline-flex; align-items: center; padding: 5px 9px; border-radius: 999px; font-size: 12px; font-weight: 800; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        .badge-rejected { background: #fee2e2; color: #991b1b; }
        .badge-neutral { background: #e2e8f0; color: #334155; }
        .property-card { overflow: hidden; padding: 0; }
        .property-image { width: 100%; height: 190px; object-fit: cover; background: #e2e8f0; display: block; }
        .property-placeholder { height: 190px; display: grid; place-items: center; background: linear-gradient(135deg,#e0e7ff,#f1f5f9); font-size: 45px; }
        .property-body { padding: 16px; }
        .property-title { font-size: 18px; margin: 0 0 7px; }
        .price { color: var(--primary); font-size: 21px; font-weight: 800; }
        .meta { display: flex; gap: 12px; flex-wrap: wrap; color: #475569; margin: 10px 0; font-size: 14px; }
        .empty { text-align: center; padding: 45px 20px; color: var(--muted); }
        .loading { text-align: center; padding: 35px; color: var(--muted); }
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 12px; border-bottom: 1px solid var(--border); text-align: right; vertical-align: top; }
        th { color: #475569; background: #f8fafc; }
        .auth-shell { min-height: 100vh; display: grid; place-items: center; padding: 20px; background: linear-gradient(135deg,#eff6ff,#f8fafc 55%,#eef2ff); }
        .auth-card { width: 100%; max-width: 480px; }
        .auth-card h1 { text-align: center; margin-top: 0; }
        .gallery { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 10px; }
        .gallery-item { position: relative; }
        .gallery img { width: 100%; height: 180px; object-fit: cover; border-radius: 12px; }
        .gallery .delete-image { position: absolute; top: 8px; left: 8px; }
        .stars { color: #f59e0b; letter-spacing: 2px; }
        .message-list { display: flex; flex-direction: column; gap: 10px; }
        .message { max-width: 72%; padding: 10px 13px; border-radius: 14px; background: #e2e8f0; }
        .message.me { align-self: flex-start; background: #dbeafe; }
        .message.other { align-self: flex-end; background: #fff; border: 1px solid var(--border); }
        .section-title { margin: 0 0 14px; font-size: 20px; }
        .split { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; align-items: start; }
        .pill { background:#eef2ff; color:#3730a3; padding:6px 10px; border-radius:999px; font-size:13px; }
        .hr { height:1px; background:var(--border); margin:18px 0; }
        .hidden { display:none !important; }
        .map-box { width:100%; min-height:360px; border:1px solid var(--border); border-radius:14px; overflow:hidden; background:#e2e8f0; }
        .map-help { margin:8px 0 0; color:var(--muted); font-size:13px; }
        .nav-badge { display:none; min-width:20px; height:20px; padding:0 6px; align-items:center; justify-content:center; border-radius:999px; background:#dc2626; color:#fff; font-size:11px; font-weight:800; margin-right:4px; }
        .nav-badge.show { display:inline-flex; }
        .realtime-toasts { position:fixed; top:82px; left:20px; z-index:9999; width:min(360px,calc(100vw - 40px)); display:grid; gap:10px; }
        .realtime-toast { background:#fff; border:1px solid var(--border); border-right:4px solid var(--primary); border-radius:12px; padding:13px 14px; box-shadow:0 16px 45px rgba(15,23,42,.18); animation:toastIn .2s ease-out; }
        .realtime-toast strong { display:block; margin-bottom:4px; }
        @keyframes toastIn { from { opacity:0; transform:translateY(-8px); } to { opacity:1; transform:translateY(0); } }
        @media (max-width: 900px) {
            .grid-4, .grid-3 { grid-template-columns: repeat(2,minmax(0,1fr)); }
            .split { grid-template-columns: 1fr; }
        }
        @media (max-width: 620px) {
            .grid-4, .grid-3, .grid-2 { grid-template-columns: 1fr; }
            .container { padding: 0 12px 30px; }
            .nav { padding: 10px 12px; }
            .nav-links { order: 3; width: 100%; overflow-x: auto; flex-wrap: nowrap; }
            .gallery { grid-template-columns: 1fr 1fr; }
        }
    </style>
    @stack('styles')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script src="https://js.pusher.com/8.4.0/pusher.min.js" defer></script>
</head>
<body>
@php
    $hideUserNavbar = request()->routeIs('user.login', 'user.register', 'user.password.*')
        || request()->is('login', 'register', 'forgot-password', 'reset-password');
@endphp
@if(! $hideUserNavbar)
<header class="topbar">
    <nav class="nav">
        <a class="brand" href="{{ route('user.dashboard') }}">🏠 عقاري</a>
        <div class="nav-links">
            <a class="nav-link {{ request()->routeIs('user.dashboard') ? 'active' : '' }}" href="{{ route('user.dashboard') }}">الرئيسية</a>
            <a class="nav-link {{ request()->routeIs('user.properties.*') && !request()->routeIs('user.properties.my') ? 'active' : '' }}" href="{{ route('user.properties.index') }}">العقارات</a>
            <a class="nav-link {{ request()->routeIs('user.properties.my') ? 'active' : '' }}" href="{{ route('user.properties.my') }}">عقاراتي</a>
            <a class="nav-link {{ request()->routeIs('user.search') ? 'active' : '' }}" href="{{ route('user.search') }}">البحث</a>
            <a class="nav-link {{ request()->routeIs('user.nearby') ? 'active' : '' }}" href="{{ route('user.nearby') }}">القريبة</a>
            <a class="nav-link {{ request()->routeIs('user.favorites') ? 'active' : '' }}" href="{{ route('user.favorites') }}">المفضلة</a>
            <a class="nav-link {{ request()->routeIs('user.chats.*') ? 'active' : '' }}" href="{{ route('user.chats.index') }}">المحادثات <span id="messageBadge" class="nav-badge">0</span></a>
            <a class="nav-link {{ request()->routeIs('user.notifications') ? 'active' : '' }}" href="{{ route('user.notifications') }}">الإشعارات <span id="notificationBadge" class="nav-badge">0</span></a>
            <a class="nav-link {{ request()->routeIs('user.account') ? 'active' : '' }}" href="{{ route('user.account') }}">الحساب</a>
        </div>
        <button class="btn btn-light btn-sm" onclick="logoutUser()">تسجيل الخروج</button>
    </nav>
</header>
@endif
<div id="realtimeToasts" class="realtime-toasts" aria-live="polite"></div>

@yield('content')

<script>
    const API_BASE = '/api';
    const REALTIME_CONFIG = {
        broadcaster: @json(config('broadcasting.default')),
        key: @json(config('broadcasting.connections.pusher.key')),
        cluster: @json(config('broadcasting.connections.pusher.options.cluster')),
    };
    let realtimeClient = null;
    let realtimeChannel = null;
    let realtimePollTimer = null;

    function token() { return localStorage.getItem('auth_token') || ''; }
    function savedUser() {
        try { return JSON.parse(localStorage.getItem('auth_user') || 'null'); }
        catch (_) { return null; }
    }
    function saveAuth(data) {
        if (data.token) localStorage.setItem('auth_token', data.token);
        if (data.user) localStorage.setItem('auth_user', JSON.stringify(data.user));
    }
    function clearAuth() {
        localStorage.removeItem('auth_token');
        localStorage.removeItem('auth_user');
    }
    function authHeaders(extra = {}) {
        const headers = { 'Accept': 'application/json', ...extra };
        if (token()) headers['Authorization'] = `Bearer ${token()}`;
        return headers;
    }
    async function api(path, options = {}) {
        const requestOptions = { ...options };
        const isForm = requestOptions.body instanceof FormData;
        requestOptions.headers = authHeaders(requestOptions.headers || {});
        if (requestOptions.body && !isForm && typeof requestOptions.body !== 'string') {
            requestOptions.headers['Content-Type'] = 'application/json';
            requestOptions.body = JSON.stringify(requestOptions.body);
        }

        const controller = new AbortController();
        const timeoutId = window.setTimeout(() => controller.abort(), 15000);
        if (!requestOptions.signal) requestOptions.signal = controller.signal;

        let response;
        try {
            response = await fetch(API_BASE + path, requestOptions);
        } catch (error) {
            if (error?.name === 'AbortError') {
                throw new Error('انتهت مهلة الاتصال بالخادم. تحقق من تشغيل Laravel ثم أعد المحاولة.');
            }
            throw new Error('تعذر الاتصال بالخادم. تحقق من الرابط والبورت واتصال الشبكة.');
        } finally {
            window.clearTimeout(timeoutId);
        }

        let data;
        try { data = await response.json(); } catch (_) { data = {}; }
        if (response.status === 401) {
            clearAuth();
            if (!location.pathname.endsWith('/login')) location.href = '{{ route('user.login') }}';
        }
        if (!response.ok) {
            const validation = data.errors ? Object.values(data.errors).flat().join('\n') : '';
            throw new Error(validation || data.message || `حدث خطأ (${response.status})`);
        }
        return data;
    }
    async function downloadProtected(path, filename = 'document') {
        const response = await fetch(API_BASE + path, { headers: authHeaders() });
        if (!response.ok) {
            let message = 'تعذر تنزيل الملف';
            try { const data = await response.json(); message = data.message || message; } catch (_) {}
            throw new Error(message);
        }
        const blob = await response.blob();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url; a.download = filename; document.body.appendChild(a); a.click(); a.remove();
        URL.revokeObjectURL(url);
    }
    function requireAuth() {
        if (!token()) {
            location.href = '{{ route('user.login') }}';
            return false;
        }
        return true;
    }
    async function logoutUser() {
        try { await api('/logout', { method: 'POST' }); } catch (_) {}
        clearAuth();
        location.href = '{{ route('user.login') }}';
    }
    function esc(value) {
        return String(value ?? '').replace(/[&<>'"]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
    }
    function money(value) { return new Intl.NumberFormat('ar', { maximumFractionDigits: 0 }).format(Number(value || 0)); }
    function stars(value) {
        const n = Math.max(0, Math.min(5, Math.round(Number(value || 0))));
        return '★'.repeat(n) + '☆'.repeat(5 - n);
    }
    function approvalBadge(status) {
        const map = { approved: ['تمت الموافقة','badge-approved'], pending: ['قيد المراجعة','badge-pending'], rejected: ['مرفوض','badge-rejected'] };
        const item = map[status] || [status || 'غير محدد','badge-neutral'];
        return `<span class="badge ${item[1]}">${esc(item[0])}</span>`;
    }
    function showAlert(elementId, message, type = 'success') {
        const el = document.getElementById(elementId);
        if (!el) return;
        el.className = `alert show ${type === 'error' ? 'alert-error' : 'alert-success'}`;
        el.textContent = message;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    function firstImage(property) {
        const image = property?.images?.[0]?.image_path;
        return image ? `/storage/${image}` : null;
    }
    function createLocationMap(options) {
        if (typeof window.L === 'undefined') {
            throw new Error('تعذر تحميل مكتبة الخريطة. تحقق من اتصال الإنترنت.');
        }

        const mapElement = document.getElementById(options.mapId);
        if (!mapElement) return null;

        const latInput = options.latInputId ? document.getElementById(options.latInputId) : null;
        const lngInput = options.lngInputId ? document.getElementById(options.lngInputId) : null;
        const parsedLat = Number(options.latitude ?? latInput?.value);
        const parsedLng = Number(options.longitude ?? lngInput?.value);
        const hasInitialPoint = Number.isFinite(parsedLat) && Number.isFinite(parsedLng)
            && parsedLat >= -90 && parsedLat <= 90 && parsedLng >= -180 && parsedLng <= 180;

        const initialPoint = hasInitialPoint ? [parsedLat, parsedLng] : [25, 15];
        const map = L.map(mapElement).setView(initialPoint, hasInitialPoint ? 15 : 2);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        let marker = hasInitialPoint ? L.marker(initialPoint).addTo(map) : null;

        function setPoint(latitude, longitude, center = true) {
            const lat = Number(latitude);
            const lng = Number(longitude);
            if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

            if (latInput) latInput.value = lat.toFixed(7);
            if (lngInput) lngInput.value = lng.toFixed(7);

            if (marker) marker.setLatLng([lat, lng]);
            else marker = L.marker([lat, lng]).addTo(map);

            if (center) map.setView([lat, lng], Math.max(map.getZoom(), 15));
        }

        if (!options.readOnly) {
            map.on('click', (event) => setPoint(event.latlng.lat, event.latlng.lng, false));
        }

        return {
            map,
            setPoint,
            locateCurrent() {
                if (!navigator.geolocation) {
                    return Promise.reject(new Error('المتصفح لا يدعم تحديد الموقع الحالي.'));
                }

                return new Promise((resolve, reject) => {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            setPoint(lat, lng, true);
                            resolve({ latitude: lat, longitude: lng });
                        },
                        (error) => reject(new Error(error.message || 'تعذر تحديد الموقع الحالي.')),
                        { enableHighAccuracy: true, timeout: 10000 }
                    );
                });
            },
        };
    }

    function updateNavBadge(id, count) {
        const el = document.getElementById(id);
        if (!el) return;
        const n = Math.max(0, Number(count || 0));
        el.textContent = n > 99 ? '99+' : String(n);
        el.classList.toggle('show', n > 0);
    }

    function showRealtimeToast(title, message) {
        const container = document.getElementById('realtimeToasts');
        if (!container) return;
        const toast = document.createElement('div');
        toast.className = 'realtime-toast';
        toast.innerHTML = `<strong>${esc(title || 'إشعار جديد')}</strong><div>${esc(message || '')}</div>`;
        container.prepend(toast);
        setTimeout(() => toast.remove(), 5500);
    }

    async function refreshRealtimeCounters() {
        if (!token()) return;
        try {
            const [notificationsData, messagesData] = await Promise.all([
                api('/notifications/live'),
                api('/messages/unread-count'),
            ]);
            updateNavBadge('notificationBadge', notificationsData.unread_count || 0);
            updateNavBadge('messageBadge', messagesData.data?.unread_count || 0);
        } catch (_) {
            // Background counter refresh must never interrupt the current page.
        }
    }

    function handleRealtimeNotification(payload) {
        const notification = payload?.notification || payload || {};
        const title = notification.title || 'إشعار جديد';
        const message = notification.message || notification.content || notification.location || '';
        showRealtimeToast(title, message);
        document.dispatchEvent(new CustomEvent('realtime:notification', { detail: notification }));
        refreshRealtimeCounters();
    }

    function handleRealtimeMessage(payload) {
        const message = payload?.message || payload || {};
        showRealtimeToast('رسالة جديدة', message.content || 'وصلتك رسالة جديدة.');
        document.dispatchEvent(new CustomEvent('realtime:message', { detail: message }));
        refreshRealtimeCounters();
    }

    function startRealtimeFallbackPolling() {
        if (realtimePollTimer || !token()) return;
        refreshRealtimeCounters();
        realtimePollTimer = setInterval(refreshRealtimeCounters, 5000);
    }

    function initializeRealtime() {
        if (!token()) return;

        const user = savedUser();
        startRealtimeFallbackPolling();
        if (!user?.id || REALTIME_CONFIG.broadcaster !== 'pusher' || !REALTIME_CONFIG.key || typeof window.Pusher === 'undefined') {
            return;
        }

        try {
            realtimeClient = new window.Pusher(REALTIME_CONFIG.key, {
                cluster: REALTIME_CONFIG.cluster || 'mt1',
                forceTLS: true,
                channelAuthorization: {
                    endpoint: '/broadcasting/auth',
                    transport: 'ajax',
                    headers: {
                        'Accept': 'application/json',
                        'Authorization': `Bearer ${token()}`,
                    },
                },
            });

            realtimeChannel = realtimeClient.subscribe(`private-App.Models.User.${user.id}`);
            realtimeChannel.bind('notification.created', handleRealtimeNotification);
            realtimeChannel.bind('message.sent', handleRealtimeMessage);
            realtimeChannel.bind('pusher:subscription_error', () => startRealtimeFallbackPolling());
        } catch (_) {
            startRealtimeFallbackPolling();
        }
    }

    document.addEventListener('DOMContentLoaded', initializeRealtime);

    function propertyCard(property) {
        const image = firstImage(property);
        const favorite = Boolean(property.is_favorite);
        return `
            <article class="card property-card">
                ${image ? `<img class="property-image" src="${esc(image)}" alt="صورة العقار">` : '<div class="property-placeholder">🏡</div>'}
                <div class="property-body">
                    <div class="actions" style="justify-content:space-between;">
                        <h3 class="property-title">${esc(property.type)} في ${esc(property.location)}</h3>
                        ${property.approval_status ? approvalBadge(property.approval_status) : ''}
                    </div>
                    <div class="price">${money(property.price)} $</div>
                    <div class="meta">
                        <span>📐 ${esc(property.area)} م²</span>
                        <span>🛏 ${esc(property.badroom || 0)}</span>
                        <span>🚿 ${esc(property.bathroom || 0)}</span>
                    </div>
                    <div class="meta">
                        <span class="stars">${stars(property.ratings_avg_rating)}</span>
                        <span>(${esc(property.ratings_count || 0)} تقييم)</span>
                    </div>
                    <div class="actions">
                        <a class="btn btn-primary btn-sm" href="/app/properties/${property.id}">التفاصيل</a>
                        ${property.approval_status === 'approved' ? `<button class="btn btn-light btn-sm" onclick="event.preventDefault();event.stopPropagation();toggleFavorite(${property.id}, this, ${favorite})">${favorite ? '♥ إزالة من المفضلة' : '♡ إضافة للمفضلة'}</button>` : ''}
                    </div>
                </div>
            </article>`;
    }
    async function toggleFavorite(id, button, currentlyFavorite) {
        try {
            button.disabled = true;
            await api(`/properties/${id}/favorite`, { method: currentlyFavorite ? 'DELETE' : 'POST' });
            button.textContent = currentlyFavorite ? '♡ إضافة للمفضلة' : '♥ إزالة من المفضلة';
            button.setAttribute('onclick', `event.preventDefault();event.stopPropagation();toggleFavorite(${id}, this, ${!currentlyFavorite})`);
            document.dispatchEvent(new CustomEvent('favorite:changed', { detail: { id, isFavorite: !currentlyFavorite } }));
        } catch (e) { alert(e.message); }
        finally { button.disabled = false; }
    }
</script>
@stack('scripts')
</body>
</html>
