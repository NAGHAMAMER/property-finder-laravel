# نظام مفضلة العقارات

## التحديث

بعد استبدال المشروع شغّل:

```bash
php artisan migrate
```

سيُنشأ جدول `favorites` مع قيد يمنع تكرار العقار نفسه للمستخدم نفسه.

## مسارات API

جميع المسارات تحتاج `auth:sanctum` وBearer Token لمستخدم عادي:

```text
GET    /api/favorites
POST   /api/properties/{property}/favorite
DELETE /api/properties/{property}/favorite
```

لا يمكن للأدمن استخدام المفضلة، ولا يمكن إضافة عقار غير معتمد.
القوائم العامة والبحث وتفاصيل العقار تعيد `is_favorite` للمستخدم الحالي.

## ملفات Postman

الملفات المحدثة موجودة داخل مجلد `postman`، وفي Collection مجلد باسم:

```text
05 - مفضلة المستخدم العادي
```
