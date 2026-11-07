import { DataTable, Pagination, useList, type Column } from '@/components/ba/table';
import { Badge, Field, Modal, ModalFooter, PageHeader, SelectInput, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { fmtDate, useTz } from '@/lib/format';
import type { Account, Filters, Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Info, RefreshCw } from 'lucide-react';
import { useState } from 'react';

interface PhoneNumber {
    id: number;
    number: string;
    country: string;
    status: string;
    account_id: number | null;
    next_bill_at: string | null;
    monthly_price: string;
    account?: Account | null;
}

function BuyNumber({ accounts, onClose }: { accounts: Account[]; onClose: () => void }) {
    const { data, setData, post, processing } = useForm({ country: 'US', account_id: '' });
    return (
        <Modal open onClose={onClose} title="Buy Number" description="Get a phone number for SMS and OTP routing.">
            <form
                className="space-y-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    post(route('numbers.store'), { onSuccess: onClose });
                }}
            >
                <Field label="Country">
                    <SelectInput value={data.country} onChange={(e) => setData('country', e.target.value)} options={[['US', 'United States (+1)'], ['CA', 'Canada (+1)'], ['GB', 'United Kingdom (+44)']]} />
                </Field>
                <Field label="Assign to account">
                    <SelectInput value={data.account_id} onChange={(e) => setData('account_id', e.target.value)} placeholder="Unassigned" options={accounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()})`])} />
                </Field>
                <p className="text-muted-foreground flex gap-2 rounded-md bg-amber-50 p-2 text-xs text-amber-900">
                    <Info className="size-4 shrink-0" /> Demo only: this creates a placeholder number. No telephony provider is connected and nothing is charged.
                </p>
                <ModalFooter onCancel={onClose} processing={processing} submitLabel="Buy Number" />
            </form>
        </Modal>
    );
}

export default function Numbers({ numbers, filters, accounts }: { numbers: Paginated<PhoneNumber>; filters: Filters; accounts: Account[] }) {
    const tz = useTz();
    const list = useList('numbers', route('numbers.index'), filters);
    const [buying, setBuying] = useState(false);
    const columns: Column<PhoneNumber>[] = [
        { key: 'number', label: 'Numbers', sortKey: 'number', render: (n) => <span className="font-semibold">{n.number}</span> },
        { key: 'status', label: 'Status', sortKey: 'status', render: (n) => <Badge>{n.status}</Badge> },
        {
            key: 'assigned',
            label: 'Assigned To',
            render: (n) => (
                <select
                    value={n.account_id ?? ''}
                    onChange={(e) => router.put(route('numbers.update', n.id), { account_id: e.target.value || null }, { preserveScroll: true })}
                    className="bg-card h-8 rounded-md border px-2 text-xs"
                >
                    <option value="">Unassigned</option>
                    {accounts.map((a) => (
                        <option key={a.id} value={a.id}>
                            {a.username} ({a.provider.toUpperCase()})
                        </option>
                    ))}
                </select>
            ),
        },
        { key: 'next', label: 'Next Bill', sortKey: 'next_bill_at', render: (n) => fmtDate(n.next_bill_at, tz) },
        {
            key: 'actions',
            label: 'Actions',
            render: (n) => (
                <div className="flex gap-2">
                    <ToolbarButton small onClick={() => router.put(route('numbers.update', n.id), { status: n.status === 'active' ? 'paused' : 'active', account_id: n.account_id }, { preserveScroll: true })}>
                        {n.status === 'active' ? 'Pause' : 'Resume'}
                    </ToolbarButton>
                    <ToolbarButton small danger onClick={() => confirm('Release this number?') && router.delete(route('numbers.destroy', n.id), { preserveScroll: true })}>
                        Release
                    </ToolbarButton>
                </div>
            ),
        },
    ];
    return (
        <AppLayout>
            <Head title="Numbers" />
            <PageHeader
                title="Numbers"
                subtitle="Buy and manage phone numbers for SMS and OTP routing."
                actions={
                    <>
                        <ToolbarButton icon={<RefreshCw />} onClick={list.refresh}>
                            Hard Refresh
                        </ToolbarButton>
                        <ToolbarButton primary onClick={() => setBuying(true)}>
                            Buy Number
                        </ToolbarButton>
                    </>
                }
            />
            <DataTable columns={columns} rows={numbers.data} list={list} />
            <Pagination page={numbers} list={list} />
            {buying && <BuyNumber accounts={accounts} onClose={() => setBuying(false)} />}
        </AppLayout>
    );
}
