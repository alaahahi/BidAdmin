import AddAccountModal from '@/components/ba/add-account-modal';
import { ToolbarButton } from '@/components/ba/ui';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { money } from '@/lib/format';
import { useT } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { BreadcrumbItem, SharedData } from '@/types';
import { Link, router, usePage } from '@inertiajs/react';
import {
    ArrowRight,
    BarChart3,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    Clock,
    Copy,
    DollarSign,
    Eye,
    FileText,
    Gavel,
    Gauge,
    Hammer,
    LayoutGrid,
    LogOut,
    Phone,
    Plus,
    Receipt,
    Search,
    Settings,
    Ship,
    User,
    UserRound,
    Users,
} from 'lucide-react';
import { ReactNode, useEffect, useState } from 'react';
import { Toaster, toast } from 'sonner';

type Item = { title: string; route?: string; match?: string; icon: React.ComponentType<{ className?: string }>; onClick?: () => void; children?: Item[]; admin?: boolean };

function isActive(item: Item): boolean {
    if (item.match && route().current(item.match)) return true;
    if (item.route && route().current(item.route)) return true;
    return !!item.children?.some(isActive);
}

function NavLink({ item, depth = 0, collapsed }: { item: Item; depth?: number; collapsed: boolean }) {
    const t = useT();
    const active = isActive(item);
    const [open, setOpen] = useState(active);
    const Icon = item.icon;
    const base = cn(
        'relative flex w-full items-center gap-2.5 rounded-md px-2.5 text-[13px] transition',
        depth === 0 ? 'h-9' : 'h-8 text-xs',
        active && !item.children ? 'bg-muted font-semibold text-foreground' : 'text-foreground/80 hover:bg-muted/70',
    );
    const indicator = active && !item.children && <span className="bg-foreground absolute end-2 top-1/2 h-4 w-[3px] -translate-y-1/2 rounded" />;

    if (item.children) {
        return (
            <div>
                <button type="button" className={base} onClick={() => setOpen(!open)} title={t(item.title)}>
                    <Icon className="size-4 shrink-0" />
                    {!collapsed && (
                        <>
                            <span className="flex-1 text-start">{t(item.title)}</span>
                            <ChevronDown className={cn('size-3.5 transition', open && 'rotate-180')} />
                        </>
                    )}
                </button>
                {open && !collapsed && (
                    <div className={cn('mt-0.5 space-y-0.5', depth === 0 ? 'ps-2.5' : 'ps-3')}>
                        {item.children.map((c) => (
                            <NavLink key={c.title} item={c} depth={depth + 1} collapsed={collapsed} />
                        ))}
                    </div>
                )}
            </div>
        );
    }
    if (item.onClick) {
        return (
            <button type="button" className={base} onClick={item.onClick} title={t(item.title)}>
                <Icon className="size-4 shrink-0" />
                {!collapsed && t(item.title)}
            </button>
        );
    }
    return (
        <Link href={route(item.route!)} className={base} title={t(item.title)}>
            <Icon className="size-4 shrink-0" />
            {!collapsed && t(item.title)}
            {indicator}
        </Link>
    );
}

