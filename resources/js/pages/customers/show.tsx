import { AnalyticsView } from '@/components/ba/charts';
import CustomerForm from '@/components/ba/customer-form';
import { AccountChip } from '@/components/ba/provider';
import { DataTable, Pagination } from '@/components/ba/table';
import { BidsTable, InvoicesTable, useWatchColumns } from '@/components/ba/tables';
import { Badge, Card, CardHeader, CheckList, Field, InfoRow, Modal, StatCard, Tabs, Tags, TextArea, TextInput, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { fmtDateTime, money, useTz } from '@/lib/format';
import type { Account, AnalyticsData, Bid, Customer, Invoice, Paginated, WatchItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Copy, Eye, EyeOff, KeyRound, Pencil, Trash2 } from 'lucide-react';
import { useState } from 'react';

interface Props {
    customer: Customer;
    tab: string;
    stats: Record<string, number>;
    allAccounts: Account[];
    invoices: Paginated<Invoice>;
    bids: Paginated<Bid>;
    watchlist: Paginated<WatchItem>;
    tokens: { id: number; name: string; last_used_at: string | null; created_at: string; expires_at: string | null }[];
    analytics?: AnalyticsData;
}

function AppLoginCodeCard({ customer }: { customer: Customer }) {
    const [show, setShow] = useState(false);
    const code = customer.login_code ?? '';
    const masked = code ? '••••' + code.slice(-4) : '—';
    return (
        <Card>
            <CardHeader title="App Login Code" subtitle="8-character code for the Flutter customer app. Keep it private." />
            <div className="space-y-4 p-5">
                <div className="flex flex-wrap items-center gap-3">
                    <code className="bg-muted rounded px-3 py-2 text-lg tracking-widest">{show ? code || '—' : masked}</code>
                    <ToolbarButton small icon={show ? <EyeOff /> : <Eye />} onClick={() => setShow((v) => !v)} disabled={!code}>
                        {show ? 'Hide' : 'Show'}
                    </ToolbarButton>
                    {show && code && (
                        <ToolbarButton
                            small
                            icon={<Copy />}
                            onClick={() => {
                                navigator.clipboard?.writeText(code);
                            }}
                        >
                            Copy
                        </ToolbarButton>
                    )}
                    <ToolbarButton
                        primary
                        onClick={() => confirm('Regenerate app login code? Existing app sessions keep working until tokens expire.') && router.post(route('customers.login-code', customer.id), {}, { preserveScroll: true })}
                    >
                        Regenerate
                    </ToolbarButton>
                </div>
            </div>
        </Card>
    );
}

function CredentialsTab({ customer, tokens }: { customer: Customer; tokens: Props['tokens'] }) {
    const tz = useTz();
    const { data, setData, put, processing, errors } = useForm({ email: customer.user?.email ?? customer.email ?? '', password: '' });
    const [tokenName, setTokenName] = useState('browser-extension');
    return (
        <div className="grid gap-4 lg:grid-cols-2">
            <AppLoginCodeCard customer={customer} />
            <Card>
                <CardHeader title="Portal Login" subtitle="The customer signs in to BidAdmin with this email and sees only their own accounts, bids, watchlist and invoices." />
                <form
                    className="space-y-4 p-5"
                    onSubmit={(e) => {
                        e.preventDefault();
                        put(route('customers.credentials', customer.id), { preserveScroll: true, onSuccess: () => setData('password', '') });
                    }}
                >
                    <Field label="Login email" error={errors.email}>
                        <TextInput type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    </Field>
                    <Field label={customer.user ? 'New password (optional)' : 'Password'} error={errors.password}>
                        <TextInput type="password" autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} />
                    </Field>
                    <ToolbarButton type="submit" primary disabled={processing}>
                        {customer.user ? 'Update Login' : 'Create Login'}
                    </ToolbarButton>
                </form>
            </Card>
            <Card className="lg:col-span-2">
                <CardHeader title="API Tokens" subtitle="For the browser extension. Events sent with this token are attributed to this customer, with the request IP." />
                <div className="space-y-3 p-5">
                    {!customer.user ? (
                        <p className="text-muted-foreground text-sm">Create a portal login first.</p>
                    ) : (
                        <div className="flex gap-2">
                            <TextInput value={tokenName} onChange={(e) => setTokenName(e.target.value)} />
                            <ToolbarButton primary icon={<KeyRound />} onClick={() => router.post(route('customers.tokens.store', customer.id), { name: tokenName }, { preserveScroll: true })}>
                                Generate
                            </ToolbarButton>
                        </div>
                    )}
                    <div className="divide-y rounded-md border">
                        {tokens.length === 0 && <p className="text-muted-foreground p-3 text-xs">No tokens.</p>}
                        {tokens.map((t) => (
                            <div key={t.id} className="flex items-center justify-between p-3 text-sm">
                                <div>
                                    <div className="font-medium">{t.name}</div>
                                    <div className="text-muted-foreground text-xs">
                                        Created {fmtDateTime(t.created_at, tz)} · Last used {t.last_used_at ? fmtDateTime(t.last_used_at, tz) : 'never'}
                                    </div>
                                </div>
                                <button className="text-xs text-red-600" onClick={() => confirm('Revoke token?') && router.delete(route('customers.tokens.destroy', [customer.id, t.id]), { preserveScroll: true })}>
                                    Revoke
                                </button>
                            </div>
                        ))}
                    </div>
                </div>
            </Card>
        </div>
    );
}

