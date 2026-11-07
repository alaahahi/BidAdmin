import { fmtDate, fmtDateTime, money, titleCase, useTz } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Activity, Bid, Invoice, WatchItem } from '@/types';
import { Link } from '@inertiajs/react';
import { FileDown, Info } from 'lucide-react';
import type { Filters } from '@/types';
import { AccountChip, ProviderMark } from './provider';
import { DataTable, type Column, type ListState } from './table';
import { Badge, CarImage, LinkButton } from './ui';

export const ACTIVITY_LABELS: Record<string, string> = {
    login: 'Login',
    lot_view: 'Lot View',
    bid: 'Bid Placed',
    watchlist_add: 'Watchlist Add',
    watchlist_remove: 'Watchlist Remove',
    won: 'Car Won',
    credential_reveal: 'Credential Reveal',
};

const ACT_TONE: Record<string, string> = {
    login: 'bg-slate-100 text-slate-700',
    lot_view: 'bg-sky-50 text-sky-700',
    bid: 'bg-indigo-50 text-indigo-700',
    watchlist_add: 'bg-amber-50 text-amber-700',
    watchlist_remove: 'bg-gray-100 text-gray-600',
    won: 'bg-green-50 text-green-700',
    credential_reveal: 'bg-red-50 text-red-700',
};

export function ActivityBadge({ type }: { type: string }) {
    const t = useT();
    const label = ACTIVITY_LABELS[type] ?? titleCase(type);
    return <span className={`rounded px-1.5 py-0.5 text-[10px] font-semibold ${ACT_TONE[type] ?? 'bg-muted'}`}>{t(label)}</span>;
}

export function Vehicle({ description, sub, lot }: { description?: string | null; sub?: string | null; lot?: string | null }) {
    return (
        <div className="max-w-[260px] leading-tight">
            <div className="font-semibold uppercase">{description ?? '—'}</div>
            {sub && <div className="text-muted-foreground text-[10px]">{sub}</div>}
            {lot && <div className="text-muted-foreground text-[10px]">{lot}</div>}
        </div>
    );
}

export function useInvoiceColumns(opts: { showAccount?: boolean; showWonBy?: boolean } = {}): Column<Invoice>[] {
    const tz = useTz();
    const cols: Column<Invoice>[] = [
        { key: 'provider', label: 'Provider', sortKey: 'provider', render: (i) => <ProviderMark provider={i.provider} /> },
        {
            key: 'number',
            label: 'Lot/Invoice #',
            sortKey: 'invoice_number',
            render: (i) => (
                <Link href={route('invoices.show', i.id)} className="font-semibold hover:underline">
                    {i.invoice_number}
                </Link>
            ),
        },
        { key: 'date', label: 'Date', sortKey: 'invoice_date', render: (i) => <span className="whitespace-nowrap">{fmtDate(i.invoice_date, tz, 'long')}</span> },
    ];
    if (opts.showAccount !== false) cols.push({ key: 'accounts', label: 'Accounts', render: (i) => <AccountChip account={i.account} /> });
    cols.push(
        { key: 'type', label: 'Type', sortKey: 'type', render: (i) => <Badge tone="lot">{i.type}</Badge> },
        { key: 'status', label: 'Status', sortKey: 'status', render: (i) => <Badge>{i.status}</Badge> },
        { key: 'amount', label: 'Amount', sortKey: 'amount', render: (i) => money(i.amount) },
        { key: 'due', label: 'Due', sortKey: 'amount_due', render: (i) => <span className={Number(i.amount_due) > 0 ? 'font-semibold text-orange-600' : ''}>{money(i.amount_due)}</span> },
        { key: 'image', label: 'Image', render: (i) => <CarImage src={i.image_url} /> },
        { key: 'description', label: 'Description', render: (i) => <Vehicle description={i.description} sub={i.vin} lot={[i.invoice_number, i.location].filter(Boolean).join(' · ')} /> },
    );
    if (opts.showWonBy !== false)
        cols.push({
            key: 'won_by',
            label: 'Won By',
            render: (i) => (i.won_by ? <span className="font-medium">{i.won_by.name}</span> : <span className="text-muted-foreground">—</span>),
        });
    cols.push({
        key: 'actions',
        label: 'Actions',
        render: (i) => <InvoiceActions invoiceId={i.id} />,
    });
    return cols;
}

function InvoiceActions({ invoiceId }: { invoiceId: number }) {
    const t = useT();
    return (
        <div className="flex gap-1.5">
            <LinkButton href={route('invoices.show', invoiceId)}>{t('Details')}</LinkButton>
            <a href={route('invoices.pdf', invoiceId)} className="bg-card hover:bg-muted inline-flex h-8 items-center gap-1 rounded-md border px-2 text-xs" title={t('Download PDF')}>
                <FileDown className="size-3.5" /> PDF
            </a>
        </div>
    );
}

