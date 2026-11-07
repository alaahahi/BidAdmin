import { AnalyticsView } from '@/components/ba/charts';
import { Flag, ProviderMark, StatusDot } from '@/components/ba/provider';
import { Pagination } from '@/components/ba/table';
import { BidsTable, InvoicesTable } from '@/components/ba/tables';
import { Card, CardHeader, CheckList, Field, InfoRow, Modal, ModalFooter, MultiStat, SelectInput, StatCard, Tabs, TagInput, TextArea, TextInput, ToolbarButton, Badge } from '@/components/ba/ui';
import { DataTable } from '@/components/ba/table';
import AppLayout from '@/layouts/app-layout';
import { fmtDate, fmtDateTime, money, useTz } from '@/lib/format';
import type { Account, AnalyticsData, Bid, Broker, Customer, Invoice, Paginated } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import axios from 'axios';
import { Copy, Eye, EyeOff, FileText, Pencil, Trash2, Upload } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { toast } from 'sonner';

interface Props {
    account: Account;
    tab: string;
    stats: Record<string, number>;
    options: { providers: Record<string, string>; regions: Record<string, string[]>; action_statuses: Record<string, string> };
    isAdmin: boolean;
    bids: Paginated<Bid>;
    invoices: Paginated<Invoice>;
    allCustomers?: Pick<Customer, 'id' | 'name' | 'code'>[];
    allBrokers?: Pick<Broker, 'id' | 'name' | 'email'>[];
    history?: { id: number; subject_type: string; subject_name: string; action: string; created_at: string; user?: { name: string } | null }[];
    documentGroups?: Record<string, string[]>;
    analytics?: AnalyticsData;
}

function EditAccount({ account, options, onClose }: { account: Account; options: Props['options']; onClose: () => void }) {
    const { data, setData, put, processing, errors } = useForm({
        provider: account.provider,
        region: account.region,
        username: account.username,
        password: '',
        type: account.type ?? '',
        category: account.category ?? '',
        company: account.company ?? '',
        account_number: account.account_number ?? '',
        external_id: account.external_id ?? '',
        email: account.email ?? '',
        phone: account.phone ?? '',
        address: account.address ?? '',
        balance_due: account.balance_due ?? '0',
        status: account.status,
        action_status: account.action_status,
        tags: account.tags ?? [],
        notes: account.notes ?? '',
        membership_plan: account.membership_plan ?? '',
        next_billing_date: account.next_billing_date?.slice(0, 10) ?? '',
    });
    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(route('accounts.update', account.id), { onSuccess: onClose, preserveScroll: true });
    };
    const text = (k: keyof typeof data, label: string, type = 'text') => (
        <Field label={label} error={errors[k]}>
            <TextInput type={type} value={String(data[k] ?? '')} onChange={(e) => setData(k, e.target.value as never)} />
        </Field>
    );
    return (
        <Modal open onClose={onClose} title="Edit Account" wide>
            <form onSubmit={submit} className="grid gap-4 md:grid-cols-2">
                <Field label="Provider">
                    <SelectInput value={data.provider} onChange={(e) => setData((d) => ({ ...d, provider: e.target.value, region: options.regions[e.target.value][0] }))} options={Object.entries(options.providers)} />
                </Field>
                <Field label="Region">
                    <SelectInput value={data.region} onChange={(e) => setData('region', e.target.value)} options={(options.regions[data.provider] ?? []).map((r) => [r, r])} />
                </Field>
                {text('username', 'Username')}
                <Field label="New password" hint="Leave empty to keep the current password" error={errors.password}>
                    <TextInput type="password" autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                </Field>
                {text('company', 'Company Name')}
                {text('account_number', 'Account Number')}
                {text('external_id', 'External ID')}
                {text('email', 'Email Address', 'email')}
                {text('phone', 'Phone Number')}
                {text('address', 'Address')}
                {text('type', 'Type')}
                {text('category', 'Category')}
                {text('balance_due', 'Balance Due', 'number')}
                <Field label="Status">
                    <SelectInput value={data.status} onChange={(e) => setData('status', e.target.value)} options={[['active', 'Active'], ['inactive', 'Inactive'], ['error', 'Error']]} />
                </Field>
                <Field label="Action Status">
                    <SelectInput value={data.action_status} onChange={(e) => setData('action_status', e.target.value)} options={Object.entries(options.action_statuses)} />
                </Field>
                <Field label="Tags">
                    <TagInput value={data.tags} onChange={(v) => setData('tags', v)} />
                </Field>
                {text('membership_plan', 'Subscription Plan')}
                {text('next_billing_date', 'Next Billing Date', 'date')}
                <Field label="Notes" className="md:col-span-2">
                    <TextArea value={data.notes} onChange={(e) => setData('notes', e.target.value)} />
                </Field>
                <div className="md:col-span-2">
                    <ModalFooter onCancel={onClose} processing={processing} />
                </div>
            </form>
        </Modal>
    );
}

