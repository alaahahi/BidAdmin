import { Field, Modal, ModalFooter, TagInput, TextArea, TextInput } from '@/components/ba/ui';
import type { Customer } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export const PHONE_CODES = ['+964', '+1', '+44', '+971', '+90', '+49', '+966', '+962', '+961', '+20'];

export default function CustomerForm({ customer, onClose, full }: { customer?: Customer; onClose: () => void; full?: boolean }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: customer?.name ?? '',
        code: customer?.code ?? '',
        email: customer?.email ?? '',
        phone_code: customer?.phone_code ?? '+964',
        phone: customer?.phone ?? '',
        address: customer?.address ?? '',
        bid_limit: customer?.bid_limit ?? '',
        car_limit: customer?.car_limit ?? '',
        credit_limit: customer?.credit_limit ?? '',
        tags: customer?.tags ?? [],
        notes: customer?.notes ?? '',
    });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        const opts = { onSuccess: onClose, preserveScroll: true };
        if (customer) put(route('customers.update', customer.id), opts);
        else post(route('customers.store'), opts);
    };
    return (
        <Modal open onClose={onClose} title={customer ? 'Edit Customer' : 'Add Customer'} description={customer ? undefined : 'Create a customer to assign auction accounts to.'} wide={full}>
            <form onSubmit={submit} className={full ? 'grid gap-4 md:grid-cols-2' : 'space-y-4'}>
                <Field label="Name" error={errors.name}>
                    <TextInput value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Full name" required />
                </Field>
                <Field label="Email" error={errors.email}>
                    <TextInput type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="name@example.com" />
                </Field>
                <Field label="Phone Number" error={errors.phone}>
                    <div className="flex gap-2">
                        <select value={data.phone_code} onChange={(e) => setData('phone_code', e.target.value)} className="bg-card h-9 rounded-md border px-2 text-sm">
                            {PHONE_CODES.map((c) => (
                                <option key={c}>{c}</option>
                            ))}
                        </select>
                        <TextInput value={data.phone} onChange={(e) => setData('phone', e.target.value)} placeholder="7501234567" />
                    </div>
                </Field>
                <Field label="Address" error={errors.address}>
                    <TextInput value={data.address} onChange={(e) => setData('address', e.target.value)} placeholder="City, Country" />
                </Field>
                {full && (
                    <>
                        <Field label="Customer Code" hint="Leave empty to auto-generate" error={errors.code}>
                            <TextInput value={data.code} onChange={(e) => setData('code', e.target.value)} />
                        </Field>
                        <Field label="Max bid amount ($)" error={errors.bid_limit}>
                            <TextInput type="number" value={String(data.bid_limit ?? '')} onChange={(e) => setData('bid_limit', e.target.value)} />
                        </Field>
                        <Field label="Max cars" error={errors.car_limit}>
                            <TextInput type="number" value={String(data.car_limit ?? '')} onChange={(e) => setData('car_limit', e.target.value)} />
                        </Field>
                        <Field label="Credit limit ($)" error={errors.credit_limit}>
                            <TextInput type="number" value={String(data.credit_limit ?? '')} onChange={(e) => setData('credit_limit', e.target.value)} />
                        </Field>
                        <Field label="Tags">
                            <TagInput value={data.tags} onChange={(v) => setData('tags', v)} />
                        </Field>
                        <Field label="Notes" className="md:col-span-2">
                            <TextArea value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                        </Field>
                    </>
                )}
                <div className={full ? 'md:col-span-2' : ''}>
                    <ModalFooter onCancel={onClose} processing={processing} submitLabel={customer ? 'Save' : 'Add Customer'} />
                </div>
            </form>
        </Modal>
    );
}
