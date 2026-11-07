import { AnalyticsView } from '@/components/ba/charts';
import { DateFilter, FilterSelect, PageHeader, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { useT } from '@/lib/i18n';
import type { AnalyticsData, Filters } from '@/types';
import { Head, router } from '@inertiajs/react';
import { Download, RefreshCw, RotateCcw } from 'lucide-react';
import { useState } from 'react';

interface Props {
    analytics: AnalyticsData;
    filters: Filters;
    options: { providers: Record<string, string>; accounts: { id: number; username: string; provider: string; region: string }[]; customers: { id: number; name: string }[]; activity_types: Record<string, string> };
}

export default function Analytics({ analytics, filters, options }: Props) {
    const t = useT();
    const [custom, setCustom] = useState(!!filters.from);
    const apply = (patch: Filters) => {
        const next = { ...filters, ...patch };
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] === undefined) && delete next[k]);
        router.get(route('explorer.analytics'), next as Record<string, string>, { preserveState: true, preserveScroll: true, replace: true });
    };
    const days = Number(filters.days ?? 7);
    const label = filters.from ? 'Custom range' : `Last ${days}D`;
    return (
        <AppLayout>
            <Head title="Analytics" />
            <PageHeader
                title={t('Analytics')}
                subtitle="Platform-wide activity insights & metrics"
                actions={
                    <div className="bg-card flex rounded-md border p-0.5 text-[11px]">
                        {[7, 30, 90].map((d) => (
                            <button
                                key={d}
                                onClick={() => {
                                    setCustom(false);
                                    apply({ days: d, from: '', to: '' });
                                }}
                                className={`rounded px-2.5 py-1 ${!custom && days === d ? 'brand-bg text-white' : 'hover:bg-muted'}`}
                            >
                                {d}D
                            </button>
                        ))}
                        <button onClick={() => setCustom(true)} className={`rounded px-2.5 py-1 ${custom ? 'brand-bg text-white' : 'hover:bg-muted'}`}>
                            Custom
                        </button>
                    </div>
                }
            />
            <div className="mb-4 flex flex-wrap gap-2">
                <FilterSelect className="min-w-40 flex-1" value={filters.type} onChange={(v) => apply({ type: v })} placeholder="All Activity Types" options={Object.entries(options.activity_types)} />
                <FilterSelect className="min-w-40 flex-1" value={filters.customer_id} onChange={(v) => apply({ customer_id: v })} placeholder="All Customers" options={options.customers.map((c) => [c.id, c.name])} />
                <FilterSelect className="min-w-40 flex-1" value={filters.account_id} onChange={(v) => apply({ account_id: v })} placeholder="All Accounts" options={options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
                <FilterSelect className="min-w-40 flex-1" value={filters.provider} onChange={(v) => apply({ provider: v })} placeholder="All Providers" options={Object.entries(options.providers)} />
                {custom && <DateFilter from={filters.from as string} to={filters.to as string} onChange={(from, to) => apply({ from, to })} />}
                <ToolbarButton className="h-9" icon={<RefreshCw />} onClick={() => router.reload()}>
                    Refresh
                </ToolbarButton>
                <ToolbarButton className="h-9" icon={<RotateCcw />} onClick={() => router.get(route('explorer.analytics'))}>
                    {t('Reset Filters')}
                </ToolbarButton>
                <a href={route('explorer.analytics', { ...filters, export: 1 })} className="bg-card hover:bg-muted inline-flex h-9 items-center gap-1.5 rounded-md border px-3 text-xs font-medium">
                    <Download className="size-3.5" /> {t('Export')}
                </a>
            </div>
            <AnalyticsView data={analytics} periodLabel={label} />
        </AppLayout>
    );
}
