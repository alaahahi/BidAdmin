import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

export type AppLanguage = 'en' | 'ar' | 'ckb';

export const RTL_LANGUAGES: AppLanguage[] = ['ar', 'ckb'];

export function isRtl(lang: string | null | undefined): boolean {
    return RTL_LANGUAGES.includes((lang ?? 'en') as AppLanguage);
}

/** English key → Arabic / Kurdish Sorani. Missing keys fall back to English. */
const translations: Record<string, { ar: string; ckb: string }> = {
    // Nav
    Dashboard: { ar: 'لوحة التحكم', ckb: 'داشبۆرد' },
    Accounts: { ar: 'الحسابات', ckb: 'هەژمارەکان' },
    'Add Account': { ar: 'إضافة حساب', ckb: 'زیادکردنی هەژمار' },
    'Add New Account': { ar: 'إضافة حساب جديد', ckb: 'زیادکردنی هەژماری نوێ' },
    Invoices: { ar: 'الفواتير', ckb: 'پسوولەکان' },
    Customers: { ar: 'العملاء', ckb: 'کڕیارەکان' },
    Brokers: { ar: 'الوسطاء', ckb: 'نێوەندگیرەکان' },
    Explorer: { ar: 'المستكشف', ckb: 'گەڕۆک' },
    Analytics: { ar: 'التحليلات', ckb: 'شیکاری' },
    Activity: { ar: 'النشاط', ckb: 'چالاکی' },
    'Active Bids': { ar: 'المزايدات النشطة', ckb: 'مەزادە چالاکەکان' },
    Bids: { ar: 'المزايدات', ckb: 'مەزادەکان' },
    Watchlist: { ar: 'قائمة المتابعة', ckb: 'لیستی چاودێری' },
    Utilities: { ar: 'الأدوات', ckb: 'ئامرازەکان' },
    Shipping: { ar: 'الشحن', ckb: 'گواستنەوە' },
    Rates: { ar: 'الأسعار', ckb: 'نرخەکان' },
    Quotations: { ar: 'عروض الأسعار', ckb: 'پێشنیارەکان' },
    Numbers: { ar: 'الأرقام', ckb: 'ژمارەکان' },
    Settings: { ar: 'الإعدادات', ckb: 'ڕێکخستنەکان' },
    Finance: { ar: 'المالية', ckb: 'دارایی' },
    'Current Balance': { ar: 'الرصيد الحالي', ckb: 'باڵانسی ئێستا' },
    'Go to Finance': { ar: 'اذهب إلى المالية', ckb: 'بڕۆ بۆ دارایی' },
    'Sign Out': { ar: 'تسجيل الخروج', ckb: 'چوونەدەرەوە' },

    // Toolbar
    'Hard Refresh': { ar: 'تحديث', ckb: 'نوێکردنەوە' },
    'Show Deleted': { ar: 'عرض المحذوف', ckb: 'پیشاندانی سڕاوەکان' },
    'Hide Deleted': { ar: 'إخفاء المحذوف', ckb: 'شاردنەوەی سڕاوەکان' },
    'Hide Sensitive Info': { ar: 'إخفاء البيانات الحساسة', ckb: 'شاردنەوەی زانیاری هەستیار' },
    'Show Sensitive Info': { ar: 'إظهار البيانات الحساسة', ckb: 'پیشاندانی زانیاری هەستیار' },
    'Reset Filters': { ar: 'إعادة ضبط الفلاتر', ckb: 'ڕێکخستنەوەی فلتەرەکان' },
    'Hide Filters': { ar: 'إخفاء الفلاتر', ckb: 'شاردنەوەی فلتەرەکان' },
    'Show Filters': { ar: 'إظهار الفلاتر', ckb: 'پیشاندانی فلتەرەکان' },
    Export: { ar: 'تصدير', ckb: 'هەناردە' },
    'Manage Columns': { ar: 'إدارة الأعمدة', ckb: 'بەڕێوەبردنی ستوونەکان' },
    'View Details': { ar: 'عرض التفاصيل', ckb: 'بینینی وردەکاری' },
    Restore: { ar: 'استعادة', ckb: 'گەڕاندنەوە' },
    Delete: { ar: 'حذف', ckb: 'سڕینەوە' },
    Edit: { ar: 'تعديل', ckb: 'دەستکاری' },
    Save: { ar: 'حفظ', ckb: 'پاشەکەوت' },
    Cancel: { ar: 'إلغاء', ckb: 'هەڵوەشاندنەوە' },
    'Add Customer': { ar: 'إضافة عميل', ckb: 'زیادکردنی کڕیار' },
    'Add Broker': { ar: 'إضافة وسيط', ckb: 'زیادکردنی نێوەندگیر' },
    'Add Balance': { ar: 'إضافة رصيد', ckb: 'زیادکردنی باڵانس' },
    'New Invoice': { ar: 'فاتورة جديدة', ckb: 'پسوولەی نوێ' },
    'Show Codes': { ar: 'إظهار الرموز', ckb: 'پیشاندانی کۆدەکان' },
    'Hide Codes': { ar: 'إخفاء الرموز', ckb: 'شاردنەوەی کۆدەکان' },
    'Open Analytics': { ar: 'فتح التحليلات', ckb: 'کردنەوەی شیکاری' },
    'View all': { ar: 'عرض الكل', ckb: 'بینینی هەموو' },

    // Table / pagination
    Actions: { ar: 'إجراءات', ckb: 'کردارەکان' },
    Showing: { ar: 'عرض', ckb: 'پیشاندان' },
    to: { ar: 'إلى', ckb: 'بۆ' },
    of: { ar: 'من', ckb: 'لە' },
    results: { ar: 'نتيجة', ckb: 'ئەنجام' },
    'per page:': { ar: 'لكل صفحة:', ckb: 'بۆ هەر پەڕەیەک:' },
    First: { ar: 'الأولى', ckb: 'یەکەم' },
    Previous: { ar: 'السابق', ckb: 'پێشوو' },
    Next: { ar: 'التالي', ckb: 'دواتر' },
    Last: { ar: 'الأخيرة', ckb: 'کۆتایی' },
    Page: { ar: 'صفحة', ckb: 'پەڕە' },
    'No results found': { ar: 'لا توجد نتائج', ckb: 'هیچ ئەنجامێک نەدۆزرایەوە' },
    'Loading...': { ar: 'جاري التحميل...', ckb: 'چاوەڕوان بە...' },
    'Not provided': { ar: 'غير متوفر', ckb: 'دابین نەکراوە' },
    'Nothing to choose from.': { ar: 'لا يوجد خيارات.', ckb: 'هیچ بژاردەیەک نییە.' },

    // Common columns
    Provider: { ar: 'المنصة', ckb: 'پلاتفۆرم' },
    Type: { ar: 'النوع', ckb: 'جۆر' },
    Username: { ar: 'اسم المستخدم', ckb: 'ناوی بەکارهێنەر' },
    Password: { ar: 'كلمة المرور', ckb: 'وشەی نهێنی' },
    Company: { ar: 'الشركة', ckb: 'کۆمپانیا' },
    'Balance Due': { ar: 'الرصيد المستحق', ckb: 'باڵانسی قەرز' },
    Status: { ar: 'الحالة', ckb: 'دۆخ' },
    Tags: { ar: 'الوسوم', ckb: 'تاگەکان' },
    Name: { ar: 'الاسم', ckb: 'ناو' },
    Email: { ar: 'البريد', ckb: 'ئیمەیڵ' },
    'Phone Number': { ar: 'رقم الهاتف', ckb: 'ژمارەی تەلەفۆن' },
    'Customer Code': { ar: 'رمز العميل', ckb: 'کۆدی کڕیار' },
    'Bid Limits': { ar: 'حدود المزايدة', ckb: 'سنووری مەزاد' },
    Date: { ar: 'التاريخ', ckb: 'بەروار' },
    'Date/Time': { ar: 'التاريخ/الوقت', ckb: 'بەروار/کات' },
    Amount: { ar: 'المبلغ', ckb: 'بڕ' },
    Due: { ar: 'المستحق', ckb: 'قەرز' },
    Image: { ar: 'الصورة', ckb: 'وێنە' },
    Description: { ar: 'الوصف', ckb: 'وەسف' },
    'Won By': { ar: 'فاز بها', ckb: 'بردرا لەلایەن' },
    'Lot/Invoice #': { ar: 'رقم اللوت/الفاتورة', ckb: 'ژمارەی لۆت/پسوولە' },
    'Lot #': { ar: 'رقم اللوت', ckb: 'ژمارەی لۆت' },
    Account: { ar: 'الحساب', ckb: 'هەژمار' },
    'Placed By': { ar: 'وضعت بواسطة', ckb: 'دانرا لەلایەن' },
    Phase: { ar: 'المرحلة', ckb: 'قۆناغ' },
    'Bid Amount': { ar: 'مبلغ المزايدة', ckb: 'بڕی مەزاد' },
    'IP Address': { ar: 'عنوان IP', ckb: 'ناونیشانی IP' },
    App: { ar: 'التطبيق', ckb: 'ئەپ' },
    'Activity Type': { ar: 'نوع النشاط', ckb: 'جۆری چالاکی' },
    Target: { ar: 'الهدف', ckb: 'ئامانج' },
    'Sale Status': { ar: 'حالة البيع', ckb: 'دۆخی فرۆشتن' },
    'Auction Status': { ar: 'حالة المزاد', ckb: 'دۆخی مەزاد' },
    Added: { ar: 'أُضيف', ckb: 'زیادکرا' },
    Customer: { ar: 'العميل', ckb: 'کڕیار' },
    Transactions: { ar: 'المعاملات', ckb: 'مامەڵەکان' },
    'Customer Bids': { ar: 'مزايدات العملاء', ckb: 'مەزادەکانی کڕیار' },
    'Pickup Yard': { ar: 'ساحات الاستلام', ckb: 'گۆڕەپانی وەرگرتن' },
    'Loading Port': { ar: 'ميناء التحميل', ckb: 'بەندەری بارکردن' },
    'Delivery Port': { ar: 'ميناء التسليم', ckb: 'بەندەری گەیاندن' },
    'Sale Amount': { ar: 'مبلغ البيع', ckb: 'بڕی فرۆشتن' },
    Total: { ar: 'الإجمالي', ckb: 'کۆی گشتی' },
    'Quote URL': { ar: 'رابط العرض', ckb: 'بەستەری پێشنیار' },
    'Valid From': { ar: 'صالح من', ckb: 'کارا لە' },
    'Valid Until': { ar: 'صالح حتى', ckb: 'کارا تا' },
    Towing: { ar: 'السحب', ckb: 'ڕاکێشان' },
    Loading: { ar: 'التحميل', ckb: 'بارکردن' },
    Surcharges: { ar: 'رسوم إضافية', ckb: 'کرێی زیادە' },
    'Assigned To': { ar: 'مُسند إلى', ckb: 'سپێردراوە بە' },
    'Next Bill': { ar: 'الفاتورة التالية', ckb: 'پسوولەی داهاتوو' },
    Created: { ar: 'تاريخ الإنشاء', ckb: 'دروستکرا' },
    Expires: { ar: 'ينتهي', ckb: 'بەسەردەچێت' },
    Never: { ar: 'أبداً', ckb: 'هەرگیز' },
    Role: { ar: 'الدور', ckb: 'ڕۆڵ' },
    'API Key': { ar: 'مفتاح API', ckb: 'کلیلی API' },
    Assigned: { ar: 'مُسند', ckb: 'سپێردراو' },

    // Stats
    'Total Accounts': { ar: 'إجمالي الحسابات', ckb: 'کۆی هەژمارەکان' },
    'Action Required': { ar: 'يتطلب إجراء', ckb: 'پێویستی بە کردار هەیە' },
    'Total Overdue': { ar: 'إجمالي المتأخر', ckb: 'کۆی دواکەوتوو' },
    'Total Unpaid': { ar: 'إجمالي غير المدفوع', ckb: 'کۆی نەدراو' },
    'Unpaid Invoices': { ar: 'فواتير غير مدفوعة', ckb: 'پسوولە نەدراوەکان' },
    'Total count': { ar: 'العدد الإجمالي', ckb: 'کۆی ژمارە' },
    'Requiring immediate attention': { ar: 'يتطلب اهتماماً فورياً', ckb: 'پێویستی بە سەرنجی خێرا هەیە' },
    'Requires immediate action': { ar: 'يتطلب إجراءً فورياً', ckb: 'پێویستی بە کرداری خێرا هەیە' },
    'Pending payment': { ar: 'بانتظار الدفع', ckb: 'چاوەڕوانی پارەدان' },
    'Requiring attention': { ar: 'يتطلب الانتباه', ckb: 'پێویستی بە سەرنج هەیە' },
    'Auction accounts': { ar: 'حسابات المزاد', ckb: 'هەژمارەکانی مەزاد' },
    'Overdue Invoices': { ar: 'فواتير متأخرة', ckb: 'پسوولە دواکەوتووەکان' },
    'Cars Won': { ar: 'سيارات فائزة', ckb: 'ئۆتۆمبێلی براوە' },
    'Active Customers': { ar: 'عملاء نشطون', ckb: 'کڕیارە چالاکەکان' },
    'Won lot invoices': { ar: 'فواتير اللوتات الفائزة', ckb: 'پسوولەی لۆتی براو' },
    'Last 30 days': { ar: 'آخر 30 يوماً', ckb: 'دوایین ٣٠ ڕۆژ' },
    'Total Invoices': { ar: 'إجمالي الفواتير', ckb: 'کۆی پسوولەکان' },
    'Paid Invoices': { ar: 'فواتير مدفوعة', ckb: 'پسوولە دراوەکان' },
    'Pending Invoices': { ar: 'فواتير معلّقة', ckb: 'پسوولە هەڵپەسێردراوەکان' },
    'Daily Activity': { ar: 'النشاط اليومي', ckb: 'چالاکی ڕۆژانە' },
    'Top Customers': { ar: 'أفضل العملاء', ckb: 'باشترین کڕیارەکان' },
    'Recent Invoices': { ar: 'أحدث الفواتير', ckb: 'دوایین پسوولەکان' },
    'Recent Bids': { ar: 'أحدث المزايدات', ckb: 'دوایین مەزادەکان' },
    'Latest Bids': { ar: 'أحدث المزايدات', ckb: 'دوایین مەزادەکان' },
    Unassigned: { ar: 'غير مسند', ckb: 'سپێردراو نییە' },
    'Welcome back': { ar: 'مرحباً بعودتك', ckb: 'بەخێربێیتەوە' },
    'Here is what happened in the last 30 days.': {
        ar: 'إليك ما حدث خلال آخر 30 يوماً.',
        ckb: 'ئەمە ئەوەیە کە لە دوایین ٣٠ ڕۆژدا ڕووی دا.',
    },
    outstanding: { ar: 'مستحق', ckb: 'قەرزدار' },
    'past due': { ar: 'متأخر', ckb: 'دواکەوتوو' },
    'active now': { ar: 'نشط الآن', ckb: 'ئێستا چالاکە' },
    'No activity yet.': { ar: 'لا يوجد نشاط بعد.', ckb: 'هێشتا چالاکی نییە.' },
    bids: { ar: 'مزايدة', ckb: 'مەزاد' },
    wins: { ar: 'فوز', ckb: 'بردنەوە' },
    Details: { ar: 'التفاصيل', ckb: 'وردەکاری' },
    'Download PDF': { ar: 'تحميل PDF', ckb: 'داگرتنی PDF' },
    'Lot views, bids, watchlist adds and wins per day': {
        ar: 'مشاهدات اللوت والمزايدات والمتابعة والفوز يومياً',
        ckb: 'بینینی لۆت و مەزاد و چاودێری و بردنەوە بۆ هەر ڕۆژ',
    },
    'Most active in the last 30 days': { ar: 'الأكثر نشاطاً خلال آخر 30 يوماً', ckb: 'چالاکترین لە دوایین ٣٠ ڕۆژ' },

    // Filters / search
    'All Providers': { ar: 'كل المنصات', ckb: 'هەموو پلاتفۆرمەکان' },
    'All Regions': { ar: 'كل المناطق', ckb: 'هەموو ناوچەکان' },
    'All Action Statuses': { ar: 'كل حالات الإجراء', ckb: 'هەموو دۆخەکانی کردار' },
    'All Tags': { ar: 'كل الوسوم', ckb: 'هەموو تاگەکان' },
    'All Types': { ar: 'كل الأنواع', ckb: 'هەموو جۆرەکان' },
    'All Accounts': { ar: 'كل الحسابات', ckb: 'هەموو هەژمارەکان' },
    'All Statuses': { ar: 'كل الحالات', ckb: 'هەموو دۆخەکان' },
    'All Won By': { ar: 'كل الفائزين', ckb: 'هەموو براوەکان' },
    'Not assigned': { ar: 'غير مسند', ckb: 'سپێردراو نییە' },
    Paid: { ar: 'مدفوع', ckb: 'دراو' },
    Pending: { ar: 'معلّق', ckb: 'هەڵپەسێردراو' },
    Overdue: { ar: 'متأخر', ckb: 'دواکەوتوو' },
    paid: { ar: 'مدفوع', ckb: 'دراو' },
    pending: { ar: 'معلّق', ckb: 'هەڵپەسێردراو' },
    overdue: { ar: 'متأخر', ckb: 'دواکەوتوو' },
    won: { ar: 'فائز', ckb: 'براو' },
    winning: { ar: 'رابح', ckb: 'براوە' },
    outbid: { ar: 'تم تجاوزه', ckb: 'تێپەڕێنرا' },
    lost: { ar: 'خاسر', ckb: 'دۆڕاو' },
    active: { ar: 'نشط', ckb: 'چالاک' },
    expired: { ar: 'منتهي', ckb: 'بەسەرچوو' },
    cancelled: { ar: 'ملغى', ckb: 'هەڵوەشاوە' },
    'Search by name, email, phone number, or customer code...': {
        ar: 'ابحث بالاسم أو البريد أو الهاتف أو رمز العميل...',
        ckb: 'بگەڕێ بە ناو، ئیمەیڵ، تەلەفۆن، یان کۆدی کڕیار...',
    },
    'All Invoices': { ar: 'كل الفواتير', ckb: 'هەموو پسوولەکان' },
    'No accounts': { ar: 'بدون حسابات', ckb: 'بێ هەژمار' },
    'With invoices': { ar: 'مع فواتير', ckb: 'لەگەڵ پسوولە' },
    'With unpaid invoices': { ar: 'مع فواتير غير مدفوعة', ckb: 'لەگەڵ پسوولەی نەدراو' },
    'No invoices': { ar: 'بدون فواتير', ckb: 'بێ پسوولە' },
    Credentials: { ar: 'بيانات الدخول', ckb: 'زانیاری چوونەژوور' },
    'Login used for this auction account. The password is stored encrypted.': {
        ar: 'بيانات الدخول لهذا الحساب. كلمة المرور مخزّنة مشفّرة.',
        ckb: 'زانیاری چوونەژووری ئەم هەژمارە. وشەی نهێنی بە شێوەی کۆدکراو هەڵگیراوە.',
    },
    'Primary details about this account': { ar: 'التفاصيل الأساسية لهذا الحساب', ckb: 'وردەکاری سەرەکی ئەم هەژمارە' },
    'Membership Details': { ar: 'تفاصيل العضوية', ckb: 'وردەکاری ئەندامێتی' },
    'Subscription and billing information': { ar: 'معلومات الاشتراك والفواتير', ckb: 'زانیاری بەشداری و پسوولە' },
    'Customer Login Notice': { ar: 'تنبيه دخول العميل', ckb: 'ئاگاداری چوونەژووری کڕیار' },
    'Shown to customers when they open this account through BidAdmin.': {
        ar: 'يظهر للعملاء عند فتح هذا الحساب عبر BidAdmin.',
        ckb: 'کاتێک کڕیار ئەم هەژمارە لە ڕێگەی BidAdmin دەکاتەوە پیشان دەدرێت.',
    },
    'Customers who can bid through this account. Every action is attributed to them.': {
        ar: 'العملاء الذين يمكنهم المزايدة عبر هذا الحساب. كل إجراء يُنسب إليهم.',
        ckb: 'کڕیارەکان کە دەتوانن لە ڕێگەی ئەم هەژمارە مەزاد بکەن. هەر کردارێک دەدرێتە پاڵیان.',
    },
    'Brokers linked to this account': { ar: 'الوسطاء المرتبطون بهذا الحساب', ckb: 'نێوەندگیرە پەیوەندیدارەکان بەم هەژمارە' },
    'View and manage all your quotations across shipping rates': {
        ar: 'عرض وإدارة كل عروض الأسعار عبر أسعار الشحن',
        ckb: 'بینین و بەڕێوەبردنی هەموو پێشنیارەکان لەسەر نرخەکانی گواستنەوە',
    },
    'Manage your shipping rate lists': { ar: 'إدارة قوائم أسعار الشحن', ckb: 'بەڕێوەبردنی لیستی نرخەکانی گواستنەوە' },
    'Buy and manage phone numbers for SMS and OTP routing.': {
        ar: 'شراء وإدارة أرقام الهاتف لتوجيه الرسائل ورمز التحقق.',
        ckb: 'کڕین و بەڕێوەبردنی ژمارەی تەلەفۆن بۆ SMS و OTP.',
    },
    'Every login, lot view, bid and watchlist change, attributed to the customer who made it': {
        ar: 'كل تسجيل دخول وعرض لوت ومزايدة وتغيير متابعة، منسوب للعميل الذي قام به',
        ckb: 'هەر چوونەژوورەوە و بینینی لۆت و مەزاد و گۆڕانی چاودێری، دەدرێتە پاڵ ئەو کڕیارەی کردوویەتی',
    },
    'View bids placed by your assigned customers': {
        ar: 'عرض المزايدات التي وضعها عملاؤك المسندون',
        ckb: 'بینینی مەزادەکانی کڕیارە سپێردراوەکانت',
    },
    'Search by username, company, provider, region...': {
        ar: 'ابحث باسم المستخدم أو الشركة أو المنصة أو المنطقة...',
        ckb: 'بگەڕێ بە ناوی بەکارهێنەر، کۆمپانیا، پلاتفۆرم، ناوچە...',
    },
    'Search by lot number, VIN, or description...': {
        ar: 'ابحث برقم اللوت أو VIN أو الوصف...',
        ckb: 'بگەڕێ بە ژمارەی لۆت یان VIN یان وەسف...',
    },

    // Page subtitles
    'Manage auction house accounts and credentials': {
        ar: 'إدارة حسابات منصات المزادات وبيانات الدخول',
        ckb: 'بەڕێوەبردنی هەژمار و زانیاری چوونەژووری مەزادەکان',
    },
    'Manage your customers and their associated accounts': {
        ar: 'إدارة عملائك والحسابات المرتبطة بهم',
        ckb: 'بەڕێوەبردنی کڕیارەکانت و هەژمارە پەیوەندیدارەکان',
    },
    'Manage vehicle invoices and relist fees': {
        ar: 'إدارة فواتير المركبات ورسوم إعادة الإدراج',
        ckb: 'بەڕێوەبردنی پسوولەی ئۆتۆمبێل و کرێی دووبارە لیستکردن',
    },
    'Connect a new auction platform account to your dashboard.': {
        ar: 'اربط حساب منصة مزاد جديدة بلوحة التحكم.',
        ckb: 'هەژمارێکی نوێی پلاتفۆرمی مەزاد ببەستە بە داشبۆردەکەت.',
    },

    // Add account modal
    'Select Platform': { ar: 'اختر المنصة', ckb: 'پلاتفۆرم هەڵبژێرە' },
    'Select Region': { ar: 'اختر المنطقة', ckb: 'ناوچە هەڵبژێرە' },
    'Please select a platform first': { ar: 'يرجى اختيار المنصة أولاً', ckb: 'تکایە سەرەتا پلاتفۆرم هەڵبژێرە' },
    'Enter your username': { ar: 'أدخل اسم المستخدم', ckb: 'ناوی بەکارهێنەر بنووسە' },
    'Enter your password': { ar: 'أدخل كلمة المرور', ckb: 'وشەی نهێنی بنووسە' },
    "The password is encrypted with your application key and is only visible to admins on the account's Credentials tab.": {
        ar: 'كلمة المرور مشفّرة بمفتاح التطبيق وتظهر فقط للمسؤولين في تبويب بيانات الدخول.',
        ckb: 'وشەی نهێنی بە کلیلی ئەپەکە کۆدکراوە و تەنها ئەدمینەکان لە تابەتی زانیاری چوونەژوور دەیبینن.',
    },

    // Activity types
    Login: { ar: 'تسجيل دخول', ckb: 'چوونەژوورەوە' },
    'Lot View': { ar: 'عرض لوت', ckb: 'بینینی لۆت' },
    'Bid Placed': { ar: 'مزايدة موضوعة', ckb: 'مەزاد دانرا' },
    'Watchlist Add': { ar: 'إضافة للمتابعة', ckb: 'زیادکردن بۆ چاودێری' },
    'Watchlist Remove': { ar: 'إزالة من المتابعة', ckb: 'لابردن لە چاودێری' },
    'Car Won': { ar: 'سيارة فائزة', ckb: 'ئۆتۆمبێل برا' },
    'Credential Reveal': { ar: 'كشف بيانات الدخول', ckb: 'پیشاندانی زانیاری چوونەژوور' },

    // Settings
    Language: { ar: 'اللغة', ckb: 'زمان' },
    Branding: { ar: 'الهوية', ckb: 'براندینگ' },
    Team: { ar: 'الفريق', ckb: 'تیم' },
    Development: { ar: 'التطوير', ckb: 'گەشەپێدان' },
    Timezone: { ar: 'المنطقة الزمنية', ckb: 'ناوچەی کاتی' },
    Performance: { ar: 'الأداء', ckb: 'کارایی' },
    'App Configuration': { ar: 'إعدادات التطبيق', ckb: 'ڕێکخستنی ئەپ' },
    'Manage your account settings and preferences': {
        ar: 'إدارة إعدادات وتفضيلات حسابك',
        ckb: 'بەڕێوەبردنی ڕێکخستن و هەڵبژاردەکانی هەژمارەکەت',
    },
    'Interface language. Arabic and Kurdish Sorani switch the layout to right-to-left.': {
        ar: 'لغة الواجهة. العربية والكردية السورانية تقلبان التخطيط من اليمين لليسار.',
        ckb: 'زمانی ڕووکار. عەرەبی و کوردی سۆرانی ڕێکخستن دەکەنە ڕاست بۆ چەپ.',
    },
};

function buildDict(lang: 'ar' | 'ckb'): Record<string, string> {
    const out: Record<string, string> = {};
    for (const [key, val] of Object.entries(translations)) {
        out[key] = val[lang];
    }
    return out;
}

const dictionaries: Partial<Record<AppLanguage, Record<string, string>>> = {
    ar: buildDict('ar'),
    ckb: buildDict('ckb'),
};

export function useT() {
    const lang = (usePage<SharedData>().props.settings?.language ?? 'en') as AppLanguage;
    const dict = dictionaries[lang];
    return (s: string) => dict?.[s] ?? s;
}