function Credentials({ account, isAdmin }: { account: Account; isAdmin: boolean }) {
    const [pw, setPw] = useState<string | null>(null);
    const reveal = async () => {
        if (pw) return setPw(null);
        const r = await axios.post(route('accounts.reveal', account.id));
        setPw(r.data.password ?? '');
    };
    return (
        <Card className="max-w-2xl">
            <CardHeader title="Credentials" subtitle="Login used for this auction account. The password is stored encrypted." />
            <InfoRow label="Provider">
                <ProviderMark provider={account.provider} />
            </InfoRow>
            <InfoRow label="Username">{account.username}</InfoRow>
            <InfoRow label="Password">
                <span className="flex items-center gap-2">
                    <code className="text-sm">{pw ?? '••••••••••'}</code>
                    {isAdmin && (
                        <>
                            <ToolbarButton small icon={pw ? <EyeOff /> : <Eye />} onClick={reveal}>
                                {pw ? 'Hide' : 'Reveal'}
                            </ToolbarButton>
                            {pw && (
                                <ToolbarButton
                                    small
                                    icon={<Copy />}
                                    onClick={() => {
                                        navigator.clipboard?.writeText(pw);
                                        toast.success('Copied');
                                    }}
                                />
                            )}
                        </>
                    )}
                </span>
            </InfoRow>
            <p className="text-muted-foreground px-5 py-3 text-xs">Every reveal is written to the activity log. Customers never receive this password; they work through BidAdmin instead.</p>
        </Card>
    );
}

function Documents({ account, groups }: { account: Account; groups: Record<string, string[]> }) {
    const { data, setData, post, processing, errors, reset } = useForm<{ group: string; name: string; file: File | null }>({ group: 'Business', name: groups.Business?.[0] ?? '', file: null });
    const docs = account.documents ?? [];
    return (
        <div className="grid gap-4 lg:grid-cols-3">
            <div className="space-y-4 lg:col-span-2">
                {Object.entries(groups).map(([g, types]) => (
                    <Card key={g}>
                        <CardHeader title={g} subtitle={types.join(', ')} />
                        <div className="divide-y">
                            {docs.filter((d) => d.group === g).length === 0 && <p className="text-muted-foreground px-5 py-3 text-xs">No documents.</p>}
                            {docs
                                .filter((d) => d.group === g)
                                .map((d) => (
                                    <div key={d.id} className="flex items-center justify-between px-5 py-2.5 text-sm">
                                        <span className="flex items-center gap-2">
                                            <FileText className="size-4" /> {d.name}
                                            {!d.path && <Badge tone="pending">placeholder</Badge>}
                                        </span>
                                        <span className="flex gap-2">
                                            {d.path && (
                                                <a className="text-xs underline" href={d.url ?? '#'} target="_blank">
                                                    Open
                                                </a>
                                            )}
                                            <button className="text-xs text-red-600" onClick={() => confirm('Remove document?') && router.delete(route('accounts.documents.destroy', [account.id, d.id]), { preserveScroll: true })}>
                                                Remove
                                            </button>
                                        </span>
                                    </div>
                                ))}
                        </div>
                    </Card>
                ))}
            </div>
            <Card className="h-fit">
                <CardHeader title="Upload Document" />
                <form
                    className="space-y-3 p-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('accounts.documents.store', account.id), { forceFormData: true, onSuccess: () => reset('file'), preserveScroll: true });
                    }}
                >
                    <Field label="Group">
                        <SelectInput value={data.group} onChange={(e) => setData((d) => ({ ...d, group: e.target.value, name: groups[e.target.value][0] }))} options={Object.keys(groups).map((g) => [g, g])} />
                    </Field>
                    <Field label="Document type">
                        <SelectInput value={data.name} onChange={(e) => setData('name', e.target.value)} options={(groups[data.group] ?? []).map((t) => [t, t])} />
                    </Field>
                    <Field label="File" error={errors.file}>
                        <input type="file" onChange={(e) => setData('file', e.target.files?.[0] ?? null)} className="text-xs" />
                    </Field>
                    <ToolbarButton type="submit" primary icon={<Upload />} disabled={processing || !data.file}>
                        Upload
                    </ToolbarButton>
                </form>
            </Card>
        </div>
    );
}

