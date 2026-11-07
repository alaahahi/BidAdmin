import { AccountChip } from '@/components/ba/provider';
import { DataTable, FilterRow, ListToolbar, Pagination, useList, type Column } from '@/components/ba/table';
import { CheckList, ColumnsButton, Field, FilterSelect, Modal, ModalFooter, PageHeader, SearchBar, StatCard, TextInput, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { mask } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Account, Broker, Filters, Paginated } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';
import { Pencil, RotateCcw, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
    brokers: Paginated<Broker>;
    filters: Filters;
    stats: { active: number };
    allAccounts: Account[];
}

function BrokerForm({ broker, accounts, onClose }: { broker?: Broker; accounts: Account[]; onClose: () => void }) {
    const { data, setData, post, put, processing, errors } = useForm({
        name: broker?.name ?? '',
        email: broker?.email ?? '',
        covered: broker?.covered ?? false,
        account_ids: (broker?.accounts ?? []).map((a) => a.id),
    });
    return (
        <Modal open onClose={onClose} title={broker ? 'Edit Broker' : 'Add Broker'}>
            <form
                className="space-y-4"
                onSubmit={(e) => {
                    e.preventDefault();
                    const o = { onSuccess: onClose, preserveScroll: true };
                    if (broker) put(route('brokers.update', broker.id), o);
                    else post(route('brokers.store'), o);
                }}
            >
                <Field label="Name" error={errors.name}>
                    <TextInput value={data.name} onChange={(e) => setData('name', e.target.value)} placeholder="Enter a name for this broker" required />
                </Field>
                <Field label="Email" error={errors.email}>
                    <TextInput type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} placeholder="Enter broker's email address" />
                </Field>
                <label className="flex gap-2 text-sm">
                    <input type="checkbox" className="mt-1" checked={data.covered} onChange={(e) => setData('covered', e.target.checked)} />
                    <span>
                        <span className="font-medium">Cover this broker on your plan</span>
                        <span className="text-muted-foreground block text-xs">They pay nothing per car won, work only on the accounts you share, and can share their customers with you.</span>
                    </span>
                </label>
                <Field label="Shared accounts">
                    <CheckList items={accounts} selected={data.account_ids} onChange={(v) => setData('account_ids', v)} render={(a) => <AccountChip account={a} />} />
                </Field>
                <ModalFooter onCancel={onClose} processing={processing} submitLabel={broker ? 'Save' : 'Add Broker'} />
            </form>
        </Modal>
    );
}

export default function BrokersIndex({ brokers, filters, stats, allAccounts }: Props) {
    const t = useT();
    const list = useList('brokers', route('brokers.index'), filters);
    const [editing, setEditing] = useState<Broker | null | 'new'>(null);
    const trashed = filters.trashed === '1';
    const columns: Column<Broker>[] = [
        {
            key: 'name',
            label: 'Name',
            sortKey: 'name',
            render: (b) => (
                <span className="font-semibold">
                    {b.name} {b.covered && <span className="ms-1 rounded bg-green-50 px-1.5 py-0.5 text-[10px] text-green-700">Covered</span>}
                </span>
            ),
        },
        { key: 'email', label: 'Email', sortKey: 'email', render: (b) => mask(b.email, list.hideSensitive) || '—' },
        {
            key: 'accounts',
            label: 'Accounts',
            render: (b) => <div className="flex flex-wrap gap-1">{b.accounts?.length ? b.accounts.map((a) => <AccountChip key={a.id} account={a} />) : '—'}</div>,
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (b) =>
                trashed ? (
                    <ToolbarButton small icon={<RotateCcw />} onClick={() => router.post(route('brokers.restore', b.id))}>
                        Restore
                    </ToolbarButton>
                ) : (
                    <div className="flex gap-2">
                        <ToolbarButton icon={<Pencil />} onClick={() => setEditing(b)}>
                            Edit
                        </ToolbarButton>
                        <ToolbarButton danger icon={<Trash2 />} onClick={() => confirm(`Delete ${b.name}?`) && router.delete(route('brokers.destroy', b.id), { preserveScroll: true })} />
                    </div>
                ),
        },
    ];
    return (
        <AppLayout>
            <Head title="Brokers" />
            <PageHeader
                title={t('Brokers')}
                subtitle="Manage brokers and their associated accounts"
                actions={
                    <ListToolbar
                        list={list}
                        deleted
                        sensitive={false}
                        exportUrl={route('brokers.export', filters)}
                        extra={
                            <ToolbarButton primary onClick={() => setEditing('new')}>
                                {t('Add Broker')}
                            </ToolbarButton>
                        }
                    />
                }
            />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by name, email or accounts..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list}>
                <FilterSelect value={filters.account_id} onChange={(v) => list.apply({ account_id: v })} placeholder="All Accounts" options={allAccounts.map((a) => [a.id, `${a.username} (${a.provider.toUpperCase()} ${a.region})`])} />
            </FilterRow>
            <div className="mb-4 grid grid-cols-1 gap-3 md:grid-cols-3">
                <StatCard label="Active Brokers" value={stats.active} hint="Total count" />
            </div>
            <DataTable columns={columns} rows={brokers.data} list={list} />
            <Pagination page={brokers} list={list} />
            {editing && <BrokerForm broker={editing === 'new' ? undefined : editing} accounts={allAccounts} onClose={() => setEditing(null)} />}
        </AppLayout>
    );
}
