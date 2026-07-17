# إعداد إرسال رمز استعادة كلمة المرور عبر البريد

الميزة تعمل للمستخدم العادي وللأدمن باستخدام جدول Laravel الموجود أصلًا `password_reset_tokens`، لذلك لا تحتاج Migration جديدة.

## 1) إعداد SMTP داخل `.env`

مثال باستخدام Gmail SMTP:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Property Finder"
```

استخدم App Password بدل كلمة مرور Gmail العادية.

## 2) تنظيف كاش الإعدادات

```bash
php artisan optimize:clear
```

## 3) مسارات المستخدم العادي

- `GET /forgot-password`
- `GET /reset-password`
- `POST /api/forgot-password/send-code`
- `POST /api/forgot-password/reset`

## 4) مسارات الأدمن

- `GET /admin/forgot-password`
- `POST /admin/forgot-password`
- `GET /admin/reset-password`
- `POST /admin/reset-password`

## ملاحظات الأمان

- الرمز يتكون من 6 أرقام.
- الرمز صالح لمدة 10 دقائق فقط.
- الرمز مخزن بشكل مشفر في قاعدة البيانات.
- بعد تغيير كلمة المرور بنجاح يتم حذف الرمز وإلغاء جميع Sanctum tokens القديمة لذلك الحساب.
- توجد Rate Limits على إرسال الرمز ومحاولة إعادة التعيين.