function Assignment({ account, kind, all, history }: { account: Account; kind: 'customer' | 'broker'; all: { id: number; name: string; code?: string | null; email?: string | null }[]; history: Props['history'] }) {
    const current = ((kind === "customer" ? account.customers : account.brokers) ?? []) as (Customer & { pivot?: { assigned_at: string } })[];
    const [open, setOpen] = useState(false);
    const [sel, setSel] = useState<number[]>(current.map((c) => c.id));
    const tz = useTz();
    const save = () =>
        router.put(route(kind === 'customer' ? 'accounts.customers' : 'accounts.brokers', account.id), { [`${kind}_ids`]: sel }, { preserveScroll: true, onSuccess: () => setOpen(false) });
    const remove = (id: number) =>
        router.put(route(kind === 'customer' ? 'accounts.customers' : 'accounts.brokers', account.id), { [`${kind}_ids`]: current.map((c) => c.id).filter((x) => x !== id) }, { preserveScroll: true });
    return (
        <div className="space-y-4">
            <Card>
                <CardHeader
                    title={kind === 'customer' ? 'Assigned Customers' : 'Assigned Brokers'}
                    subtitle={kind === 'customer' ? 'Customers who can bid through this account. Every action is attributed to them.' : 'Brokers linked to this account'}
                    actions={
                        <ToolbarButton primary onClick={() => setOpen(true)}>
                            Manage {kind === 'customer' ? 'Customers' : 'Brokers'}
                        </ToolbarButton>
                    }
                />
                <DataTable
                    rows={current}
                    columns={[
                        {
                            key: 'name',
                            label: 'Name',
                            render: (c) =>
                                kind === 'customer' ? (
                                    <Link className="font-semibold hover:underline" href={route('customers.show', c.id)}>
                                        {c.name}
                                    </Link>
                                ) : (
                                    <span className="font-semibold">{c.name}</span>
                                ),
                        },
                        { key: 'code', label: kind === 'customer' ? 'Customer Code' : 'Email', render: (c) => ('code' in c ? (c as Customer).code : (c as Broker).email) ?? '—' },
                        { key: 'assigned', label: 'Assigned', render: (c) => fmtDateTime(c.pivot?.assigned_at, tz) },
                        {
                            key: 'actions',
                            label: 'Actions',
                            render: (c) => (
                                <button className="text-xs text-red-600 hover:underline" onClick={() => confirm('Unassign?') && remove(c.id)}>
                                    Unassign
                                </button>
                            ),
                        },
                    ]}
                />
            </Card>
            <Card>
                <CardHeader title="Assignment History" />
                <div className="divide-y">
                    {(history ?? []).filter((h) => h.subject_type === kind).length === 0 && <p className="text-muted-foreground px-5 py-3 text-xs">No history.</p>}
                    {(history ?? [])
                        .filter((h) => h.subject_type === kind)
                        .map((h) => (
                            <div key={h.id} className="flex items-center justify-between px-5 py-2.5 text-sm">
                                <span>
                                    <Badge tone={h.action === 'assigned' ? 'paid' : 'overdue'}>{h.action}</Badge> <span className="ms-2 font-medium">{h.subject_name}</span>
                                </span>
                                <span className="text-muted-foreground text-xs">
                                    {fmtDateTime(h.created_at, tz)} {h.user && `· by ${h.user.name}`}
                                </span>
                            </div>
                        ))}
                </div>
            </Card>
            <Modal open={open} onClose={() => setOpen(false)} title={`Assign ${kind === 'customer' ? 'Customers' : 'Brokers'}`}>
                <CheckList items={all} selected={sel} onChange={setSel} render={(i) => <span>{i.name} <span className="text-muted-foreground text-xs">{i.code ?? i.email}</span></span>} />
                <div className="flex justify-end gap-2">
                    <ToolbarButton onClick={() => setOpen(false)}>Cancel</ToolbarButton>
                    <ToolbarButton primary onClick={save}>
                        Save
                    </ToolbarButton>
                </div>
            </Modal>
        </div>
    );
}