export default function AppLayout({ children }: { children: ReactNode; breadcrumbs?: BreadcrumbItem[] }) {
    const { auth, settings, balance, flash } = usePage<SharedData>().props;
    const t = useT();
    const isAdmin = auth.user?.role === 'admin';
    const [addOpen, setAddOpen] = useState(false);
    const [collapsed, setCollapsed] = useState(() => localStorage.getItem('ba.sidebar') === '1');
    const [token, setToken] = useState<string | null>(null);

    useEffect(() => {
        document.documentElement.style.setProperty('--brand', settings?.brand_color || '#111827');
        document.documentElement.dir = ['ar', 'ckb'].includes(settings?.language ?? 'en') ? 'rtl' : 'ltr';
        document.documentElement.lang = settings?.language ?? 'en';
    }, [settings?.brand_color, settings?.language]);

    useEffect(() => {
        if (flash?.success) toast.success(flash.success);
        if (flash?.error) toast.error(flash.error);
        if (flash?.token) setToken(flash.token);
    }, [flash]);

    useEffect(() => {
        if (new URLSearchParams(window.location.search).get('add') === '1' && isAdmin) setAddOpen(true);
    }, [isAdmin]);

    const nav: Item[] = [
        { title: 'Dashboard', route: 'dashboard', icon: Gauge },
        { title: 'Accounts', route: 'accounts.index', match: 'accounts.*', icon: Users },
        { title: 'Add Account', icon: Plus, onClick: () => setAddOpen(true), admin: true },
        { title: 'Invoices', route: 'invoices.index', match: 'invoices.*', icon: FileText },
        { title: 'Customers', route: 'customers.index', match: 'customers.*', icon: UserRound, admin: true },
        { title: 'Brokers', route: 'brokers.index', icon: User, admin: true },
        {
            title: 'Explorer',
            icon: Search,
            children: [
                { title: 'Analytics', route: 'explorer.analytics', icon: BarChart3 },
                { title: 'Activity', route: 'explorer.activity', icon: Clock },
                { title: 'Active Bids', route: 'explorer.active-bids', icon: Hammer },
                { title: 'Bids', route: 'explorer.bids', icon: DollarSign },
                { title: 'Watchlist', route: 'explorer.watchlist', icon: Eye },
            ],
        },
        {
            title: 'Utilities',
            icon: LayoutGrid,
            admin: true,
            children: [
                {
                    title: 'Shipping',
                    icon: Ship,
                    children: [
                        { title: 'Rates', route: 'shipping-rates.index', icon: Receipt },
                        { title: 'Quotations', route: 'quotations.index', icon: Gavel },
                    ],
                },
                { title: 'Numbers', route: 'numbers.index', icon: Phone },
            ],
        },
    ].filter((i) => isAdmin || !i.admin) as Item[];

    const copy = () => {
        if (token) navigator.clipboard?.writeText(token);
        toast.success('Copied');
    };

    return (
        <div className="bg-muted/30 flex min-h-screen dark:bg-background">
            <aside className={cn('bg-background sticky top-0 flex h-screen shrink-0 flex-col border-e transition-all', collapsed ? 'w-16' : 'w-[220px]')}>
                <div className="relative flex h-[52px] items-center gap-2 border-b px-4">
                    <Link href={route('dashboard')} className="flex min-w-0 items-center gap-2">
                        {settings?.logo_url ? (
                            <img src={settings.logo_url} className="h-7 max-w-[150px] object-contain" alt="logo" />
                        ) : (
                            <span className="brand-bg flex size-7 shrink-0 items-center justify-center rounded-md text-xs font-bold text-white">
                                {(settings?.company_name || 'B').slice(0, 1)}
                            </span>
                        )}
                        {!collapsed && !settings?.logo_url && <span className="truncate font-bold tracking-tight">{settings?.company_name || 'BidAdmin'}</span>}
                    </Link>
                    <button
                        onClick={() => {
                            localStorage.setItem('ba.sidebar', collapsed ? '0' : '1');
                            setCollapsed(!collapsed);
                        }}
                        className="bg-background text-muted-foreground absolute -end-3 top-[44px] z-10 flex size-6 items-center justify-center rounded border"
                    >
                        {collapsed ? <ChevronRight className="size-3.5 rtl:rotate-180" /> : <ChevronLeft className="size-3.5 rtl:rotate-180" />}
                    </button>
                </div>
                <nav className="flex-1 space-y-0.5 overflow-y-auto p-2 pt-3">
                    {nav.map((i) => (
                        <NavLink key={i.title} item={i} collapsed={collapsed} />
                    ))}
                </nav>
                <div className="border-t p-2">
                    {isAdmin && <NavLink item={{ title: 'Settings', route: 'settings.index', icon: Settings }} collapsed={collapsed} />}
                </div>
                {!collapsed && (
                    <div className="space-y-2 border-t p-2">
                        {isAdmin && balance !== null && (
                            <Link href={route('finance.index')} className="block rounded-lg bg-green-50 p-3 text-center dark:bg-green-950/40">
                                <div className="text-[11px] text-green-700 dark:text-green-400">{t('Current Balance')}</div>
                                <div className="text-base font-bold text-green-700 dark:text-green-400">{money(balance, { cents: true })}</div>
                                <div className="text-[10px] text-green-700/80">= {Math.floor((balance ?? 0) / 20)} cars</div>
                                <div className="mt-1 inline-flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400">
                                    {t('Go to Finance')} <ArrowRight className="size-3.5 rtl:rotate-180" />
                                </div>
                            </Link>
                        )}
                        <Link href={route('profile.edit')} className="hover:bg-muted flex items-center gap-2 rounded-md p-1.5">
                            <span className="bg-muted flex size-7 shrink-0 items-center justify-center rounded-full text-xs font-semibold">{auth.user?.name?.slice(0, 1)}</span>
                            <span className="min-w-0">
                                <span className="block truncate text-[11px] font-semibold uppercase">{auth.user?.name}</span>
                                <span className="text-muted-foreground block truncate text-[10px]">{auth.user?.email}</span>
                            </span>
                        </Link>
                        <Link href={route('logout')} method="post" as="button" className="flex w-full items-center gap-2 rounded-md px-2.5 py-2 text-[13px] text-red-600 hover:bg-red-50 dark:hover:bg-red-950">
                            <LogOut className="size-4 rtl:rotate-180" /> {t('Sign Out')}
                        </Link>
                    </div>
                )}
            </aside>
            <main className="min-w-0 flex-1 p-5 lg:px-5 lg:py-4">{children}</main>
            <AddAccountModal open={addOpen} onClose={() => setAddOpen(false)} />
            <Dialog open={!!token} onOpenChange={(o) => !o && setToken(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Your new API token</DialogTitle>
                        <DialogDescription>Copy it now. For security it will not be shown again.</DialogDescription>
                    </DialogHeader>
                    <code className="bg-muted block rounded-md p-3 text-xs break-all">{token}</code>
                    <div className="flex justify-end gap-2">
                        <ToolbarButton icon={<Copy />} onClick={copy}>
                            Copy
                        </ToolbarButton>
                        <ToolbarButton primary onClick={() => setToken(null)}>
                            Done
                        </ToolbarButton>
                    </div>
                </DialogContent>
            </Dialog>
            <Toaster position="top-right" richColors closeButton />
        </div>
    );
}

export const reload = () => router.reload();
