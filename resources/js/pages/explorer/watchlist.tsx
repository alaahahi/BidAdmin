import { DataTable, FilterRow, ListToolbar, Pagination, useList } from '@/components/ba/table';
import { useWatchColumns } from '@/components/ba/tables';
import { ColumnsButton, DateFilter, FilterSelect, PageHeader, SearchBar } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { useT } from '@/lib/i18n';
import type { Filters, Paginated, WatchItem } from '@/types';
import { Head, router } from '@inertiajs/react';

interface Props {
    items: Paginated<WatchItem>;
    filters: Filters;
    options: { providers: Record<string, string>; accounts: { id: number; username: string; provider: string; region: string }[]; customers: { id: number; name: string }[] };
}

export default function Watchlist({ items, filters, options }: Props) {
    const t = useT();
    const list = useList('watchlist', route('explorer.watchlist'), filters);
    const columns = useWatchColumns((w) => confirm('Remove from watchlist?') && router.delete(route('explorer.watchlist.destroy', w.id), { preserveScroll: true }));
    return (
        <AppLayout>
            <Head title="Watchlist" />
            <PageHeader title={t('Watchlist')} subtitle="Vehicles your customers are watching" actions={<ListToolbar list={list} sensitive={false} exportUrl={route('explorer.watchlist.export', filters)} />} />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by lot #, VIN, description, customer..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list} cols={6}>
                <FilterSelect value={filters.provider} onChange={(v) => list.apply({ provider: v })} placeholder="All Providers" options={Object.entries(options.providers)} />
                <FilterSelect value={filters.account_id} onChange={(v) => list.apply({ account_id: v })} placeholder="All Accounts" options={options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
                <FilterSelect value={filters.customer_id} onChange={(v) => list.apply({ customer_id: v })} placeholder="All Customers" options={options.customers.map((c) => [c.id, c.name])} />
                <FilterSelect value={filters.sale_status} onChange={(v) => list.apply({ sale_status: v })} placeholder="All Sale Statuses" options={['On Approval', 'Pure Sale', 'Minimum Bid', 'Sold'].map((s) => [s, s])} />
                <FilterSelect value={filters.auction_status} onChange={(v) => list.apply({ auction_status: v })} placeholder="All Auction Statuses" options={['Upcoming', 'Live', 'Ended'].map((s) => [s, s])} />
                <DateFilter from={filters.from as string} to={filters.to as string} onChange={(from, to) => list.apply({ from, to })} />
            </FilterRow>
            <DataTable columns={columns} rows={items.data} list={list} />
            <Pagination page={items} list={list} />
        </AppLayout>
    );
}
