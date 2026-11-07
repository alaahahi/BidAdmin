# BidAdmin

نسخة مصغّرة من BidManager.io لإدارة حسابات مزادات السيارات (Copart / IAAI / Manheim / EdgePipeline / NPA)، ربطها بالعملاء والوسطاء، تسجيل مين من العملاء عمل كل مزايدة/مشاهدة/إضافة للمتابعة/فوز، ومتابعة الفواتير والدفعات.

A mini BidManager.io: manage car-auction accounts, link them to customers and brokers, record which customer placed every bid / lot view / watchlist change / win, and track invoices.

**Stack:** Laravel 12 · PHP 8.2+ · MySQL/MariaDB (XAMPP) · Inertia 2 · React 19 · TypeScript · Vite · Tailwind CSS 4 · shadcn/ui · Recharts · Sanctum (API tokens) · DomPDF (invoice PDFs).

---

## العربية — التشغيل على XAMPP (Windows)

### المتطلبات
- **XAMPP مع PHP 8.2 أو أحدث** (مثلاً XAMPP 8.2.12). تأكد: `php -v` لازم يطلع 8.2 أو أعلى. Laravel 12 ما بيشتغل على PHP 8.0/8.1.
- Composer 2
- Node.js 18+ مع npm
- بملف `C:\xampp\php\php.ini` تأكد إن هالسطور مفعّلة (بدون `;` بأولها): `extension=zip` · `extension=fileinfo` · `extension=gd` · `extension=pdo_mysql` · `extension=mbstring` · `extension=openssl`. بعد التعديل أعد تشغيل Apache.

### الخطوات
1. شغّل **Apache** و **MySQL** من XAMPP Control Panel.
2. افتح phpMyAdmin على `http://localhost/phpmyadmin` ← **New** ← اسم قاعدة البيانات `bidadmin` ← Collation: `utf8mb4_unicode_ci` ← **Create**.
3. فك ضغط الملف داخل `C:\xampp\htdocs\BidAdmin` بحيث يكون الملف `C:\xampp\htdocs\BidAdmin\artisan` موجود.
4. افتح Terminal (PowerShell أو CMD) داخل المجلد:
   ```bat
   cd C:\xampp\htdocs\BidAdmin
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm install
   npm run build
   php artisan serve
   ```
   - `.env.example` مضبوط مسبقًا لـ XAMPP: `DB_HOST=127.0.0.1`، `DB_DATABASE=bidadmin`، `DB_USERNAME=root`، وكلمة سر فاضية.
   - أثناء التطوير بدل `npm run build` شغّل `npm run dev` بنافذة ثانية (أو `composer run dev` لتشغيل السيرفر و Vite مع بعض).
   - `php artisan storage:link` على ويندوز ممكن يحتاج تشغيل الـ Terminal كـ Administrator (لرفع الشعار والمستندات).
5. افتح التطبيق:
   - مع `php artisan serve`: **http://127.0.0.1:8000**
   - أو عبر Apache مباشرة: **http://localhost/BidAdmin/public** — لهالطريقة غيّر بملف `.env` السطر `APP_URL=http://localhost/BidAdmin/public` ثم نفّذ `php artisan config:clear`.

### بيانات الدخول التجريبية
| الدور | الإيميل | كلمة السر |
|---|---|---|
| Admin | `admin@bidadmin.test` | `password` |
| Admin (ثاني) | `salam@bidadmin.test` | `password` |
| Customer (العميل NASHWAN) | `customer@bidadmin.test` | `password` |

لإعادة البيانات التجريبية من الصفر: `php artisan migrate:fresh --seed`.

### ملاحظات
- كلمات سر حسابات المزاد مشفّرة بـ Laravel Crypt (AES-256 بمفتاح `APP_KEY`). لا تظهر بأي جدول، والأدمن فقط يقدر يكشفها من تبويب **Credentials** بصفحة الحساب، وكل كشف بينسجّل بسجل النشاط. **لا تغيّر `APP_KEY` بعد إضافة حسابات** وإلا ما بتنفك كلمات السر.
- العميل بيشوف بس حساباته ومزايداته وقائمة متابعته والفواتير اللي ربحها.
- صفحة Numbers وزر Add Balance للعرض فقط: ما في شراء أرقام حقيقي ولا دفع حقيقي.

---

## English — XAMPP setup (Windows)

### Requirements
- **XAMPP with PHP 8.2 or newer** (e.g. XAMPP 8.2.12). Laravel 12 does not run on PHP 8.0/8.1 — check with `php -v`.
- Composer 2, Node.js 18+ with npm.
- In `C:\xampp\php\php.ini` make sure these are enabled (no leading `;`): `extension=zip`, `extension=fileinfo`, `extension=gd`, `extension=pdo_mysql`, `extension=mbstring`, `extension=openssl`. Restart Apache afterwards.

