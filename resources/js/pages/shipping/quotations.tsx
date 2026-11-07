import { DataTable, Pagination, useList, type Column } from '@/components/ba/table';
import { Vehicle } from '@/components/ba/tables';
import { CarImage, Field, Modal, ModalFooter, PageHeader, SearchBar, SelectInput, TextInput, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { money } from '@/lib/format';
import type { Filters, Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Copy, ExternalLink, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import type { Rate } from './rates';

interface Quote {
    id: number;
    token: string;
    lot_number: string | null;
    vin: string | null;
    description: string | null;
    image_url: string | null;
    pickup_yard: string | null;
    loading_port: string | null;
    delivery_port: string | null;
    sale_amount: string;
    shipping: string;
    total: string;
    customer?: { name: string } | null;
    shipping_rate?: { name: string } | null;
}

function CreateQuote({ rates, customers, onClose }: { rates: Rate[]; customers: { id: number; name: string }[]; onClose: () => void }) {
    const { data, setData, post, processing, errors } = useForm({
        shipping_rate_id: rates[0]?.id ?? '',
        line: 0,
        customer_id: '',
        lot_number: '',
        vin: '',
        description: '',
        image_url: '',
        delivery_port: '',
        sale_amount: '',
    });
    const rate = rates.find((r) => r.id === Number(data.shipping_rate_id));
    const line = rate?.lines?.[data.line];
    const shipping = line ? Number(line.price) + Number(rate!.towing) + Number(rate!.loading) + Number(rate!.surcharges) : 0;
    return (
        <Modal open onClose={onClose} title="Create Quote" description="Pick a shipping rate list and route; the total is calculated automatically." wide>
            <form
                className="grid gap-4 md:grid-cols-2"
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('quotations.store'), { onSuccess: onClose });
                }}
            >
                <Field label="Shipping rate" error={errors.shipping_rate_id}>
                    <SelectInput value={data.shipping_rate_id} onChange={(e) => setData((d) => ({ ...d, shipping_rate_id: Number(e.target.value), line: 0 }))} options={rates.map((r) => [r.id, r.name])} />
                </Field>
                <Field label="Route" error={errors.line}>
                    <SelectInput value={data.line} onChange={(e) => setData('line', Number(e.target.value))} options={(rate?.lines ?? []).map((l, i) => [i, `${l.yard}, ${l.state} → ${l.port} (${money(l.price)})`])} />
                </Field>
                <Field label="Vehicle description" error={errors.description}>
                    <TextInput value={data.description} onChange={(e) => setData('description', e.target.value)} placeholder="2025 TOYOTA CAMRY LE" required />
                </Field>
                <Field label="Customer">
                    <SelectInput value={data.customer_id} onChange={(e) => setData('customer_id', e.target.value)} placeholder="—" options={customers.map((c) => [c.id, c.name])} />
                </Field>
                <Field label="Lot #">
                    <TextInput value={data.lot_number} onChange={(e) => setData('lot_number', e.target.value)} />
                </Field>
                <Field label="VIN">
                    <TextInput value={data.vin} onChange={(e) => setData('vin', e.target.value)} />
                </Field>
                <Field label="Sale amount ($)" error={errors.sale_amount}>
                    <TextInput type="number" value={data.sale_amount} onChange={(e) => setData('sale_amount', e.target.value)} required />
                </Field>
                <Field label="Delivery port" hint={`Default: ${line?.destination ?? '—'}`}>
                    <TextInput value={data.delivery_port} onChange={(e) => setData('delivery_port', e.target.value)} />
                </Field>
                <Field label="Image URL" className="md:col-span-2" error={errors.image_url}>
                    <TextInput value={data.image_url} onChange={(e) => setData('image_url', e.target.value)} />
                </Field>
                <div className="bg-muted/50 rounded-lg p-4 text-sm md:col-span-2">
                    Shipping {money(shipping)} + Sale {money(data.sale_amount || 0)} = <b>{money(shipping + Number(data.sale_amount || 0))}</b>
                </div>
                <div className="md:col-span-2">
                    <ModalFooter onCancel={onClose} processing={processing} submitLabel="Create Quote" />
                </div>
            </form>
        </Modal>
    );
}

export default function Quotations({ quotations, filters, rates, customers }: { quotations: Paginated<Quote>; filters: Filters; rates: Rate[]; customers: { id: number; name: string }[] }) {
    const list = useList('quotes', route('quotations.index'), filters);
    const [creating, setCreating] = useState(false);
    const columns: Column<Quote>[] = [
        { key: 'image', label: 'Image', render: (q) => <CarImage src={q.image_url} /> },
        { key: 'description', label: 'Description', sortKey: 'description', render: (q) => <Vehicle description={q.description} sub={q.vin} lot={[q.lot_number, q.customer?.name].filter(Boolean).join(' · ')} /> },
        { key: 'yard', label: 'Pickup Yard', render: (q) => q.pickup_yard },
        { key: 'lport', label: 'Loading Port', render: (q) => q.loading_port },
        { key: 'dport', label: 'Delivery Port', render: (q) => q.delivery_port },
        { key: 'sale', label: 'Sale Amount', sortKey: 'sale_amount', render: (q) => money(q.sale_amount) },
        { key: 'shipping', label: 'Shipping', sortKey: 'shipping', render: (q) => money(q.shipping) },
        { key: 'total', label: 'Total', sortKey: 'total', render: (q) => <b>{money(q.total)}</b> },
        {
            key: 'url',
            label: 'Quote URL',
            render: (q) => {
                const url = route('quotes.public', q.token);
                return (
                    <div className="flex gap-2">
                        <a href={url} target="_blank" className="inline-flex items-center gap-1 text-xs underline">
                            Open <ExternalLink className="size-3" />
                        </a>
                        <button
                            onClick={() => {
                                navigator.clipboard?.writeText(url);
                                toast.success('Link copied');
                            }}
                        >
                            <Copy className="size-3.5" />
                        </button>
                    </div>
                );
            },
        },
        { key: 'actions', label: 'Actions', render: (q) => <ToolbarButton danger icon={<Trash2 />} onClick={() => confirm('Delete quote?') && router.delete(route('quotations.destroy', q.id), { preserveScroll: true })} /> },
    ];
    return (
        <AppLayout>
            <Head title="Quotations" />
            <PageHeader title="Quotations" subtitle="View and manage all your quotations across shipping rates" />
            <SearchBar
                value={list.search}
                onChange={list.setSearch}
                placeholder="Search by VIN, lot number, make, model..."
                right={
                    <ToolbarButton primary className="h-9" icon={<Plus />} onClick={() => setCreating(true)} disabled={!rates.length}>
                        Create Quote
                    </ToolbarButton>
                }
            />
            <div className="h-2" />
            <DataTable columns={columns} rows={quotations.data} list={list} />
            <Pagination page={quotations} list={list} />
            {creating && <CreateQuote rates={rates} customers={customers} onClose={() => setCreating(false)} />}
        </AppLayout>
    );
}
