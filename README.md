# BidAdmin

نسخة مصغّرة من BidManager.io لإدارة حسابات مزادات السيارات.

**الوضع الحالي:** قاعدة واحدة مشتركة (`bidadmin`) — السوبر أدمن يرى كل البيانات. مسار `/{tenant}` وكود قواعد منفصلة جاهز لاحقاً عبر `TENANCY_SEPARATE_DATABASES=true`.

**Stack:** Laravel 12 · PHP 8.2+ · MySQL (XAMPP) · Inertia 2 · React 19 · TypeScript · Vite · Tailwind CSS 4 · shadcn/ui · Sanctum · DomPDF.

---

## العربية — التشغيل على XAMPP

### المتطلبات
- XAMPP مع **PHP 8.2+** و MySQL
- Composer 2 · Node.js 18+

### الخطوات
1. شغّل **Apache** و **MySQL** من XAMPP.
2. من phpMyAdmin أنشئ قاعدة اسمها `bidadmin` (utf8mb4_unicode_ci).
3. من مجلد المشروع:
   ```bat
   cd C:\xampp\htdocs\BidAdmin\BidAdmin
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm install
   npm run build
   php artisan serve
   ```

### الروابط
| الصفحة | الرابط |
|---|---|
| سوبر أدمن | https://bidadmin.test/super/login |
| دخول النظام (ERP) | من لوحة السوبر → **دخول النظام** → `/demo/dashboard` |
| مستأجر demo | https://bidadmin.test/demo/login |
| الجذر `/` | يحوّل إلى `/super/login` |

### بيانات الدخول
| الدور | الإيميل | كلمة السر |
|---|---|---|
| Super Admin (يرى كل شيء) | `superadmin@bidadmin.test` | `password` |
| Admin | `admin@bidadmin.test` | `password` |
| Customer | `customer@bidadmin.test` | `password` |

### وضع القواعد المنفصلة لاحقاً
في `.env` ضع `TENANCY_SEPARATE_DATABASES=true` — عندها كل مستأجر يحصل على `bidadmin_{slug}`.

### أوامر المستأجرين
```bat
php artisan tenants:create acme admin@acme.test "password" --name="Acme Bids"
php artisan tenants:migrate
php artisan tenants:import-legacy legacy-slug --name="Legacy"
```

### API (لكل مستأجر)
```bash
curl -X POST http://127.0.0.1:8000/demo/api/bids \
  -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{"account_id":1,"lot_number":"46121395","amount":5000,"type":"prebid"}'
```

### الاختبارات
تحتاج MySQL. أنشئ قاعدة `bidadmin_testing` ثم:
```bat
php artisan test
```

---

## English — XAMPP setup

1. Start Apache + MySQL. Create central DB `bidadmin` (tenant DBs are created automatically; MySQL user needs `CREATE DATABASE`).
2. From the project folder (`BidAdmin\BidAdmin`):
   ```bat
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm install && npm run build
   php artisan serve
   ```
3. Open **http://127.0.0.1:8000/super/login** (platform) or **http://127.0.0.1:8000/demo/login** (demo tenant).

### Architecture
- **Central DB `bidadmin`:** `super_admins`, `tenants`
- **Tenant DB `bidadmin_{slug}`:** users, accounts, invoices, settings, …
- Hand-rolled tenancy via `TenantManager` (no stancl/tenancy)
- Session cookie per tenant: `bidadmin_{slug}_session`

### Demo credentials
- Super: `superadmin@bidadmin.test` / `password`
- Demo admin: `admin@bidadmin.test` / `password`
- Demo customer: `customer@bidadmin.test` / `password`