### Steps
1. Start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Open phpMyAdmin (`http://localhost/phpmyadmin`) → **New** → database name `bidadmin`, collation `utf8mb4_unicode_ci` → **Create**.
3. Unzip into `C:\xampp\htdocs\BidAdmin` (so `C:\xampp\htdocs\BidAdmin\artisan` exists).
4. In a terminal:
   ```bat
   cd C:\xampp\htdocs\BidAdmin
   composer install
   copy .env.example .env
   php artisan key:generate
   php artisan migrate --seed
   php artisan storage:link
   npm install
   npm run build
   php artisan serve
   ```
   `.env.example` is already set for XAMPP (`DB_HOST=127.0.0.1`, `DB_DATABASE=bidadmin`, `DB_USERNAME=root`, empty password). For development use `npm run dev` (or `composer run dev`) instead of `npm run build`. On Windows `storage:link` may require an Administrator terminal.
5. Open **http://127.0.0.1:8000**, or through Apache at **http://localhost/BidAdmin/public** (set `APP_URL=http://localhost/BidAdmin/public` in `.env`, then `php artisan config:clear`). The Vite build uses relative asset URLs, so it works from the sub-folder too.

Demo logins: `admin@bidadmin.test` / `password` (admin) and `customer@bidadmin.test` / `password` (customer NASHWAN). Reset demo data with `php artisan migrate:fresh --seed`.

### Modules
| Area | Route | What it does |
|---|---|---|
| Dashboard | `/dashboard` | Accounts, unpaid/overdue invoices, bids, cars won, active customers, daily activity chart, top customers, recent invoices & bids |
| Accounts | `/accounts` | Cards (Total, Action Required, Total Overdue, Total Unpaid, Unpaid Invoices), search, filters (provider/region/action status/tags), CSV export, show deleted + restore, hide sensitive info, manage columns, Add Account modal (platform → region → username/password) |
| Account detail | `/accounts/{id}?tab=` | Overview (info, notes, membership + stat cards), Credentials (admin reveal), Documents (grouped upload), Active Bids, Invoices, Customers & Brokers (assignment + history), Analytics, Settings (customer login notice) |
| Invoices | `/invoices` | Cards (Total/Paid/Pending, Total Value, Unpaid, Due Soon, Overdue), filters (type/account/provider/status/won by/date), CSV, PDF, create/edit. **Won By is set automatically** from the customer with the winning bid on the same lot. Detail tabs: Overview, Customers, Brokers, Bids, Activity |
| Customers | `/customers` | Code, bid limits (max bid, max cars, credit), tags, assigned accounts, invoice counts; detail tabs Details, Credentials (portal login + API tokens), Accounts, Invoices, Bids, Watchlist, Analytics, Settings |
| Brokers | `/brokers` | CRUD + shared accounts + "cover on your plan" |
| Explorer | `/explorer/analytics`, `/activity`, `/active-bids`, `/bids`, `/watchlist` | 7D/30D/90D/custom analytics, activity log (type, placed by, account, target, IP, app), bids, active bids, watchlist (90-day default window) |
| Utilities | `/shipping-rates`, `/quotations`, `/numbers` | Rate lists with lines and customers, quotes with public share URL (`/q/{token}`), phone numbers (UI only) |
| Finance | `/finance` | Transactions, Add Balance (manual record, no real payment), CSV |
| Settings | `/settings?tab=` | Branding (light/dark, logo, company name, brand color), App Configuration, Timezone, Performance (Low Data Mode), Language (English / العربية RTL), Development (API keys), Team |

### Tracking API (for the future browser extension)
Create a token for a customer on **Customers → (customer) → Credentials → API Tokens** (or an admin key in **Settings → Development**). The customer is taken from the token and the request IP is recorded automatically. Admin keys may pass `customer_id`.

```bash
curl -X POST http://127.0.0.1:8000/api/bids \
  -H "Authorization: Bearer <TOKEN>" -H "Accept: application/json" -H "Content-Type: application/json" \
  -d '{"account_id":1,"lot_number":"46121395","amount":5000,"max_bid":6500,"type":"prebid","description":"2025 TOYOTA CAMRY"}'
```
| Endpoint | Body |
|---|---|
| `POST /api/activity` | `type` (login, lot_view, bid, watchlist_add, watchlist_remove, won), `account_id`, `lot_number`/`target`, `description`, `amount`, `app`, `meta` |
| `POST /api/bids` | `account_id`, `lot_number`, `amount`, `max_bid`, `type` (prebid/live/buy_now), `phase`, `status` (a `won` bid also sets Won By on a matching invoice), `sale_date`, `vin`, `description`, `image_url` |
| `POST /api/watchlist` | `account_id`, `lot_number`, `action` (add/remove), `description`, `sale_status`, `auction_status` |
| `GET /api/me` | Current token user and assigned accounts |

Customers can only post for accounts assigned to them; bids above the customer's bid limit are rejected (422).

### Tests
```bash
php artisan test
```
Tests use an in-memory SQLite database (see `phpunit.xml`); on XAMPP enable `extension=pdo_sqlite` and `extension=sqlite3` in `php.ini` to run them.
