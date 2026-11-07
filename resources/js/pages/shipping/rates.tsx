import { DataTable, Pagination, useList, type Column } from '@/components/ba/table';
import { CheckList, Field, Modal, ModalFooter, PageHeader, TextInput, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { fmtDate, money, useTz } from '@/lib/format';
import type { Filters, Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, Plus, RefreshCw, Trash2, X } from 'lucide-react';
import { useState } from 'react';

export interface RateLine {
    yard: string;
    state: string;
    port: string;
    destination: string;
    price: string | number;
}
export interface Rate {
    id: number;
    name: string;
    valid_from: string | null;
    valid_until: string | null;
    towing: string;
    loading: string;
    surcharges: string;
    lines: RateLine[] | null;
    customers: { id: number; name: string }[];
}

function RateForm({ rate, customers, onClose }: { rate?: Rate; customers: { id: number; name: string }[]; onClose: () => void }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: rate?.name ?? '',
        valid_from: rate?.valid_from?.slice(0, 10) ?? '',
        valid_until: rate?.valid_until?.slice(0, 10) ?? '',
        towing: rate?.towing ?? '0',
        loading: rate?.loading ?? '0',
        surcharges: rate?.surcharges ?? '0',
        lines: (rate?.lines ?? [{ yard: '', state: '', port: '', destination: '', price: '' }]) as RateLine[],
        customer_ids: (rate?.customers ?? []).map((c) => c.id),
    });
    const setLine = (i: number, k: keyof RateLine, v: string) => setData('lines', data.lines.map((l, j) => (j === i ? { ...l, [k]: v } : l)));
    return (
        <Modal open onClose={onClose} title={rate ? 'Edit Shipping Rate' : 'New Shipping Rate'} wide>
            <form
                className="space-y-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    const o = { onSuccess: onClose, preserveScroll: true };
                    if (rate) put(route('shipping-rates.update', rate.id), o);
                    else post(route('shipping-rates.store'), o);
                }}
            >
                <div className="grid gap-4 md:grid-cols-3">
                    <Field label="Name" error={errors.name} className="md:col-span-3">
                        <TextInput value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                    </Field>
                    <Field label="Valid From">
                        <TextInput type="date" value={data.valid_from} onChange={(e) => setData('valid_from', e.target.value)} />
                    </Field>
                    <Field label="Valid Until" error={errors.valid_until}>
                        <TextInput type="date" value={data.valid_until} onChange={(e) => setData('valid_until', e.target.value)} />
                    </Field>
                    <div />
                    <Field label="Towing ($)">
                        <TextInput type="number" value={data.towing} onChange={(e) => setData('towing', e.target.value)} />
                    </Field>
                    <Field label="Loading ($)">
                        <TextInput type="number" value={data.loading} onChange={(e) => setData('loading', e.target.value)} />
                    </Field>
                    <Field label="Surcharges ($)">
                        <TextInput type="number" value={data.surcharges} onChange={(e) => setData('surcharges', e.target.value)} />
                    </Field>
                </div>
                <div>
                    <div className="mb-2 flex items-center justify-between">
                        <span className="text-xs font-medium">Rate lines (yard → port)</span>
                        <ToolbarButton small icon={<Plus />} onClick={() => setData('lines', [...data.lines, { yard: '', state: '', port: '', destination: '', price: '' }])}>
                            Add line
                        </ToolbarButton>
                    </div>
                    <div className="space-y-2">
                        {data.lines.map((l, i) => (
                            <div key={i} className="grid grid-cols-12 gap-2">
                                <TextInput className="col-span-3" placeholder="Yard" value={l.yard} onChange={(e) => setLine(i, 'yard', e.target.value)} />
                                <TextInput className="col-span-1" placeholder="ST" value={l.state} onChange={(e) => setLine(i, 'state', e.target.value)} />
                                <TextInput className="col-span-3" placeholder="Loading port" value={l.port} onChange={(e) => setLine(i, 'port', e.target.value)} />
                                <TextInput className="col-span-2" placeholder="Destination" value={l.destination} onChange={(e) => setLine(i, 'destination', e.target.value)} />
                                <TextInput className="col-span-2" type="number" placeholder="Price" value={String(l.price)} onChange={(e) => setLine(i, 'price', e.target.value)} />
                                <button type="button" className="text-muted-foreground" onClick={() => setData('lines', data.lines.filter((_, j) => j !== i))}>
                                    <X className="size-4" />
                                </button>
                            </div>
                        ))}
                    </div>
                    {Object.keys(errors).some((k) => k.startsWith('lines')) && <p className="mt-1 text-xs text-red-600">Each line needs a yard, port and price.</p>}
                </div>
                <Field label="Customers on this rate list">
                    <CheckList items={customers} selected={data.customer_ids} onChange={(v) => setData('customer_ids', v)} render={(c) => c.name} />
                </Field>
                <ModalFooter onCancel={onClose} processing={processing} />
            </form>
        </Modal>
    );
}

export default function Rates({ rates, filters, customers }: { rates: Paginated<Rate>; filters: Filters; customers: { id: number; name: string }[] }) {
    const tz = useTz();
    const list = useList('rates', route('shipping-rates.index'), filters);
    const [editing, setEditing] = useState<Rate | 'new' | null>(null);
    const columns: Column<Rate>[] = [
        {
            key: 'name',
            label: 'Name',
            sortKey: 'name',
            render: (r) => (
                <div>
                    <div className="font-semibold">{r.name}</div>
                    <div className="text-muted-foreground text-[10px]">{r.lines?.length ?? 0} lines</div>
                </div>
            ),
        },
        { key: 'from', label: 'Valid From', sortKey: 'valid_from', render: (r) => fmtDate(r.valid_from, tz) },
        { key: 'until', label: 'Valid Until', sortKey: 'valid_until', render: (r) => fmtDate(r.valid_until, tz) },
        { key: 'towing', label: 'Towing', sortKey: 'towing', render: (r) => money(r.towing) },
        { key: 'loading', label: 'Loading', sortKey: 'loading', render: (r) => money(r.loading) },
        { key: 'surcharges', label: 'Surcharges', sortKey: 'surcharges', render: (r) => money(r.surcharges) },
        { key: 'customers', label: 'Customers', render: (r) => <span title={r.customers.map((c) => c.name).join(', ')}>{r.customers.length}</span> },
        {
            key: 'actions',
            label: 'Actions',
            render: (r) => (
                <div className="flex gap-2">
                    <ToolbarButton icon={<Pencil />} onClick={() => setEditing(r)}>
                        Edit
                    </ToolbarButton>
                    <ToolbarButton danger icon={<Trash2 />} onClick={() => confirm('Delete rate list?') && router.delete(route('shipping-rates.destroy', r.id), { preserveScroll: true })} />
                </div>
            ),
        },
    ];
    return (
        <AppLayout>
            <Head title="Shipping" />
            <PageHeader
                title="Shipping"
                subtitle="Manage your shipping rate lists"
                actions={
                    <>
                        <ToolbarButton icon={<RefreshCw />} onClick={list.refresh}>
                            Hard Refresh
                        </ToolbarButton>
                        <ToolbarButton primary onClick={() => setEditing('new')}>
                            New Shipping Rate
                        </ToolbarButton>
                    </>
                }
            />
            <DataTable columns={columns} rows={rates.data} list={list} />
            <Pagination page={rates} list={list} />
            {editing && <RateForm rate={editing === 'new' ? undefined : editing} customers={customers} onClose={() => setEditing(null)} />}
        </AppLayout>
    );
}
