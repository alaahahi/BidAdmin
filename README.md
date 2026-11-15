# BidAdmin

نسخة مصغّرة من BidManager.io لإدارة حسابات مزادات السيارات.

**الوضع الحالي:** SQLite ملف واحد (`database/database.sqlite`) — السوبر أدمن يرى كل البيانات. مناسب لـ cPanel. MySQL يبقى متاحاً عبر `DB_CONNECTION=mysql`.

**Stack:** Laravel 12 · PHP 8.2+ · SQLite (أو MySQL) · Inertia 2 · React 19 · TypeScript · Vite · Tailwind CSS 4 · shadcn/ui · Sanctum · DomPDF.

---

## العربية — التشغيل (cPanel / محلي)

### المتطلبات
- PHP 8.2+ مع امتداد `pdo_sqlite`
- Composer 2 · Node.js 18+

### الخطوات
1. من مجلد المشروع:
   ```bat
   cd C:\xampp\htdocs\BidAdmin\BidAdmin
   composer install
   copy .env.example .env
   php artisan key:generate
   if not exist database\database.sqlite type nul > database\database.sqlite
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

### MySQL / قواعد منفصلة لاحقاً
`DB_CONNECTION=mysql` ثم اضبط المضيف والمستخدم. لقواعد منفصلة: `TENANCY_SEPARATE_DATABASES=true` (MySQL فقط).

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
تستخدم SQLite (`database/testing.sqlite`) تلقائياً:
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