function NoticeSettings({ account }: { account: Account }) {
    const { data, setData, put, processing } = useForm({
        login_notice_title: account.login_notice_title ?? '',
        login_notice_description: account.login_notice_description ?? '',
        login_notice_button: account.login_notice_button ?? '',
    });
    return (
        <Card className="max-w-2xl">
            <CardHeader title="Customer Login Notice" subtitle="Shown to customers when they open this account through BidAdmin." />
            <form
                className="space-y-4 p-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    put(route('accounts.settings', account.id), { preserveScroll: true });
                }}
            >
                <Field label="Title">
                    <TextInput value={data.login_notice_title} onChange={(e) => setData('login_notice_title', e.target.value)} placeholder="e.g. Read before bidding" />
                </Field>
                <Field label="Description">
                    <TextArea value={data.login_notice_description} onChange={(e) => setData('login_notice_description', e.target.value)} />
                </Field>
                <Field label="Button text">
                    <TextInput value={data.login_notice_button} onChange={(e) => setData('login_notice_button', e.target.value)} placeholder="I understand" />
                </Field>
                <ToolbarButton type="submit" primary disabled={processing}>
                    Save
                </ToolbarButton>
            </form>
        </Card>
    );
}

export default function AccountShow(props: Props) {
    const { account, tab, stats, isAdmin, options } = props;
    const tz = useTz();
    const [editing, setEditing] = useState(false);
    const tabs: [string, string][] = isAdmin
        ? [
              ['overview', 'Overview'],
              ['credentials', 'Credentials'],
              ['documents', 'Documents'],
              ['bids', 'Active Bids'],
              ['invoices', 'Invoices'],
              ['customers', 'Customers'],
              ['brokers', 'Brokers'],
              ['analytics', 'Analytics'],
              ['settings', 'Settings'],
          ]
        : [
              ['overview', 'Overview'],
              ['bids', 'My Bids'],
              ['analytics', 'Analytics'],
          ];

    return (
        <AppLayout>
            <Head title={account.username} />
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div className="flex items-start gap-3">
                    <StatusDot status={account.status} />
                    <div className="flex flex-col items-center gap-1">
                        <ProviderMark provider={account.provider} />
                        <Flag region={account.region} />
                    </div>
                    <div>
                        <h1 className="text-xl font-semibold">
                            {account.username} {account.external_id && <span className="text-muted-foreground font-normal">({account.external_id})</span>}
                        </h1>
                        <div className="text-muted-foreground flex items-center gap-2 text-[10px] uppercase">
                            {account.company} <Badge tone="paid">Synced</Badge>
                            {account.deleted_at && <Badge tone="overdue">Deleted</Badge>}
                        </div>
                        <div className="text-muted-foreground text-[10px] uppercase">{[account.type, account.category].filter(Boolean).join(' – ')}</div>
                        <div className="text-muted-foreground text-xs">Last synced: {fmtDateTime(account.last_synced_at, tz)}</div>
                    </div>
                </div>
                {isAdmin && !account.deleted_at && (
                    <ToolbarButton danger icon={<Trash2 />} onClick={() => confirm('Delete this account?') && router.delete(route('accounts.destroy', account.id))}>
                        Delete
                    </ToolbarButton>
                )}
            </div>
            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-6">
                <MultiStat title="Assigned" rows={[{ label: 'Customers', value: stats.customers }, { label: 'Brokers', value: stats.brokers }]} />
                <MultiStat
                    rows={[
                        { label: 'Total Invoices', value: stats.total_invoices },
                        { label: 'Paid Invoices', value: stats.paid_invoices, tone: 'green' },
                        { label: 'Pending Invoices', value: stats.pending_invoices, tone: 'orange' },
                    ]}
                />
                <StatCard label="Total Value" value={money(stats.total_value)} hint="Invoice amount" />
                <StatCard label="Unpaid" value={money(stats.unpaid)} hint="Unpaid invoices" tone="orange" />
                <StatCard label="Due Soon" value={money(stats.due_soon)} hint="Due within 3 days" tone="orange" />
                <StatCard label="Overdue" value={money(stats.overdue)} hint="Past due" tone="red" />
            </div>
            <Tabs tabs={tabs} current={tab} hrefFor={(k) => route('accounts.show', { id: account.id, tab: k })} />

            {tab === 'overview' && (
                <div className="grid gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <CardHeader
                                title="Account Information"
                                subtitle="Primary details about this account"
                                actions={
                                    isAdmin && (
                                        <ToolbarButton icon={<Pencil />} onClick={() => setEditing(true)}>
                                            Edit
                                        </ToolbarButton>
                                    )
                                }
                            />
                            <InfoRow label="Company Name">{account.company}</InfoRow>
                            <InfoRow label="Account Number">{account.account_number}</InfoRow>
                            <InfoRow label="External ID">{account.external_id}</InfoRow>
                            <InfoRow label="Email Address">{account.email}</InfoRow>
                            <InfoRow label="Phone Number">{account.phone}</InfoRow>
                            <InfoRow label="Address">{account.address}</InfoRow>
                            <InfoRow label="Region">{account.region}</InfoRow>
                            <InfoRow label="Type">
                                {account.type} <Badge className="ms-1 normal-case">Custom</Badge>
                            </InfoRow>
                            <InfoRow label="Category">
                                {account.category} <Badge className="ms-1 normal-case">Custom</Badge>
                            </InfoRow>
                            <InfoRow label="Action Status">{options.action_statuses[account.action_status]}</InfoRow>
                            <InfoRow label="Last Updated">{fmtDate(account.updated_at, tz)}</InfoRow>
                        </Card>
                        <Card>
                            <CardHeader title="Notes" />
                            <p className="text-muted-foreground p-5 text-sm whitespace-pre-line">{account.notes || 'No notes.'}</p>
                        </Card>
                    </div>
                    <Card className="h-fit">
                        <CardHeader title="Membership Details" subtitle="Subscription and billing information" />
                        <InfoRow label="Subscription Plan">{account.membership_plan}</InfoRow>
                        <InfoRow label="Next Billing Date">{account.next_billing_date ? fmtDate(account.next_billing_date, tz) : null}</InfoRow>
                    </Card>
                </div>
            )}
            {tab === 'credentials' && <Credentials account={account} isAdmin={isAdmin} />}
            {tab === 'documents' && props.documentGroups && <Documents account={account} groups={props.documentGroups} />}
            {tab === 'bids' && (
                <>
                    <BidsTable rows={props.bids.data} showAccount={false} />
                    <Pagination page={props.bids} pageParam="bids_page" />
                </>
            )}
            {tab === 'invoices' && (
                <>
                    <InvoicesTable rows={props.invoices.data} showAccount={false} />
                    <Pagination page={props.invoices} pageParam="inv_page" />
                </>
            )}
            {tab === 'customers' && props.allCustomers && <Assignment account={account} kind="customer" all={props.allCustomers} history={props.history} />}
            {tab === 'brokers' && props.allBrokers && <Assignment account={account} kind="broker" all={props.allBrokers} history={props.history} />}
            {tab === 'analytics' && props.analytics && <AnalyticsView data={props.analytics} periodLabel="Last 30D" />}
            {tab === 'settings' && <NoticeSettings account={account} />}
            {editing && <EditAccount account={account} options={options} onClose={() => setEditing(false)} />}
        </AppLayout>
    );
}
