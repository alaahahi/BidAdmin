import InvoiceForm, { type InvoiceOptions } from '@/components/ba/invoice-form';
import { DataTable, FilterRow, ListToolbar, Pagination, useList } from '@/components/ba/table';
import { useInvoiceColumns } from '@/components/ba/tables';
import { ColumnsButton, DateFilter, FilterSelect, MultiStat, PageHeader, SearchBar, StatCard, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { mask, money } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Filters, Invoice, Paginated, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { Eye, Hash, Plus, RotateCcw } from 'lucide-react';
import { useState } from 'react';

interface Props {
    invoices: Paginated<Invoice>;
    filters: Filters;
    stats: { total: number; paid: number; pending: number; total_value: number; unpaid: number; due_soon: number; overdue: number };
    options: InvoiceOptions;
}

export default function InvoicesIndex({ invoices, filters, stats, options }: Props) {
    const t = useT();
    const isAdmin = usePage<SharedData>().props.auth.user.role === 'admin';
    const list = useList('invoices', route('invoices.index'), filters);
    const [codes, setCodes] = useState(false);
    const [creating, setCreating] = useState(false);
    const trashed = filters.trashed === '1';
    const base = useInvoiceColumns({ showWonBy: isAdmin });
    const columns = base.map((c) => {
        if (c.key === 'won_by')
            return {
                ...c,
                render: (i: Invoice) =>
                    i.won_by ? (
                        <div>
                            <div className="font-medium">{mask(i.won_by.name, list.hideSensitive)}</div>
                            {codes && <div className="text-muted-foreground text-[10px]">{i.won_by.code}</div>}
                        </div>
                    ) : (
                        <span className="text-muted-foreground">—</span>
                    ),
            };
        if (c.key === 'actions' && trashed)
            return {
                ...c,
                render: (i: Invoice) => (
                    <ToolbarButton small icon={<RotateCcw />} onClick={() => router.post(route('invoices.restore', i.id))}>
                        {t('Restore')}
                    </ToolbarButton>
                ),
            };
        return c;
    });

    return (
        <AppLayout>
            <Head title="Invoices" />
            <PageHeader
                title={t('Invoices')}
                subtitle="Manage vehicle invoices and relist fees"
                actions={
                    <ListToolbar
                        list={list}
                        exportUrl={route('invoices.export', filters)}
                        before={null}
                        extra={
                            <>
                                <ToolbarButton icon={<Hash />} onClick={() => setCodes(!codes)}>
                                    {codes ? t('Hide Codes') : t('Show Codes')}
                                </ToolbarButton>
                                {isAdmin && (
                                    <ToolbarButton icon={<Eye />} onClick={() => list.apply({ trashed: trashed ? '' : '1' })}>
                                        {trashed ? t('Hide Deleted') : t('Show Deleted')}
                                    </ToolbarButton>
                                )}
                                {isAdmin && (
                                    <ToolbarButton primary icon={<Plus />} onClick={() => setCreating(true)}>
                                        {t('New Invoice')}
                                    </ToolbarButton>
                                )}
                            </>
                        }
                    />
                }
            />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by lot number, VIN, or description..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list} cols={6}>
                <FilterSelect value={filters.type} onChange={(v) => list.apply({ type: v })} placeholder="All Types" options={Object.entries(options.types)} />
                <FilterSelect value={filters.account_id} onChange={(v) => list.apply({ account_id: v })} placeholder="All Accounts" options={options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
                <FilterSelect value={filters.provider} onChange={(v) => list.apply({ provider: v })} placeholder="All Providers" options={Object.entries(options.providers)} />
                <FilterSelect value={filters.status} onChange={(v) => list.apply({ status: v })} placeholder="All Statuses" options={[['paid', 'Paid'], ['pending', 'Pending'], ['overdue', 'Overdue']]} />
                <FilterSelect value={filters.won_by} onChange={(v) => list.apply({ won_by: v })} placeholder="All Won By" options={[['none', 'Not assigned'], ...options.customers.map((c) => [c.id, c.name] as [number, string])]} />
                <DateFilter from={filters.from as string} to={filters.to as string} onChange={(from, to) => list.apply({ from, to })} />
            </FilterRow>
            <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-5">
                <MultiStat
                    rows={[
                        { label: 'Total Invoices', value: stats.total },
                        { label: 'Paid Invoices', value: stats.paid, tone: 'green' },
                        { label: 'Pending Invoices', value: stats.pending, tone: 'orange' },
                    ]}
                />
                <StatCard label="Total Value" value={money(stats.total_value, { cents: true })} hint="Invoice amount" />
                <StatCard label="Unpaid" value={money(stats.unpaid)} hint="Unpaid invoices" tone="orange" onClick={() => list.apply({ status: filters.status === 'pending' ? '' : 'pending' })} active={filters.status === 'pending'} />
                <StatCard label="Due Soon" value={money(stats.due_soon)} hint="Due within 3 days" tone="orange" />
                <StatCard label="Overdue" value={money(stats.overdue)} hint="Past due" tone="red" onClick={() => list.apply({ status: filters.status === 'overdue' ? '' : 'overdue' })} active={filters.status === 'overdue'} />
            </div>
            <DataTable columns={columns} rows={invoices.data} list={list} />
            <Pagination page={invoices} list={list} />
            {creating && <InvoiceForm options={options} onClose={() => setCreating(false)} />}
        </AppLayout>
    );
}