export function useBidColumns(opts: { showCustomer?: boolean; showAccount?: boolean } = {}): Column<Bid>[] {
    const tz = useTz();
    const cols: Column<Bid>[] = [
        { key: 'provider', label: 'Provider', sortKey: 'provider', render: (b) => <ProviderMark provider={b.provider} /> },
        { key: 'lot', label: 'Lot #', sortKey: 'lot_number', render: (b) => <span className="font-semibold">{b.lot_number}</span> },
        { key: 'date', label: 'Date/Time', sortKey: 'placed_at', render: (b) => <span className="whitespace-nowrap">{fmtDateTime(b.placed_at, tz)}</span> },
    ];
    if (opts.showAccount !== false) cols.push({ key: 'account', label: 'Account', render: (b) => <AccountChip account={b.account} /> });
    if (opts.showCustomer !== false) cols.push({ key: 'placed_by', label: 'Placed By', render: (b) => <span className="font-medium">{b.customer?.name ?? '—'}</span> });
    cols.push(
        { key: 'type', label: 'Type', sortKey: 'type', render: (b) => titleCase(b.type) },
        { key: 'phase', label: 'Phase', sortKey: 'phase', render: (b) => titleCase(b.phase) },
        {
            key: 'amount',
            label: 'Bid Amount',
            sortKey: 'amount',
            render: (b) => (
                <div>
                    <div className="font-semibold">{money(b.amount)}</div>
                    <Badge>{b.status}</Badge>
                </div>
            ),
        },
        { key: 'ip', label: 'IP Address', render: (b) => <code className="text-[11px]">{b.ip_address ?? '—'}</code> },
        { key: 'app', label: 'App', render: (b) => <span className="capitalize">{b.app ?? '—'}</span> },
    );
    return cols;
}

export function BidsTable({ rows, list, showCustomer, showAccount }: { rows: Bid[]; list?: ListState; showCustomer?: boolean; showAccount?: boolean }) {
    return <DataTable columns={useBidColumns({ showCustomer, showAccount })} rows={rows} list={list} />;
}

export function InvoicesTable({ rows, list, showAccount, showWonBy }: { rows: Invoice[]; list?: ListState; showAccount?: boolean; showWonBy?: boolean }) {
    return <DataTable columns={useInvoiceColumns({ showAccount, showWonBy })} rows={rows} list={list} />;
}

export function useActivityColumns(): Column<Activity>[] {
    const tz = useTz();
    return [
        { key: 'date', label: 'Date/Time', sortKey: 'created_at', render: (a) => <span className="whitespace-nowrap">{fmtDateTime(a.created_at, tz)}</span> },
        { key: 'type', label: 'Activity Type', sortKey: 'type', render: (a) => <ActivityBadge type={a.type} /> },
        { key: 'placed_by', label: 'Placed By', render: (a) => <span className="font-medium">{a.customer?.name ?? '—'}</span> },
        { key: 'account', label: 'Account', render: (a) => <AccountChip account={a.account} /> },
        {
            key: 'target',
            label: 'Target',
            render: (a) => (
                <div className="max-w-[240px] leading-tight">
                    <div className="font-semibold">{a.target ?? '—'}</div>
                    {a.description && <div className="text-muted-foreground truncate text-[10px]">{a.description}</div>}
                    {a.amount && <div className="text-[10px] font-semibold text-indigo-600">{money(a.amount)}</div>}
                </div>
            ),
        },
        { key: 'ip', label: 'IP Address', sortKey: 'ip_address', render: (a) => <code className="text-[11px]">{a.ip_address ?? '—'}</code> },
        { key: 'app', label: 'App', sortKey: 'app', render: (a) => <span className="capitalize">{a.app ?? '—'}</span> },
    ];
}

export function ActivityTable({ rows, list }: { rows: Activity[]; list?: ListState }) {
    return <DataTable columns={useActivityColumns()} rows={rows} list={list} />;
}

export function useWatchColumns(onRemove?: (w: WatchItem) => void): Column<WatchItem>[] {
    const tz = useTz();
    const cols: Column<WatchItem>[] = [
        { key: 'provider', label: 'Provider', sortKey: 'provider', render: (w) => <ProviderMark provider={w.provider} /> },
        { key: 'image', label: 'Image', render: (w) => <CarImage src={w.image_url} /> },
        { key: 'description', label: 'Description', sortKey: 'description', render: (w) => <Vehicle description={w.description} sub={w.vin} lot={w.lot_number} /> },
        {
            key: 'sale_status',
            label: 'Sale Status',
            sortKey: 'sale_status',
            render: (w) => (
                <div>
                    <div>{w.sale_status ?? '—'}</div>
                    <div className="text-muted-foreground text-[10px]">{fmtDateTime(w.sale_date, tz)}</div>
                </div>
            ),
        },
        { key: 'auction_status', label: 'Auction Status', sortKey: 'auction_status', render: (w) => <Badge tone={w.auction_status === 'Live' ? 'active' : w.auction_status === 'Ended' ? 'cancelled' : 'pending'}>{w.auction_status ?? '—'}</Badge> },
        { key: 'added', label: 'Added', sortKey: 'added_at', render: (w) => <span className="whitespace-nowrap">{fmtDateTime(w.added_at, tz)}</span> },
        { key: 'account', label: 'Account', render: (w) => <AccountChip account={w.account} /> },
        { key: 'customer', label: 'Customer', render: (w) => <span className="font-medium">{w.customer?.name ?? '—'}</span> },
    ];
    if (onRemove)
        cols.push({
            key: 'actions',
            label: 'Actions',
            render: (w) => (
                <button className="text-xs text-red-600 hover:underline" onClick={() => onRemove(w)}>
                    Remove
                </button>
            ),
        });
    return cols;
}

export function NinetyDays({ filters }: { filters: Filters }) {
    if (filters.from || filters.to) return null;
    return (
        <div className="mb-3 flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-200">
            <Info className="size-4" /> <b>Showing the last 90 days.</b> Select a date range to search further back.
        </div>
    );
}

