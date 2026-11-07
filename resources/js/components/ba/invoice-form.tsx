import type { Account, Customer, Invoice } from '@/types';
import { useForm } from '@inertiajs/react';
import { Field, Modal, ModalFooter, SelectInput, TextArea, TextInput } from './ui';

export interface InvoiceOptions {
    types: Record<string, string>;
    providers: Record<string, string>;
    accounts: Account[];
    customers: Pick<Customer, 'id' | 'name' | 'code'>[];
}

export default function InvoiceForm({ invoice, options, onClose }: { invoice?: Invoice; options: InvoiceOptions; onClose: () => void }) {
    const { data, setData, post, put, processing, errors } = useForm({
        account_id: invoice?.account_id ?? '',
        provider: invoice?.provider ?? '',
        invoice_number: invoice?.invoice_number ?? '',
        vin: invoice?.vin ?? '',
        description: invoice?.description ?? '',
        location: invoice?.location ?? '',
        image_url: invoice?.image_url ?? '',
        type: invoice?.type ?? 'lot',
        status: invoice?.status ?? 'pending',
        amount: invoice?.amount ?? '',
        amount_due: invoice?.amount_due ?? '',
        sale_price: invoice?.sale_price ?? '',
        fees: invoice?.fees ?? '',
        invoice_date: invoice?.invoice_date?.slice(0, 10) ?? new Date().toISOString().slice(0, 10),
        due_date: invoice?.due_date?.slice(0, 10) ?? '',
        won_by_customer_id: invoice?.won_by_customer_id ?? '',
        notes: invoice?.notes ?? '',
    });
    const set = (k: keyof typeof data) => (e: { target: { value: string } }) => setData(k, e.target.value as never);
    return (
        <Modal open onClose={onClose} title={invoice ? 'Edit Invoice' : 'New Invoice'} wide>
            <form
                className="grid gap-4 md:grid-cols-3"
                onSubmit={(e) => {
                    e.preventDefault();
                    const o = { onSuccess: onClose, preserveScroll: true };
                    if (invoice) put(route('invoices.update', invoice.id), o);
                    else post(route('invoices.store'), o);
                }}
            >
                <Field label="Lot / Invoice #" error={errors.invoice_number}>
                    <TextInput value={data.invoice_number} onChange={set('invoice_number')} required />
                </Field>
                <Field label="Account" error={errors.account_id}>
                    <SelectInput value={data.account_id} onChange={set('account_id')} placeholder="—" options={options.accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
                </Field>
                <Field label="Provider" hint="Defaults to the account's provider">
                    <SelectInput value={data.provider} onChange={set('provider')} placeholder="—" options={Object.entries(options.providers)} />
                </Field>
                <Field label="Type">
                    <SelectInput value={data.type} onChange={set('type')} options={Object.entries(options.types)} />
                </Field>
                <Field label="Status">
                    <SelectInput value={data.status} onChange={set('status')} options={[['pending', 'Pending'], ['paid', 'Paid'], ['overdue', 'Overdue']]} />
                </Field>
                <Field label="Won By" hint="Leave empty to auto-set from the winning bid on this lot" error={errors.won_by_customer_id}>
                    <SelectInput value={data.won_by_customer_id} onChange={set('won_by_customer_id')} placeholder="Auto (from bids)" options={options.customers.map((c) => [c.id, c.name])} />
                </Field>
                <Field label="Amount" error={errors.amount}>
                    <TextInput type="number" step="0.01" value={data.amount} onChange={set('amount')} required />
                </Field>
                <Field label="Amount Due" hint="Defaults to amount (0 if paid)">
                    <TextInput type="number" step="0.01" value={data.amount_due} onChange={set('amount_due')} />
                </Field>
                <Field label="Sale Price">
                    <TextInput type="number" step="0.01" value={data.sale_price} onChange={set('sale_price')} />
                </Field>
                <Field label="Fees">
                    <TextInput type="number" step="0.01" value={data.fees} onChange={set('fees')} />
                </Field>
                <Field label="Invoice Date">
                    <TextInput type="date" value={data.invoice_date} onChange={set('invoice_date')} />
                </Field>
                <Field label="Due Date">
                    <TextInput type="date" value={data.due_date} onChange={set('due_date')} />
                </Field>
                <Field label="Description" className="md:col-span-2">
                    <TextInput value={data.description} onChange={set('description')} placeholder="2025 TOYOTA CAMRY LE" />
                </Field>
                <Field label="VIN">
                    <TextInput value={data.vin} onChange={set('vin')} />
                </Field>
                <Field label="Location">
                    <TextInput value={data.location} onChange={set('location')} placeholder="TX - DALLAS" />
                </Field>
                <Field label="Image URL" className="md:col-span-2" error={errors.image_url}>
                    <TextInput value={data.image_url} onChange={set('image_url')} placeholder="https://..." />
                </Field>
                <Field label="Notes" className="md:col-span-3">
                    <TextArea value={data.notes} onChange={set('notes')} />
                </Field>
                <div className="md:col-span-3">
                    <ModalFooter onCancel={onClose} processing={processing} submitLabel={invoice ? 'Save' : 'Create Invoice'} />
                </div>
            </form>
        </Modal>
    );
}
