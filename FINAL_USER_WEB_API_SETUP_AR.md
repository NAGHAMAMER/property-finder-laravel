# النسخة النهائية: Web وAPI للمستخدم العادي

## القاعدة المعتمدة

- جميع وظائف المستخدم العادي متوفرة في واجهات Blade وفي API للموبايل/Postman.
- واجهات Blade تستعمل نفس API، لذلك لا يوجد منطق منفصل أو نتائج مختلفة.
- الأدمن متوفر عبر Blade/Web فقط، ولا توجد مسارات إدارة ضمن `routes/api.php`.
- المحادثة مرتبطة دائمًا بعقار ومستخدم آخر، ولا توجد محادثة عامة بلا عقار.

## MySQL

أنشئي قاعدة:

```sql
CREATE DATABASE property_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

إعداد `.env` الافتراضي:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=property_finder
DB_USERNAME=root
DB_PASSWORD=
```

ثم:

```powershell
php artisan optimize:clear
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --port=8000
```

## صفحات الويب

- المستخدم: `http://127.0.0.1:8000/login`
- الحساب وتغيير كلمة المرور: `http://127.0.0.1:8000/app/account`
- المحادثات: `http://127.0.0.1:8000/app/chats`
- الأدمن: `http://127.0.0.1:8000/admin/login`

## الرسائل والإشعارات الفورية

الرسائل والإشعارات تُحفظ دائمًا في MySQL. الواجهة لديها تحديث احتياطي كل عدة ثوانٍ، ولذلك تستمر بالعمل حتى دون Pusher.

للوصول الفوري عبر WebSocket ضعي بيانات Pusher الحقيقية:

```env
BROADCAST_CONNECTION=pusher
PUSHER_APP_ID=...
PUSHER_APP_KEY=...
PUSHER_APP_SECRET=...
PUSHER_APP_CLUSTER=eu
```

ثم:

```powershell
php artisan optimize:clear
```

الأحداث المستخدمة:

- `message.sent`
- `notification.created`
- القناة الخاصة: `private-App.Models.User.{USER_ID}`

## إصلاح بقاء المحادثة على جارٍ التحميل

صفحة المحادثة تستعمل الآن:

```text
GET /api/chats/{property_id}/{other_user_id}
POST /api/chats/{property_id}/{other_user_id}/messages
```

كما أنها تعرض سبب الخطأ وزر إعادة المحاولة، وتوقف الطلب بعد 15 ثانية بدل البقاء على حالة التحميل إلى ما لا نهاية.
