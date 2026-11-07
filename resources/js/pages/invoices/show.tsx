import InvoiceForm, { type InvoiceOptions } from '@/components/ba/invoice-form';
import { AccountChip, ProviderMark } from '@/components/ba/provider';
import { DataTable } from '@/components/ba/table';
import { ActivityTable, BidsTable } from '@/components/ba/tables';
import { Badge, Card, CardHeader, CarImage, InfoRow, StatCard, Tabs, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { fmtDate, money, useTz } from '@/lib/format';
import type { Activity, Bid, Invoice } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FileDown, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
    invoice: Invoice & { account?: Invoice['account'] & { customers?: { id: number; name: string; code: string | null; email: string | null; phone: string | null }[]; brokers?: { id: number; name: string; email: string | null }[] } };
    tab: string;
    bids: Bid[];
    activities: Activity[];
    options: InvoiceOptions;
    isAdmin: boolean;
}

export default function InvoiceShow({ invoice, tab, bids, activities, options, isAdmin }: Props) {
    const tz = useTz();
    const [editing, setEditing] = useState(false);
    const tabs: [string, string][] = [
        ['overview', 'Overview'],
        ...(isAdmin
            ? ([
                  ['customers', 'Customers'],
                  ['brokers', 'Brokers'],
              ] as [string, string][])
            : []),
        ['bids', 'Bids'],
        ['activity', 'Activity'],
    ];
    return (
        <AppLayout>
            <Head title={`Invoice ${invoice.invoice_number}`} />
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-start gap-3">
                    <CarImage src={invoice.image_url} className="h-16 w-24" />
                    <div>
                        <div className="flex items-center gap-2">
                            <ProviderMark provider={invoice.provider} />
                            <h1 className="text-xl font-semibold">{invoice.invoice_number}</h1>
                            <Badge>{invoice.status}</Badge>
                            {invoice.deleted_at && <Badge tone="overdue">Deleted</Badge>}
                        </div>
                        <div className="text-sm font-medium uppercase">{invoice.description}</div>
                        <div className="text-muted-foreground text-xs">
                            {invoice.vin} · {invoice.location}
                        </div>
                    </div>
                </div>
                <div className="flex gap-2">
                    <a href={route('invoices.pdf', invoice.id)} className="bg-card hover:bg-muted inline-flex h-8 items-center gap-1.5 rounded-md border px-3 text-xs font-medium">
                        <FileDown className="size-3.5" /> PDF
                    </a>
                    {isAdmin && (
                        <>
                            <ToolbarButton icon={<Pencil />} onClick={() => setEditing(true)}>
                                Edit
                            </ToolbarButton>
                            {!invoice.deleted_at && (
                                <ToolbarButton danger icon={<Trash2 />} onClick={() => confirm('Delete this invoice?') && router.delete(route('invoices.destroy', invoice.id))}>
                                    Delete
                                </ToolbarButton>
                            )}
                        </>
                    )}
                </div>
            </div>
            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-4">
                <StatCard label="Amount" value={money(invoice.amount, { cents: true })} hint="Invoice amount" />
                <StatCard label="Amount Due" value={money(invoice.amount_due, { cents: true })} hint={invoice.status === 'paid' ? 'Paid' : 'Outstanding'} tone={Number(invoice.amount_due) > 0 ? 'orange' : 'green'} />
                <StatCard label="Due Date" value={fmtDate(invoice.due_date, tz)} hint={invoice.status === 'overdue' ? 'Past due' : 'Payment deadline'} tone={invoice.status === 'overdue' ? 'red' : 'default'} />
                <StatCard label="Won By" value={invoice.won_by?.name ?? '—'} hint={invoice.won_by?.code ?? 'Customer with the winning bid'} />
            </div>
            <Tabs tabs={tabs} current={tab} hrefFor={(k) => route('invoices.show', { id: invoice.id, tab: k })} />
            {tab === 'overview' && (
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader title="Primary Information" />
                        <InfoRow label="Lot / Invoice #">{invoice.invoice_number}</InfoRow>
                        <InfoRow label="Type">
                            <Badge tone="lot">{invoice.type}</Badge>
                        </InfoRow>
                        <InfoRow label="Account">{invoice.account ? <AccountChip account={invoice.account} /> : null}</InfoRow>
                        <InfoRow label="Invoice Date">{fmtDate(invoice.invoice_date, tz, 'long')}</InfoRow>
                        <InfoRow label="VIN">{invoice.vin}</InfoRow>
                        <InfoRow label="Location">{invoice.location}</InfoRow>
                        <InfoRow label="Won By">
                            {invoice.won_by ? (
                                isAdmin ? (
                                    <Link className="hover:underline" href={route('customers.show', invoice.won_by.id)}>
                                        {invoice.won_by.name}
                                    </Link>
                                ) : (
                                    invoice.won_by.name
                                )
                            ) : null}
                        </InfoRow>
                    </Card>
                    <div className="space-y-4">
                        <Card>
                            <CardHeader title="Financial Details" />
                            <InfoRow label="Sale Price">{invoice.sale_price ? money(invoice.sale_price, { cents: true }) : null}</InfoRow>
                            <InfoRow label="Fees">{invoice.fees ? money(invoice.fees, { cents: true }) : null}</InfoRow>
                            <InfoRow label="Total Amount">{money(invoice.amount, { cents: true })}</InfoRow>
                            <InfoRow label="Amount Due">{money(invoice.amount_due, { cents: true })}</InfoRow>
                            <InfoRow label="Status">
                                <Badge>{invoice.status}</Badge>
                            </InfoRow>
                            <InfoRow label="Paid On">{invoice.paid_at ? fmtDate(invoice.paid_at, tz) : null}</InfoRow>
                        </Card>
                        <Card>
                            <CardHeader title="Notes" />
                            <p className="text-muted-foreground p-5 text-sm whitespace-pre-line">{invoice.notes || 'No notes.'}</p>
                        </Card>
                    </div>
                </div>
            )}
            {tab === 'customers' && (
                <DataTable
                    rows={invoice.account?.customers ?? []}
                    columns={[
                        {
                            key: 'name',
                            label: 'Name',
                            render: (c) => (
                                <Link className="font-semibold hover:underline" href={route('customers.show', c.id)}>
                                    {c.name} {c.id === invoice.won_by_customer_id && <Badge tone="won">Winner</Badge>}
                                </Link>
                            ),
                        },
                        { key: 'code', label: 'Customer Code', render: (c) => c.code ?? '—' },
                        { key: 'email', label: 'Email', render: (c) => c.email ?? '—' },
                        { key: 'phone', label: 'Phone', render: (c) => c.phone ?? '—' },
                    ]}
                    empty="No customers assigned to this invoice's account."
                />
            )}
            {tab === 'brokers' && (
                <DataTable
                    rows={invoice.account?.brokers ?? []}
                    columns={[
                        { key: 'name', label: 'Name', render: (b) => <span className="font-semibold">{b.name}</span> },
                        { key: 'email', label: 'Email', render: (b) => b.email ?? '—' },
                    ]}
                    empty="No brokers on this account."
                />
            )}
            {tab === 'bids' && <BidsTable rows={bids} />}
            {tab === 'activity' && <ActivityTable rows={activities} />}
            {editing && <InvoiceForm invoice={invoice} options={options} onClose={() => setEditing(false)} />}
        </AppLayout>
    );
}
