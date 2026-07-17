<?php $__env->startSection('title', 'عرض المحادثة'); ?>

<?php $__env->startSection('content'); ?>
<main class="container">
    <div class="page-head">
        <div>
            <h1 id="chatTitle">المحادثة</h1>
            <p id="chatSubtitle" class="subtitle">جارٍ تحميل معلومات العقار والمستخدم...</p>
        </div>
        <a class="btn btn-light" href="<?php echo e(route('user.chats.index')); ?>">رجوع إلى المحادثات</a>
    </div>

    <div id="alert" class="alert"></div>
    <section id="propertyCard" class="card" style="margin-bottom:18px;display:none;"></section>

    <section class="card" style="padding:0;overflow:hidden;">
        <div id="messagesList" class="message-list" style="min-height:280px;max-height:520px;overflow-y:auto;padding:18px;">
            <div class="loading">جارٍ تحميل الرسائل...</div>
        </div>

        <form id="replyForm" style="border-top:1px solid #e2e8f0;padding:16px;background:#f8fafc;">
            <label for="replyContent">اكتب ردك حول هذا العقار</label>
            <div style="display:grid;grid-template-columns:1fr auto;gap:10px;align-items:end;">
                <textarea id="replyContent" maxlength="5000" required placeholder="اكتب رسالتك..." style="min-height:75px;"></textarea>
                <button id="sendReplyButton" class="btn btn-primary" type="submit" disabled>إرسال الرد</button>
            </div>
        </form>
    </section>

    <p class="muted" style="margin-top:14px;">هذه المحادثة خاصة بالعقار الموضح أعلاه، ولا توجد محادثات عامة غير مرتبطة بعقار.</p>
</main>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
(() => {
    const chatPropertyId = <?php echo e((int) $propertyId); ?>;
    const chatOtherUserId = <?php echo e((int) $otherUserId); ?>;
    const messagesListElement = document.getElementById('messagesList');
    const propertyCardElement = document.getElementById('propertyCard');
    const chatTitleElement = document.getElementById('chatTitle');
    const chatSubtitleElement = document.getElementById('chatSubtitle');
    const replyFormElement = document.getElementById('replyForm');
    const replyContentElement = document.getElementById('replyContent');
    const sendReplyButtonElement = document.getElementById('sendReplyButton');

    let currentUserId = null;
    let pollTimer = null;
    let requestRunning = false;
    let sendingReply = false;

    function formatDate(value) {
        if (!value) return '';
        const date = new Date(value);
        return Number.isNaN(date.getTime()) ? value : date.toLocaleString('ar');
    }

    function renderPropertyInfo(property, otherUser) {
        chatTitleElement.textContent = `المحادثة مع ${otherUser?.name || 'المستخدم'}`;
        chatSubtitleElement.textContent = property
            ? `المحادثة حول ${property.type || 'عقار'} في ${property.location || 'موقع غير محدد'}`
            : 'محادثة مرتبطة بعقار.';

        if (!property) {
            propertyCardElement.style.display = 'none';
            return;
        }

        propertyCardElement.style.display = 'block';
        propertyCardElement.innerHTML = `
            <div class="actions" style="justify-content:space-between;align-items:flex-start;">
                <div>
                    <span class="muted">العقار المرتبط بالمحادثة</span>
                    <h2 style="margin:7px 0;">🏠 ${esc(property.type || 'عقار')} في ${esc(property.location || '—')}</h2>
                    <div class="actions">
                        <span class="badge badge-neutral">${esc(property.status || 'غير محدد')}</span>
                        ${property.approval_status ? approvalBadge(property.approval_status) : ''}
                        <span class="price" style="font-size:17px;">${money(property.price)} $</span>
                    </div>
                </div>
                <a class="btn btn-light" href="/app/properties/${property.id}">عرض تفاصيل العقار</a>
            </div>`;
    }

    function renderMessages(messages) {
        messagesListElement.innerHTML = messages.length
            ? messages.map((message) => `
                <div class="message ${Number(message.sender_id) === currentUserId ? 'me' : 'other'}">
                    <div>${esc(message.content)}</div>
                    <small class="muted">${esc(formatDate(message.created_at))}</small>
                </div>`).join('')
            : '<div class="empty">لا توجد رسائل في هذه المحادثة.</div>';

        messagesListElement.scrollTop = messagesListElement.scrollHeight;
    }

    function renderLoadError(message) {
        messagesListElement.innerHTML = `
            <div class="empty">
                <p style="color:#991b1b;font-weight:700;">${esc(message)}</p>
                <button id="retryConversationButton" class="btn btn-primary" type="button">إعادة المحاولة</button>
            </div>`;
        document.getElementById('retryConversationButton')?.addEventListener('click', () => loadConversation(true));
        showAlert('alert', message, 'error');
    }

    async function loadConversation(showLoading = false) {
        if (requestRunning || !requireAuth()) return;
        requestRunning = true;

        if (showLoading) {
            messagesListElement.innerHTML = '<div class="loading">جارٍ تحميل الرسائل...</div>';
        }

        try {
            if (!currentUserId) {
                const user = await api('/user');
                currentUserId = Number(user.id);
            }

            const response = await api(`/chats/${chatPropertyId}/${chatOtherUserId}`);
            const thread = response.data || {};
            renderPropertyInfo(thread.property, thread.other_user);
            renderMessages(thread.messages || []);
            sendReplyButtonElement.disabled = false;
            refreshRealtimeCounters();
        } catch (error) {
            sendReplyButtonElement.disabled = true;
            renderLoadError(error.message || 'تعذر تحميل المحادثة.');
        } finally {
            requestRunning = false;
            window.clearTimeout(pollTimer);
            pollTimer = window.setTimeout(() => loadConversation(false), 5000);
        }
    }

    replyFormElement.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (sendingReply) return;

        const content = replyContentElement.value.trim();
        if (!content) {
            showAlert('alert', 'اكتب الرسالة أولًا.', 'error');
            return;
        }

        try {
            sendingReply = true;
            sendReplyButtonElement.disabled = true;
            sendReplyButtonElement.textContent = 'جارٍ الإرسال...';

            await api(`/chats/${chatPropertyId}/${chatOtherUserId}/messages`, {
                method: 'POST',
                body: { content },
            });

            replyContentElement.value = '';
            await loadConversation(false);
        } catch (error) {
            showAlert('alert', error.message, 'error');
        } finally {
            sendingReply = false;
            sendReplyButtonElement.disabled = false;
            sendReplyButtonElement.textContent = 'إرسال الرد';
        }
    });

    document.addEventListener('realtime:message', (event) => {
        const message = event.detail || {};
        const sameProperty = Number(message.property_id) === chatPropertyId;
        const sameUsers =
            (Number(message.sender_id) === chatOtherUserId && Number(message.receiver_id) === currentUserId)
            || (Number(message.sender_id) === currentUserId && Number(message.receiver_id) === chatOtherUserId);

        if (sameProperty && sameUsers) loadConversation(false);
    });

    loadConversation(true);
})();
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('user.layout', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\Users\نغم\Downloads\property-finder-user-web-api-final\property-finder-user-web-api-final\property_finder_final\resources\views/user/chats/show.blade.php ENDPATH**/ ?>