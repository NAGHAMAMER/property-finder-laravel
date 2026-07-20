<?php $__env->startSection('title', 'الإشعارات'); ?>

<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head">
        <div>
            <h1>الإشعارات</h1>
            <p class="subtitle">
                إشعارات قبول أو رفض العقار، والعقارات المطابقة لبحثك، والرسائل والتنبيهات الأخرى.
            </p>
        </div>
    </div>

    <div id="alert" class="alert"></div>

    <div id="notificationsList" class="grid">
        <div class="loading">جارٍ التحميل...</div>
    </div>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const notificationsList = document.getElementById('notificationsList');

    function normalizedNotificationData(notification) {
        let data = notification?.data ?? {};

        if (typeof data === 'string') {
            try {
                data = JSON.parse(data);
            } catch (_) {
                data = {};
            }
        }

        return data && typeof data === 'object' ? data : {};
    }

    function propertyIdOf(notification) {
        const data = normalizedNotificationData(notification);
        const directValue = notification?.property_id
            ?? data.property_id
            ?? data.propertyId
            ?? data.property?.id;

        if (directValue !== null && directValue !== undefined && directValue !== '') {
            const id = Number(directValue);
            if (Number.isInteger(id) && id > 0) {
                return id;
            }
        }

        // دعم الإشعارات القديمة التي كان رقم العقار موجودًا داخل النص فقط.
        const text = [
            notification?.title,
            notification?.message,
            data.title,
            data.message,
            data.body,
            data.content,
        ].filter(value => typeof value === 'string').join(' ');

        const match = text.match(/(?:العقار|عقار)\s*(?:رقم|#)?\s*(\d+)/u);
        return match ? Number(match[1]) : null;
    }

    function senderIdOf(notification) {
        const data = normalizedNotificationData(notification);
        const value = notification?.sender_id
            ?? data.sender_id
            ?? data.senderId
            ?? data.sender?.id;

        const id = Number(value);
        return Number.isInteger(id) && id > 0 ? id : null;
    }

    function notificationTitle(notification) {
        const data = normalizedNotificationData(notification);

        return notification?.title
            || data.title
            || (
                notification?.notification_type === 'property_match'
                    ? 'عقار مطابق لبحثك'
                    : notification?.notification_type === 'property_approval'
                        ? 'تحديث حالة العقار'
                        : notification?.notification_type === 'message'
                            ? 'رسالة جديدة'
                            : 'إشعار جديد'
            );
    }

    function notificationMessage(notification) {
        const data = normalizedNotificationData(notification);

        return notification?.message
            || data.message
            || data.body
            || data.content
            || data.title
            || 'إشعار جديد';
    }

    function notificationProperty(notification) {
        const data = normalizedNotificationData(notification);
        return notification?.property || data.property || null;
    }

    function formatDate(value) {
        if (!value) return '';

        const date = new Date(value);
        if (Number.isNaN(date.getTime())) return String(value);

        return date.toLocaleString('ar-SY', {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
        });
    }

    function propertyDetailsHtml(notification) {
        const property = notificationProperty(notification);
        if (!property) return '';

        const details = [
            property.type ? `النوع: ${esc(property.type)}` : '',
            property.location ? `الموقع: ${esc(property.location)}` : '',
        ].filter(Boolean);

        if (!details.length) return '';

        return `
            <p class="muted" style="margin:10px 0 0;">
                ${details.join(' — ')}
            </p>
        `;
    }

    function notificationActions(notification) {
        const propertyId = propertyIdOf(notification);
        const senderId = senderIdOf(notification);

        const propertyUrl = notification?.property_url
            || (propertyId ? `/app/properties/${encodeURIComponent(propertyId)}` : null);

        const chatUrl = notification?.chat_url
            || (propertyId && senderId
                ? `/app/chats/${encodeURIComponent(propertyId)}/${encodeURIComponent(senderId)}`
                : null);

        const buttons = [];

        if (propertyUrl) {
            buttons.push(`
                <a class="btn btn-light btn-sm" href="${esc(propertyUrl)}">
                    استعراض العقار
                </a>
            `);
        }

        if (chatUrl) {
            buttons.push(`
                <a class="btn btn-primary btn-sm" href="${esc(chatUrl)}">
                    فتح المحادثة والرد
                </a>
            `);
        }

        return buttons.length
            ? `<div class="actions" style="margin-top:14px;flex-wrap:wrap;">${buttons.join('')}</div>`
            : '';
    }

    function renderNotifications(items) {
        const notifications = Array.isArray(items) ? items : [];

        if (!notifications.length) {
            notificationsList.innerHTML = '<div class="card empty">لا توجد إشعارات.</div>';
            return;
        }

        notificationsList.innerHTML = notifications.map((notification) => {
            const title = notificationTitle(notification);
            const message = notificationMessage(notification);
            const createdAt = formatDate(notification.created_at);
            const isRead = Boolean(notification.read_at);

            return `
                <div class="card">
                    <div class="actions" style="justify-content:space-between;align-items:flex-start;gap:14px;">
                        <div style="min-width:0;">
                            <h3 style="margin:0 0 8px;">${esc(title)}</h3>
                            <p style="margin:0;line-height:1.8;">${esc(message)}</p>
                        </div>

                        <span class="badge ${isRead ? 'badge-neutral' : 'badge-pending'}">
                            ${isRead ? 'مقروء' : 'جديد'}
                        </span>
                    </div>

                    ${propertyDetailsHtml(notification)}

                    ${createdAt ? `<p class="muted" style="margin:10px 0 0;">${esc(createdAt)}</p>` : ''}

                    ${notificationActions(notification)}
                </div>
            `;
        }).join('');
    }

    async function loadNotifications(markAsRead = false) {
        if (!requireAuth()) return;

        try {
            const response = await api(markAsRead ? '/notifications' : '/notifications/live');
            renderNotifications(response?.data || []);

            if (markAsRead) {
                refreshRealtimeCounters();
            }
        } catch (error) {
            notificationsList.innerHTML = `
                <div class="card empty">
                    تعذر تحميل الإشعارات.
                    <div style="margin-top:12px;">
                        <button class="btn btn-primary btn-sm" type="button" id="retryNotificationsBtn">
                            إعادة المحاولة
                        </button>
                    </div>
                </div>
            `;

            document.getElementById('retryNotificationsBtn')?.addEventListener('click', () => loadNotifications(false));
            showAlert('alert', error.message || 'حدث خطأ أثناء تحميل الإشعارات.', 'error');
        }
    }

    document.addEventListener('realtime:notification', () => loadNotifications(false));
    document.addEventListener('realtime:message', () => loadNotifications(false));

    loadNotifications(true);
    setInterval(() => loadNotifications(false), 5000);
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/notifications.blade.php ENDPATH**/ ?>