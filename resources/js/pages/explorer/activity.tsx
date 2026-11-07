import { FilterRow, ListToolbar, Pagination, useList } from '@/components/ba/table';
import { ActivityTable, NinetyDays } from '@/components/ba/tables';
import { DateFilter, FilterSelect, PageHeader, SearchBar } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { useT } from '@/lib/i18n';
import type { Activity, Filters, Paginated } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    activities: Paginated<Activity>;
    filters: Filters;
    options: { providers: Record<string, string>; accounts: { id: number; username: string; provider: string; region: string }[]; customers: { id: number; name: string }[]; activity_types: Record<string, string>; apps: string[] };
}

export default function ActivityPage({ activities, filters, options }: Props) {
    const t = useT();
    const list = useList('activity', route('explorer.activity'), filters);
    return (
        <AppLayout>
            <Head title="Activity" />
            <PageHeader title={t('Activity')} subtitle="Every login, lot view, bid and watchlist change, attributed to the customer who made it" actions={<ListToolbar list={list} sensitive={false} exportUrl={route('explorer.activity.export', filters)} />} />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by lot #, customer, account, IP..." />
            <FilterRow list={list} cols={6}>
                <FilterSelect value={filters.type} onChange={(v) => list.apply({ type: v })} placeholder="All Activity Types" options={Object.entries(options.activity_types)} />
                <FilterSelect value={filters.customer_id} onChange={(v) => list.apply({ customer_id: v })} placeholder="All Placed By" options={options.customers.map((c) => [c.id, c.name])} />
                <FilterSelect value={filters.account_id} onChange={(v) => list.apply({ account_id: v })} placeholder="All Accounts" options={options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
                <FilterSelect value={filters.provider} onChange={(v) => list.apply({ provider: v })} placeholder="All Providers" options={Object.entries(options.providers)} />
                <FilterSelect value={filters.app} onChange={(v) => list.apply({ app: v })} placeholder="All Apps" options={options.apps.map((a) => [a, a])} />
                <DateFilter from={filters.from as string} to={filters.to as string} onChange={(from, to) => list.apply({ from, to })} />
            </FilterRow>
            <NinetyDays filters={filters} />
            <ActivityTable rows={activities.data} list={list} />
            <Pagination page={activities} list={list} />
        </AppLayout>
    );
}
