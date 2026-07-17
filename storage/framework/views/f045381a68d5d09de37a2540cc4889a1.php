<?php $__env->startSection('title', 'الإشعارات'); ?>
<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head">
        <div>
            <h1>الإشعارات</h1>
            <p class="subtitle">إشعارات قبول أو رفض العقار والرسائل والتنبيهات الأخرى.</p>
        </div>
    </div>
    <div id="alert" class="alert"></div>
    <div id="notificationsList" class="grid"><div class="loading">جارٍ التحميل...</div></div>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    const notificationsList = document.getElementById('notificationsList');

    function notificationText(notification) {
        const data = notification.data || notification || {};
        return data.message || data.title || data.body || data.content || 'إشعار جديد';
    }

    function renderNotifications(items) {
        notificationsList.innerHTML = items?.length
            ? items.map((notification) => {
                const notificationData = notification.data || notification || {};
                const chatLink = notificationData.property_id && notificationData.sender_id
                    ? `/app/chats/${notificationData.property_id}/${notificationData.sender_id}`
                    : null;

                return `
                    <div class="card">
                        <div class="actions" style="justify-content:space-between">
                            <strong>${esc(notificationText(notification))}</strong>
                            <span class="badge ${notification.read_at ? 'badge-neutral' : 'badge-pending'}">${notification.read_at ? 'مقروء' : 'جديد'}</span>
                        </div>
                        <p class="muted">${esc(notification.created_at || '')}</p>
                        ${chatLink ? `<a class="btn btn-primary btn-sm" href="${chatLink}">فتح محادثة العقار والرد</a>` : ''}
                    </div>`;
            }).join('')
            : '<div class="card empty">لا توجد إشعارات.</div>';
    }

    async function loadNotifications(markAsRead = false) {
        if (!requireAuth()) return;

        try {
            const data = await api(markAsRead ? '/notifications' : '/notifications/live');
            renderNotifications(data.data || []);
            if (markAsRead) refreshRealtimeCounters();
        } catch (error) {
            showAlert('alert', error.message, 'error');
        }
    }

    document.addEventListener('realtime:notification', () => loadNotifications(false));
    document.addEventListener('realtime:message', () => loadNotifications(false));

    loadNotifications(true);
    setInterval(() => loadNotifications(false), 5000);
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/notifications.blade.php ENDPATH**/ ?>