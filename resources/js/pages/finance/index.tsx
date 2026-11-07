import { DataTable, FilterRow, ListToolbar, Pagination, useList, type Column } from '@/components/ba/table';
import { Badge, CarImage, ColumnsButton, DateFilter, Field, FilterSelect, Modal, ModalFooter, PageHeader, SearchBar, SelectInput, StatCard, TextInput, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { fmtDate, money, useTz } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Filters, Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { DollarSign, Info } from 'lucide-react';
import { useState } from 'react';

interface Tx {
    id: number;
    date: string;
    description: string | null;
    image_url: string | null;
    amount: string;
    type: string;
    status: string;
    session_id: string | null;
    method: string | null;
    won_by?: { id: number; name: string } | null;
}

interface Props {
    transactions: Paginated<Tx>;
    filters: Filters;
    options: { types: Record<string, string>; customers: { id: number; name: string }[] };
    balance: number;
}

function AddBalance({ onClose }: { onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm({ amount: '200', method: 'cash', description: '' });
    return (
        <Modal open onClose={onClose} title="Add Balance" description="Record a manual top-up of your platform balance. Each won car costs $20.">
            <form
                className="space-y-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('finance.store'), { onSuccess: onClose });
                }}
            >
                <div className="grid grid-cols-4 gap-2">
                    {['100', '200', '500', '1000'].map((a) => (
                        <button type="button" key={a} onClick={() => setData('amount', a)} className={`rounded-md border py-2 text-sm ${data.amount === a ? 'brand-bg text-white' : 'hover:bg-muted'}`}>
                            ${a}
                        </button>
                    ))}
                </div>
                <Field label="Amount ($)" error={errors.amount} hint={`= ${Math.floor(Number(data.amount || 0) / 20)} cars`}>
                    <TextInput type="number" min={1} value={data.amount} onChange={(e) => setData('amount', e.target.value)} />
                </Field>
                <Field label="Method">
                    <SelectInput value={data.method} onChange={(e) => setData('method', e.target.value)} options={[['cash', 'Cash'], ['bank_transfer', 'Bank transfer'], ['card', 'Card (external)'], ['other', 'Other']]} />
                </Field>
                <Field label="Note">
                    <TextInput value={data.description} onChange={(e) => setData('description', e.target.value)} />
                </Field>
                <p className="text-muted-foreground flex gap-2 text-xs">
                    <Info className="size-4 shrink-0" /> No payment is charged here. This only records the transaction.
                </p>
                <ModalFooter onCancel={onClose} processing={processing} submitLabel="Add Balance" />
            </form>
        </Modal>
    );
}

export default function Finance({ transactions, filters, options, balance }: Props) {
    const t = useT();
    const tz = useTz();
    const list = useList('finance', route('finance.index'), filters);
    const [adding, setAdding] = useState(false);
    const columns: Column<Tx>[] = [
        { key: 'date', label: 'Date', sortKey: 'date', render: (x) => <span className="font-medium whitespace-nowrap">{fmtDate(x.date, tz)}</span> },
        { key: 'image', label: 'Image', render: (x) => (x.type === 'vehicle_charge' ? <CarImage src={x.image_url} /> : null) },
        { key: 'description', label: 'Description', render: (x) => x.description ?? '—' },
        { key: 'won_by', label: 'Won By', render: (x) => x.won_by?.name ?? '—' },
        { key: 'amount', label: 'Amount', sortKey: 'amount', render: (x) => <span className={`font-semibold ${x.type === 'vehicle_charge' ? 'text-red-600' : ''}`}>{x.type === 'vehicle_charge' ? '-' : ''}{money(x.amount, { cents: true })}</span> },
        { key: 'type', label: 'Type', sortKey: 'type', render: (x) => <span className="text-muted-foreground">{options.types[x.type] ?? x.type}</span> },
        { key: 'status', label: 'Status', sortKey: 'status', render: (x) => <Badge className="normal-case">{x.status === 'completed' ? 'Completed' : x.status === 'cancelled' ? 'Cancelled' : 'Pending'}</Badge> },
        {
            key: 'actions',
            label: 'Actions',
            render: (x) =>
                x.status === 'pending' ? (
                    <div className="flex gap-2 text-xs">
                        <button className="text-green-700 underline" onClick={() => router.put(route('finance.update', x.id), { status: 'completed' }, { preserveScroll: true })}>
                            Complete
                        </button>
                        <button className="text-red-600 underline" onClick={() => router.put(route('finance.update', x.id), { status: 'cancelled' }, { preserveScroll: true })}>
                            Cancel
                        </button>
                    </div>
                ) : null,
        },
    ];
    return (
        <AppLayout>
            <Head title="Transactions" />
            <PageHeader
                title={t('Transactions')}
                subtitle="View your payment history and download invoices"
                actions={
                    <ListToolbar
                        list={list}
                        sensitive={false}
                        exportUrl={route('finance.export', filters)}
                        before={
                            <ToolbarButton primary icon={<DollarSign />} onClick={() => setAdding(true)}>
                                {t('Add Balance')}
                            </ToolbarButton>
                        }
                    />
                }
            />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by amount, status, session ID..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list}>
                <FilterSelect value={filters.status} onChange={(v) => list.apply({ status: v })} placeholder="All Statuses" options={[['completed', 'Completed'], ['pending', 'Pending'], ['cancelled', 'Cancelled']]} />
                <FilterSelect value={filters.type} onChange={(v) => list.apply({ type: v })} placeholder="All Types" options={Object.entries(options.types)} />
                <FilterSelect value={filters.won_by} onChange={(v) => list.apply({ won_by: v })} placeholder="All Won By" options={options.customers.map((c) => [c.id, c.name])} />
                <DateFilter from={filters.from as string} to={filters.to as string} onChange={(from, to) => list.apply({ from, to })} />
            </FilterRow>
            <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4">
                <StatCard label="Current Balance" value={money(balance, { cents: true })} hint={`= ${Math.floor(balance / 20)} cars`} tone="green" />
            </div>
            <DataTable columns={columns} rows={transactions.data} list={list} />
            <Pagination page={transactions} list={list} />
            {adding && <AddBalance onClose={() => setAdding(false)} />}
        </AppLayout>
    );
}
