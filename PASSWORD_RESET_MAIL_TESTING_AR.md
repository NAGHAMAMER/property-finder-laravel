# اختبار استعادة كلمة المرور عبر البريد

## 1) المسارات المتاحة للمستخدم العادي

### Web
- `GET /forgot-password`
- `POST /forgot-password`
- `GET /reset-password`
- `POST /reset-password`

### API
- `POST /api/forgot-password/send-code`
- `POST /api/forgot-password/reset`

طلب إرسال الكود عبر API:

```json
{
  "email": "real-user@example.com"
}
```

طلب تغيير كلمة المرور عبر API:

```json
{
  "email": "real-user@example.com",
  "code": "123456",
  "password": "NewPassword123",
  "password_confirmation": "NewPassword123"
}
```

## 2) Mailtrap Sandbox للاختبار

Mailtrap Sandbox لا يرسل الرسالة إلى البريد الحقيقي. هو يلتقط الرسالة داخل صندوق Mailtrap حتى تختبر شكلها ومحتواها بدون إرسال حقيقي.

انسخ بيانات SMTP من Inbox > Integration في حساب Mailtrap وضعها في `.env`:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=sandbox.smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=PUT_MAILTRAP_USERNAME_HERE
MAIL_PASSWORD=PUT_MAILTRAP_PASSWORD_HERE
MAIL_FROM_ADDRESS="noreply@property-finder.test"
MAIL_FROM_NAME="Property Finder"
```

ثم نفّذ:

```bash
php artisan optimize:clear
```

## 3) Gmail لإرسال حقيقي إلى بريد حقيقي

فعّل التحقق بخطوتين في حساب Google، ثم أنشئ App Password واستخدمه بدل كلمة مرور Gmail العادية.

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-sender@gmail.com
MAIL_PASSWORD=YOUR_16_CHARACTER_APP_PASSWORD
MAIL_FROM_ADDRESS=your-sender@gmail.com
MAIL_FROM_NAME="Property Finder"
```

ثم نفّذ:

```bash
php artisan optimize:clear
```

بعدها أنشئ مستخدمًا ببريد حقيقي، ثم افتح `/forgot-password` وأرسل الرمز. يفترض أن يصل إلى البريد الحقيقي لذلك المستخدم.

## 4) اختبار سريع من Artisan Tinker

```bash
php artisan tinker
```

ثم:

```php
Mail::raw('Laravel mail test', function ($message) {
    $message->to('your-real-email@example.com')->subject('Property Finder Test');
});
```

إذا لم يصل البريد، راجع:

```bash
php artisan optimize:clear
```

ثم تأكد من القيم الفعلية:

```bash
php artisan tinker
```

```php
config('mail.default');
config('mail.mailers.smtp.host');
config('mail.mailers.smtp.port');
```
