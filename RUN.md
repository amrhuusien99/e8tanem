# تشغيل مشروع اغتنم (e8tanem) محلياً

دليل سريع لتشغيل الـ API على Windows باستخدام Docker (الطريقة الموصى بها).

## المتطلبات

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) (يعمل ويظهر في شريط المهام)
- PowerShell

> لا تحتاج تثبيت PHP أو Composer على الجهاز؛ كل شيء يعمل داخل حاوية Docker.

---

## الإعداد لأول مرة

من جذر المشروع:

```powershell
cd D:\shared\studio-projects\e8tanem
.\scripts\setup-local.ps1
```

هذا السكربت يقوم تلقائياً بـ:

1. نسخ `.env.example` إلى `.env` (إن لم يكن موجوداً)
2. إنشاء قاعدة SQLite: `database/database.sqlite`
3. تثبيت الحزم: `composer install`
4. توليد مفتاح التطبيق: `php artisan key:generate`
5. تشغيل الهجرات والبذور + بيانات تجريبية (`DevSampleSeeder`)

بعد الإعداد، عدّل `.env` إذا لزم الأمر. للتطوير المحلي يُفضّل SQLite:

```env
DB_CONNECTION=sqlite
```

(تعليق إعدادات MySQL في `.env` إن وُجدت.)

---

## تشغيل التطبيق

```powershell
cd D:\shared\studio-projects\e8tanem
docker compose up -d
```

انتظر بضع ثوانٍ ثم افتح المتصفح.

### إيقاف الخادم

```powershell
docker compose down
```

### إعادة تشغيل الحاوية (إذا علّق الطلب أو انتهت المهلة)

```powershell
docker compose restart app
```

---

## الروابط بعد التشغيل

| الوصف | الرابط |
|--------|--------|
| الصفحة الرئيسية | http://127.0.0.1:8000 |
| توثيق Swagger للـ API | http://127.0.0.1:8000/api/documentation |
| لوحة Filament (إدارة) | http://127.0.0.1:8000/admin |

> استخدم `127.0.0.1` بدلاً من `localhost` على Windows إذا واجهت بطءاً أو انتهاء مهلة الاتصال.

---

## حسابات تجريبية (بعد `migrate --seed`)

| الدور | البريد | كلمة المرور |
|------|--------|-------------|
| مستخدم عادي | `test@example.com` | `password` |
| مدير (Filament) | `admin@e8tanem.com` | `password123` |

يمكن تغيير بيانات المدير عبر `.env`:

```env
ADMIN_EMAIL=admin@e8tanem.com
ADMIN_PASSWORD=password123
```

ثم أعد تشغيل: `docker compose exec app php artisan db:seed --class=AdminSeeder --force`

---

## أوامر مفيدة

تنفيذ أوامر Artisan داخل الحاوية:

```powershell
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed --class=DevSampleSeeder --force
docker compose exec app php artisan storage:link
docker compose logs -f app
```

اختبار أن الخادم يعمل:

```powershell
Invoke-WebRequest -Uri "http://127.0.0.1:8000" -UseBasicParsing
```

---

## تشغيل بدون Docker (اختياري)

إذا كان PHP 8.2+ و Composer وامتداد `intl` مثبتين محلياً:

```powershell
composer install
copy .env.example .env
php artisan key:generate
# اضبط DB_CONNECTION=sqlite وأنشئ database\database.sqlite
php artisan migrate --seed
php artisan serve
```

ثم افتح: http://127.0.0.1:8000

---

## استكشاف الأخطاء

| المشكلة | الحل |
|---------|------|
| `docker` غير معروف | شغّل Docker Desktop وانتظر حتى يصبح جاهزاً |
| المنفذ 8000 مشغول | غيّر في `docker-compose.yml`: `"8080:8000"` ثم افتح http://127.0.0.1:8080 |
| طلبات بطيئة أو timeout | `docker compose restart app` |
| خطأ قاعدة البيانات | تأكد من `DB_CONNECTION=sqlite` ووجود `database/database.sqlite` |
| `vendor` ناقص | أعد تشغيل `.\scripts\setup-local.ps1` |

---

## توثيق الـ API الكامل

راجع أيضاً: [docs/README.md](docs/README.md)
