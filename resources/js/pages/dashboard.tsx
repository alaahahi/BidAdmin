import { DailyChart } from '@/components/ba/charts';
import { AccountChip, ProviderMark } from '@/components/ba/provider';
import { Badge, Card, CardHeader, LinkButton, PageHeader, StatCard } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { fmtDate, fmtDateTime, money, useTz } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { AnalyticsData, Bid, Invoice, SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

interface Props {
    stats: { accounts: number; unpaid_invoices: number; unpaid_amount: number; overdue_invoices: number; overdue_amount: number; bids: number; cars_won: number; active_bids: number };
    analytics: AnalyticsData;
    recentInvoices: Invoice[];
    recentBids: Bid[];
}

export default function Dashboard({ stats, analytics, recentInvoices, recentBids }: Props) {
    const t = useT();
    const tz = useTz();
    const { auth } = usePage<SharedData>().props;
    return (
        <AppLayout>
            <Head title="Dashboard" />
            <PageHeader
                title="Dashboard"
                subtitle={`${t('Welcome back')}, ${auth.user.name}. ${t('Here is what happened in the last 30 days.')}`}
                actions={
                    <LinkButton href={route('explorer.analytics')} primary>
                        {t('Open Analytics')}
                    </LinkButton>
                }
            />
            <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
                <StatCard label="Total Accounts" value={stats.accounts} hint="Auction accounts" />
                <StatCard label="Unpaid Invoices" value={stats.unpaid_invoices} hint={`${money(stats.unpaid_amount)} ${t('outstanding')}`} tone="orange" />
                <StatCard label="Overdue Invoices" value={stats.overdue_invoices} hint={`${money(stats.overdue_amount)} ${t('past due')}`} tone="red" />
                <StatCard label="Bids" value={stats.bids} hint={`${stats.active_bids} ${t('active now')}`} />
                <StatCard label="Cars Won" value={stats.cars_won} hint="Won lot invoices" tone="green" />
                <StatCard label="Active Customers" value={analytics.metrics.active_customers} hint="Last 30 days" />
            </div>
            <div className="mb-4 grid gap-4 lg:grid-cols-3">
                <Card className="lg:col-span-2">
                    <CardHeader title="Daily Activity" subtitle="Lot views, bids, watchlist adds and wins per day" />
                    <div className="p-4">
                        <DailyChart data={analytics.daily} height={260} />
                    </div>
                </Card>
                <Card>
                    <CardHeader title="Top Customers" subtitle="Most active in the last 30 days" />
                    <div className="divide-y">
                        {analytics.top_customers.length === 0 && <p className="text-muted-foreground p-5 text-sm">{t('No activity yet.')}</p>}
                        {analytics.top_customers.map((c, i) => (
                            <div key={c.id} className="flex items-center justify-between px-5 py-3 text-sm">
                                <span className="flex items-center gap-3">
                                    <span className="bg-muted flex size-7 items-center justify-center rounded-full text-xs font-semibold">{i + 1}</span>
                                    {auth.user.role === 'admin' ? (
                                        <Link href={route('customers.show', c.id)} className="font-medium hover:underline">
                                            {c.label}
                                        </Link>
                                    ) : (
                                        <span className="font-medium">{c.label}</span>
                                    )}
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    {c.bids} {t('bids')} · {c.won} {t('wins')}
                                </span>
                            </div>
                        ))}
                    </div>
                </Card>
            </div>
            <div className="grid gap-4 lg:grid-cols-2">
                <Card>
                    <CardHeader title="Recent Invoices" actions={<LinkButton href={route('invoices.index')}>{t('View all')}</LinkButton>} />
                    <div className="divide-y">
                        {recentInvoices.map((i) => (
                            <Link key={i.id} href={route('invoices.show', i.id)} className="hover:bg-muted/40 flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span className="flex min-w-0 items-center gap-3">
                                    <ProviderMark provider={i.provider} />
                                    <span className="min-w-0">
                                        <span className="block truncate font-medium uppercase">{i.description}</span>
                                        <span className="text-muted-foreground text-xs">
                                            {i.invoice_number} · {fmtDate(i.invoice_date, tz)} · {i.won_by?.name ?? t('Unassigned')}
                                        </span>
                                    </span>
                                </span>
                                <span className="text-end">
                                    <span className="block font-semibold">{money(i.amount)}</span>
                                    <Badge>{t(i.status)}</Badge>
                                </span>
                            </Link>
                        ))}
                    </div>
                </Card>
                <Card>
                    <CardHeader title="Latest Bids" actions={<LinkButton href={route('explorer.bids')}>{t('View all')}</LinkButton>} />
                    <div className="divide-y">
                        {recentBids.map((b) => (
                            <div key={b.id} className="flex items-center justify-between gap-3 px-5 py-3 text-sm">
                                <span className="min-w-0">
                                    <span className="block truncate font-medium uppercase">{b.description}</span>
                                    <span className="text-muted-foreground flex items-center gap-2 text-xs">
                                        {b.customer?.name} · <AccountChip account={b.account} /> · {fmtDateTime(b.placed_at, tz)}
                                    </span>
                                </span>
                                <span className="text-end">
                                    <span className="block font-semibold">{money(b.amount)}</span>
                                    <Badge>{b.status}</Badge>
                                </span>
                            </div>
                        ))}
                    </div>
                </Card>
            </div>
        </AppLayout>
    );
}
