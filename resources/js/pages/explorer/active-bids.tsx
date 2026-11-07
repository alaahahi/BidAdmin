import { AccountChip, ProviderMark } from '@/components/ba/provider';
import { DataTable, FilterRow, ListToolbar, Pagination, useList, type Column } from '@/components/ba/table';
import { Vehicle } from '@/components/ba/tables';
import { Badge, CarImage, ColumnsButton, DateFilter, FilterSelect, PageHeader, SearchBar } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { fmtDateTime, mask, money, useTz } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Bid, Filters, Paginated } from '@/types';
import { Head } from '@inertiajs/react';

interface Props {
    bids: Paginated<Bid>;
    filters: Filters;
    options: { accounts: { id: number; username: string; provider: string; region: string }[]; customers: { id: number; name: string }[] };
}

export default function ActiveBids({ bids, filters, options }: Props) {
    const t = useT();
    const tz = useTz();
    const list = useList('active-bids', route('explorer.active-bids'), filters);
    const columns: Column<Bid>[] = [
        { key: 'provider', label: 'Provider', render: (b) => <ProviderMark provider={b.provider} /> },
        { key: 'account', label: 'Account', sortKey: 'account_id', render: (b) => <AccountChip account={b.account ? { ...b.account, username: mask(b.account.username, list.hideSensitive) } : null} /> },
        { key: 'image', label: 'Image', render: (b) => <CarImage src={b.image_url} /> },
        { key: 'description', label: 'Description', sortKey: 'description', render: (b) => <Vehicle description={b.description} sub={b.vin} lot={`${b.lot_number}${b.location ? ' · ' + b.location : ''}`} /> },
        { key: 'sale_date', label: 'Sale Date', sortKey: 'sale_date', render: (b) => <span className="whitespace-nowrap">{fmtDateTime(b.sale_date, tz)}</span> },
        {
            key: 'current',
            label: 'Current Bid / Bid Status',
            sortKey: 'current_bid',
            render: (b) => (
                <div>
                    <div className="font-semibold">{money(b.current_bid ?? b.amount)}</div>
                    <Badge>{b.status}</Badge>
                </div>
            ),
        },
        { key: 'max', label: 'Max Bid', sortKey: 'max_bid', render: (b) => <span className="font-semibold">{money(b.max_bid)}</span> },
        { key: 'placed_by', label: 'Placed By', sortKey: 'customer_id', render: (b) => <span className="font-medium">{b.customer?.name ?? '—'}</span> },
    ];
    return (
        <AppLayout>
            <Head title="Active Bids" />
            <PageHeader title={t('Active Bids')} subtitle="Live bids from your auction accounts" actions={<ListToolbar list={list} exportUrl={route('explorer.active-bids.export', filters)} />} />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by lot #, VIN, make, model, status, account..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list}>
                <FilterSelect value={filters.status} onChange={(v) => list.apply({ status: v })} placeholder="All Statuses" options={[['active', 'Active'], ['winning', 'Winning'], ['outbid', 'Outbid']]} />
                <FilterSelect value={filters.customer_id} onChange={(v) => list.apply({ customer_id: v })} placeholder="All Placed By" options={options.customers.map((c) => [c.id, c.name])} />
                <FilterSelect value={filters.account_id} onChange={(v) => list.apply({ account_id: v })} placeholder="All Accounts" options={options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
                <DateFilter from={filters.from as string} to={filters.to as string} onChange={(from, to) => list.apply({ from, to })} />
            </FilterRow>
            <DataTable columns={columns} rows={bids.data} list={list} />
            <Pagination page={bids} list={list} />
        </AppLayout>
    );
}
