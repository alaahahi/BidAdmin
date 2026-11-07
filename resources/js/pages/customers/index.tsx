import { AccountChip, InvoiceCounts } from '@/components/ba/provider';
import { DataTable, FilterRow, ListToolbar, Pagination, useList, type Column } from '@/components/ba/table';
import { ColumnsButton, FilterSelect, LinkButton, PageHeader, SearchBar, StatCard, Tags, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { mask, money } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Account, Customer, Filters, Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Car, DollarSign, ExternalLink, RotateCcw, Trash2, Wallet } from 'lucide-react';
import { useState } from 'react';
import CustomerForm from '@/components/ba/customer-form';

interface Props {
    customers: Paginated<Customer>;
    filters: Filters;
    stats: { active: number };
    options: { accounts: Account[]; tags: string[] };
}

export default function CustomersIndex({ customers, filters, stats, options }: Props) {
    const t = useT();
    const list = useList('customers', route('customers.index'), filters);
    const [adding, setAdding] = useState(false);
    const trashed = filters.trashed === '1';
    const hide = list.hideSensitive;

    const columns: Column<Customer>[] = [
        {
            key: 'name',
            label: 'Name',
            sortKey: 'name',
            render: (c) => (
                <Link href={route('customers.show', c.id)} className="inline-flex items-center gap-1 font-semibold whitespace-nowrap uppercase hover:underline">
                    {c.name} <ExternalLink className="text-muted-foreground size-3" />
                </Link>
            ),
        },
        { key: 'code', label: 'Customer Code', sortKey: 'code', render: (c) => <span className="text-muted-foreground">{hide ? '-----' : (c.code ?? '-----')}</span> },
        { key: 'email', label: 'Email', sortKey: 'email', render: (c) => mask(c.email, hide) || '' },
        { key: 'phone', label: 'Phone Number', sortKey: 'phone', render: (c) => (c.phone ? mask(`${c.phone_code ?? ''}${c.phone}`, hide) : '') },
        {
            key: 'accounts',
            label: 'Accounts',
            render: (c) => (
                <div className="flex max-w-[320px] flex-wrap gap-1">
                    {c.accounts?.length ? c.accounts.map((a) => <AccountChip key={a.id} account={a} />) : <span className="text-muted-foreground">—</span>}
                </div>
            ),
        },
        {
            key: 'limits',
            label: 'Bid Limits',
            render: (c) => (
                <div className="space-y-0.5 text-[11px]">
                    <div className="flex items-center gap-1" title="Max bid amount">
                        <DollarSign className="size-3 text-green-600" /> {c.bid_limit ? money(c.bid_limit) : '–'}
                    </div>
                    <div className="flex items-center gap-1" title="Max cars">
                        <Car className="size-3" /> {c.car_limit ?? '–'}
                    </div>
                    <div className="flex items-center gap-1" title="Credit limit">
                        <Wallet className="size-3 text-orange-500" /> {c.credit_limit ? money(c.credit_limit) : '–'}
                    </div>
                </div>
            ),
        },
        { key: 'invoices', label: 'Invoices', render: (c) => <InvoiceCounts total={c.invoices_count} paid={c.paid_invoices_count} unpaid={c.unpaid_invoices_count} /> },
        { key: 'tags', label: 'Tags', render: (c) => <Tags tags={c.tags} /> },
        {
            key: 'actions',
            label: 'Actions',
            render: (c) =>
                trashed ? (
                    <ToolbarButton small icon={<RotateCcw />} onClick={() => router.post(route('customers.restore', c.id))}>
                        {t('Restore')}
                    </ToolbarButton>
                ) : (
                    <div className="flex gap-2">
                        <LinkButton primary href={route('customers.show', c.id)}>
                            {t('View Details')}
                        </LinkButton>
                        <ToolbarButton danger className="h-8" icon={<Trash2 />} onClick={() => confirm(`Delete ${c.name}?`) && router.delete(route('customers.destroy', c.id), { preserveScroll: true })} />
                    </div>
                ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Customers" />
            <PageHeader
                title={t('Customers')}
                subtitle="Manage your customers and their associated accounts"
                actions={
                    <ListToolbar
                        list={list}
                        deleted
                        exportUrl={route('customers.export', filters)}
                        extra={
                            <ToolbarButton primary onClick={() => setAdding(true)}>
                                {t('Add Customer')}
                            </ToolbarButton>
                        }
                    />
                }
            />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by name, email, phone number, or customer code..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list} cols={3}>
                <FilterSelect value={filters.account_id} onChange={(v) => list.apply({ account_id: v })} placeholder="All Accounts" options={[['none', 'No accounts'], ...options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`] as [number, string])]} />
                <FilterSelect value={filters.invoices} onChange={(v) => list.apply({ invoices: v })} placeholder="All Invoices" options={[['with', 'With invoices'], ['unpaid', 'With unpaid invoices'], ['none', 'No invoices']]} />
                <FilterSelect value={filters.tag} onChange={(v) => list.apply({ tag: v })} placeholder="All Tags" options={options.tags.map((x) => [x, x])} />
            </FilterRow>
            <div className="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <StatCard label="Active Customers" value={stats.active} hint="Total count" />
            </div>
            <DataTable columns={columns} rows={customers.data} list={list} />
            <Pagination page={customers} list={list} />
            {adding && <CustomerForm onClose={() => setAdding(false)} />}
        </AppLayout>
    );
}
