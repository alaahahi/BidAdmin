import { DataTable, FilterRow, ListToolbar, Pagination, useList } from '@/components/ba/table';
import { NinetyDays, useBidColumns } from '@/components/ba/tables';
import { ColumnsButton, DateFilter, FilterSelect, PageHeader, SearchBar } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { mask } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Bid, Filters, Paginated } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    bids: Paginated<Bid>;
    filters: Filters;
    options: { providers: Record<string, string>; accounts: { id: number; username: string; provider: string; region: string }[]; customers: { id: number; name: string }[] };
}

export default function BidsPage({ bids, filters, options }: Props) {
    const t = useT();
    const list = useList('bids', route('explorer.bids'), filters);
    const columns = useBidColumns().map((c) => (c.key === 'ip' ? { ...c, render: (b: Bid) => <code className="text-[11px]">{mask(b.ip_address, list.hideSensitive) || '—'}</code> } : c));
    return (
        <AppLayout>
            <Head title="Customer Bids" />
            <PageHeader title={t('Customer Bids')} subtitle="View bids placed by your assigned customers" actions={<ListToolbar list={list} exportUrl={route('explorer.bids.export', filters)} />} />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by lot #, type, provider, customer, IP..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list} cols={7}>
                <FilterSelect value={filters.type} onChange={(v) => list.apply({ type: v })} placeholder="All Types" options={[['prebid', 'Prebid'], ['live', 'Live'], ['buy_now', 'Buy Now']]} />
                <FilterSelect value={filters.phase} onChange={(v) => list.apply({ phase: v })} placeholder="All Phases" options={[['preliminary', 'Preliminary'], ['live', 'Live'], ['final', 'Final']]} />
                <FilterSelect value={filters.provider} onChange={(v) => list.apply({ provider: v })} placeholder="All Providers" options={Object.entries(options.providers)} />
                <FilterSelect value={filters.account_id} onChange={(v) => list.apply({ account_id: v })} placeholder="All Accounts" options={options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
                <FilterSelect value={filters.customer_id} onChange={(v) => list.apply({ customer_id: v })} placeholder="All Placed By" options={options.customers.map((c) => [c.id, c.name])} />
                <FilterSelect value={filters.bids} onChange={(v) => list.apply({ bids: v })} placeholder="All bids" options={[['winning', 'Winning / Won'], ['outbid', 'Outbid / Lost']]} />
                <DateFilter from={filters.from as string} to={filters.to as string} onChange={(from, to) => list.apply({ from, to })} />
            </FilterRow>
            <NinetyDays filters={filters} />
            <DataTable columns={columns} rows={bids.data} list={list} />
            <Pagination page={bids} list={list} />
        </AppLayout>
    );
}
