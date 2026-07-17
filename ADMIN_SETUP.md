# إعداد نظام موافقة الأدمن على العقارات

## التشغيل بعد تنزيل المشروع

```bash
composer install
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve
```

ثم افتح:

```text
http://127.0.0.1:8000/admin/login
```

بيانات الأدمن الافتراضية موجودة في ملف `.env`:

```text
ADMIN_EMAIL=admin@example.com
ADMIN_PASSWORD=Admin@123456
```

غيّر كلمة المرور قبل استخدام المشروع فعليًا، ثم شغّل:

```bash
php artisan db:seed
```

## إضافة العقار عبر API

المسار:

```text
POST /api/add-property
```

نوع الطلب: `multipart/form-data`

الحقول العقارية السابقة بقيت كما هي، وأضيف الحقل الإلزامي:

```text
documents[]
```

يسمح بملفات PDF أو صور JPG/PNG/WEBP، بحد أقصى 10 ملفات و10MB للملف الواحد.
العقار يُنشأ بحالة `pending` ولا يظهر في القائمة العامة أو البحث أو العقارات القريبة قبل موافقة الأدمن.

## حالات الموافقة

- `pending`: بانتظار مراجعة الأدمن.
- `approved`: مقبول ويظهر للمستخدمين.
- `rejected`: مرفوض، ويصل سبب الرفض لصاحب العقار ضمن الإشعارات.

## التقييمات

```text
GET    /api/properties/{property}/ratings
POST   /api/properties/{property}/ratings
DELETE /api/properties/{property}/ratings
```

طلب الإضافة أو التعديل:

```json
{
  "rating": 5,
  "comment": "عقار ممتاز"
}
```

يمكن لكل مستخدم وضع تقييم واحد من 1 إلى 5 وتعديله لاحقًا. لا يستطيع صاحب العقار تقييم عقاره.

## حماية الوثائق

الوثائق محفوظة على القرص الخاص `local` داخل `storage/app/private`، ولا تظهر عبر `public/storage`.
تنزيل الوثيقة يمر دائمًا عبر مسار محمي ويتحقق من أن الحساب أدمن أو صاحب العقار.

## مفضلة المستخدم العادي

المفضلة متاحة للحسابات العادية فقط، ولا يمكن إضافة عقار قبل موافقة الأدمن عليه.

```text
GET    /api/favorites
POST   /api/properties/{property}/favorite
DELETE /api/properties/{property}/favorite
```

- `POST` يضيف العقار، وإذا كان موجودًا مسبقًا لا ينشئ سجلًا مكررًا.
- `GET /api/favorites` يعرض مفضلة المستخدم الحالي بترتيب الأحدث.
- `DELETE` يزيل العقار من مفضلة المستخدم الحالي فقط.
- القوائم العامة والبحث وتفاصيل العقار تعيد الحقل `is_favorite` بحسب صاحب الـBearer Token.
- حذف العقار نهائيًا يحذف سجلات المفضلة المرتبطة به تلقائيًا.
