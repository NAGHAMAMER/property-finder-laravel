<?php $__env->startSection('title', 'المحادثات'); ?>
<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head">
        <div>
            <h1>المحادثات</h1>
            <p class="subtitle">كل محادثة مرتبطة بعقار محدد، حتى لا تختلط رسائل العقارات المختلفة.</p>
        </div>
    </div>

    <div id="alert" class="alert"></div>
    <div id="chatsList" class="grid grid-2"><div class="loading">جارٍ تحميل المحادثات...</div></div>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const chatsListElement = document.getElementById('chatsList');
    let loadingChats = false;
    let pollTimer = null;

    function formatDate(value) {
        if (!value) return '';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleString('ar');
    }

    function threadCard(thread) {
        const property = thread.property || {};
        const otherUser = thread.other_user || { id: thread.id, name: thread.name };
        const propertyTitle = property.id
            ? `${property.type || 'عقار'} في ${property.location || 'موقع غير محدد'}`
            : 'عقار غير متاح';

        return `
            <article class="card" style="display:grid;gap:14px;">
                <div class="actions" style="justify-content:space-between;align-items:flex-start;">
                    <div>
                        <strong style="font-size:17px;">👤 ${esc(otherUser.name || 'مستخدم')}</strong>
                        <p class="muted" style="margin:6px 0 0;">المحادثة حول:</p>
                        <h3 style="margin:5px 0 0;font-size:18px;">🏠 ${esc(propertyTitle)}</h3>
                    </div>
                    ${Number(thread.unread_count || 0) > 0 ? `<span class="badge badge-pending">${esc(thread.unread_count)} جديد</span>` : ''}
                </div>
                <div style="padding:12px;border-radius:12px;background:#f8fafc;border:1px solid #e2e8f0;">
                    <div class="actions" style="justify-content:space-between;">
                        <span class="muted">آخر رسالة</span>
                        ${thread.last_message_at ? `<small class="muted">${esc(formatDate(thread.last_message_at))}</small>` : ''}
                    </div>
                    <p style="margin:8px 0 0;">${esc(thread.last_message || 'لا توجد رسالة')}</p>
                </div>
                <div class="actions">
                    ${property.id && otherUser.id ? `<a class="btn btn-primary" href="/app/chats/${property.id}/${otherUser.id}">فتح المحادثة والرد</a>` : ''}
                    ${property.id ? `<a class="btn btn-light" href="/app/properties/${property.id}">عرض العقار</a>` : ''}
                </div>
            </article>`;
    }

    async function loadChats() {
        if (loadingChats || !requireAuth()) return;
        loadingChats = true;
        try {
            const response = await api('/chats');
            const threads = response.data || [];
            chatsListElement.innerHTML = threads.length
                ? threads.map(threadCard).join('')
                : '<div class="card empty" style="grid-column:1/-1;">لا توجد محادثات بعد. ابدأ محادثة من صفحة تفاصيل عقار معتمد.</div>';
        } catch (error) {
            chatsListElement.innerHTML = `<div class="card empty" style="grid-column:1/-1;color:#991b1b;">${esc(error.message)}</div>`;
            showAlert('alert', error.message, 'error');
        } finally {
            loadingChats = false;
            window.clearTimeout(pollTimer);
            pollTimer = window.setTimeout(loadChats, 5000);
        }
    }

    document.addEventListener('realtime:message', loadChats);
    loadChats();
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/chats/index.blade.php ENDPATH**/ ?>