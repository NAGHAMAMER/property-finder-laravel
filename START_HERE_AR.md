# ابدئي من هنا

هذه النسخة تستخدم MySQL، وتحتوي واجهات Blade وAPI متطابقين لوظائف المستخدم العادي. الأدمن يعمل من الويب فقط.

## 1. أنشئي قاعدة MySQL

```sql
CREATE DATABASE property_finder CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## 2. راجعي `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=property_finder
DB_USERNAME=root
DB_PASSWORD=
```

## 3. شغلي الأوامر

```powershell
composer install
php artisan optimize:clear
php artisan migrate
php artisan db:seed
php artisan storage:link
php artisan serve --port=8000
```

المستخدم: `http://127.0.0.1:8000/login`

الأدمن: `http://127.0.0.1:8000/admin/login`

حساب الأدمن الافتراضي: `admin@example.com` / `Admin@123456`

## 4. Postman

استوردي:

`postman/Property-Finder-Normal-User.postman_collection.json`

ثم اتبعي `postman/README-AR.md`.