function AccountsTab({ customer, all }: { customer: Customer; all: Account[] }) {
    const [open, setOpen] = useState(false);
    const [sel, setSel] = useState<number[]>((customer.accounts ?? []).map((a) => a.id));
    const tz = useTz();
    return (
        <Card>
            <CardHeader
                title="Assigned Accounts"
                subtitle="Auction accounts this customer can bid through"
                actions={
                    <ToolbarButton primary onClick={() => setOpen(true)}>
                        Manage Accounts
                    </ToolbarButton>
                }
            />
            <DataTable
                rows={customer.accounts ?? []}
                columns={[
                    { key: 'account', label: 'Account', render: (a) => <AccountChip account={a} /> },
                    { key: 'company', label: 'Company', render: (a) => a.company ?? '—' },
                    { key: 'assigned', label: 'Assigned', render: (a) => fmtDateTime((a as Account & { pivot?: { assigned_at: string } }).pivot?.assigned_at, tz) },
                    {
                        key: 'actions',
                        label: 'Actions',
                        render: (a) => (
                            <Link className="text-xs underline" href={route('accounts.show', a.id)}>
                                Open
                            </Link>
                        ),
                    },
                ]}
            />
            <Modal open={open} onClose={() => setOpen(false)} title="Assign Accounts">
                <CheckList items={all} selected={sel} onChange={setSel} render={(a) => <AccountChip account={a} />} />
                <div className="flex justify-end gap-2">
                    <ToolbarButton onClick={() => setOpen(false)}>Cancel</ToolbarButton>
                    <ToolbarButton primary onClick={() => router.put(route('customers.accounts', customer.id), { account_ids: sel }, { preserveScroll: true, onSuccess: () => setOpen(false) })}>
                        Save
                    </ToolbarButton>
                </div>
            </Modal>
        </Card>
    );
}

function SettingsTab({ customer }: { customer: Customer }) {
    const { data, setData, put, processing } = useForm({
        name: customer.name,
        can_bid: customer.can_bid,
        login_notice_title: customer.login_notice_title ?? '',
        login_notice_description: customer.login_notice_description ?? '',
    });
    return (
        <Card className="max-w-2xl">
            <CardHeader title="Customer Settings" />
            <form
                className="space-y-4 p-5"
                onSubmit={(e) => {
                    e.preventDefault();
                    put(route('customers.update', customer.id), { preserveScroll: true });
                }}
            >
                <label className="flex items-center gap-2 text-sm">
                    <input type="checkbox" checked={data.can_bid} onChange={(e) => setData('can_bid', e.target.checked)} /> Allow this customer to place bids (API rejects bids when off)
                </label>
                <Field label="Login notice title">
                    <TextInput value={data.login_notice_title} onChange={(e) => setData('login_notice_title', e.target.value)} />
                </Field>
                <Field label="Login notice description">
                    <TextArea value={data.login_notice_description} onChange={(e) => setData('login_notice_description', e.target.value)} />
                </Field>
                <ToolbarButton type="submit" primary disabled={processing}>
                    Save
                </ToolbarButton>
            </form>
        </Card>
    );
}

export default function CustomerShow(props: Props) {
    const { customer, tab, stats } = props;
    const [editing, setEditing] = useState(false);
    const tabs: [string, string][] = [
        ['details', 'Details'],
        ['credentials', 'Credentials'],
        ['accounts', 'Accounts'],
        ['invoices', 'Invoices'],
        ['bids', 'Bids'],
        ['watchlist', 'Watchlist'],
        ['analytics', 'Analytics'],
        ['settings', 'Settings'],
    ];
    const watchCols = useWatchColumns();
    return (
        <AppLayout>
            <Head title={customer.name} />
            <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h1 className="text-xl font-semibold uppercase">{customer.name}</h1>
                    <div className="text-muted-foreground flex items-center gap-2 text-xs">
                        {customer.code} {customer.user ? <Badge tone="paid">Portal login</Badge> : <Badge>No login</Badge>}
                        {customer.deleted_at && <Badge tone="overdue">Deleted</Badge>}
                    </div>
                </div>
                <div className="flex gap-2">
                    <ToolbarButton icon={<Pencil />} onClick={() => setEditing(true)}>
                        Edit
                    </ToolbarButton>
                    {!customer.deleted_at && (
                        <ToolbarButton danger icon={<Trash2 />} onClick={() => confirm('Delete this customer?') && router.delete(route('customers.destroy', customer.id))}>
                            Delete
                        </ToolbarButton>
                    )}
                </div>
            </div>
            <div className="mb-5 grid grid-cols-2 gap-3 md:grid-cols-6">
                <StatCard label="Accounts" value={stats.accounts} hint="Assigned" />
                <StatCard label="Bids" value={stats.bids} hint="All time" />
                <StatCard label="Cars Won" value={stats.won} hint="Invoices won" tone="green" />
                <StatCard label="Invoices" value={stats.invoices} hint="Total" />
                <StatCard label="Unpaid" value={money(stats.unpaid)} hint="Outstanding" tone="orange" />
                <StatCard label="Watchlist" value={stats.watchlist} hint="Vehicles" />
            </div>
            <Tabs tabs={tabs} current={tab} hrefFor={(k) => route('customers.show', { id: customer.id, tab: k })} />
            {tab === 'details' && (
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader title="Customer Information" />
                        <InfoRow label="Name">{customer.name}</InfoRow>
                        <InfoRow label="Customer Code">{customer.code}</InfoRow>
                        <InfoRow label="Email">{customer.email}</InfoRow>
                        <InfoRow label="Phone Number">{customer.phone ? `${customer.phone_code ?? ''} ${customer.phone}` : null}</InfoRow>
                        <InfoRow label="Address">{customer.address}</InfoRow>
                        <InfoRow label="Tags">
                            <Tags tags={customer.tags} />
                        </InfoRow>
                        <InfoRow label="Notes">{customer.notes}</InfoRow>
                    </Card>
                    <Card className="h-fit">
                        <CardHeader title="Bid Limits" />
                        <InfoRow label="Max bid amount">{customer.bid_limit ? money(customer.bid_limit) : 'No limit'}</InfoRow>
                        <InfoRow label="Max cars">{customer.car_limit ?? 'No limit'}</InfoRow>
                        <InfoRow label="Credit limit">{customer.credit_limit ? money(customer.credit_limit) : 'No limit'}</InfoRow>
                        <InfoRow label="Bidding">{customer.can_bid ? 'Allowed' : 'Blocked'}</InfoRow>
                    </Card>
                </div>
            )}
            {tab === 'credentials' && <CredentialsTab customer={customer} tokens={props.tokens} />}
            {tab === 'accounts' && <AccountsTab customer={customer} all={props.allAccounts} />}
            {tab === 'invoices' && (
                <>
                    <InvoicesTable rows={props.invoices.data} showWonBy={false} />
                    <Pagination page={props.invoices} pageParam="inv_page" />
                </>
            )}
            {tab === 'bids' && (
                <>
                    <BidsTable rows={props.bids.data} showCustomer={false} />
                    <Pagination page={props.bids} pageParam="bids_page" />
                </>
            )}
            {tab === 'watchlist' && (
                <>
                    <DataTable rows={props.watchlist.data} columns={watchCols.filter((c) => c.key !== 'customer')} />
                    <Pagination page={props.watchlist} pageParam="wl_page" />
                </>
            )}
            {tab === 'analytics' && props.analytics && <AnalyticsView data={props.analytics} periodLabel="Last 30D" />}
            {tab === 'settings' && <SettingsTab customer={customer} />}
            {editing && <CustomerForm customer={customer} full onClose={() => setEditing(false)} />}
        </AppLayout>
    );
}
