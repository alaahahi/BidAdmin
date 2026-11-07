import { InvoiceCounts, ProviderCell } from '@/components/ba/provider';
import { DataTable, FilterRow, ListToolbar, Pagination, useList, type Column } from '@/components/ba/table';
import { ColumnsButton, FilterSelect, LinkButton, PageHeader, SearchBar, StatCard, Tags, ToolbarButton } from '@/components/ba/ui';
import AppLayout from '@/layouts/app-layout';
import { mask, money } from '@/lib/format';
import { useT } from '@/lib/i18n';
import type { Account, Filters, Paginated, SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { DollarSign, FileWarning, KeyRound, RotateCcw, Trash2 } from 'lucide-react';

interface Props {
    accounts: Paginated<Account>;
    filters: Filters;
    stats: { total: number; action_required: number; total_overdue: number; total_unpaid: number; unpaid_invoices: number };
    options: { providers: Record<string, string>; regions: Record<string, string[]>; tags: string[]; action_statuses: Record<string, string> };
}

export default function AccountsIndex({ accounts, filters, stats, options }: Props) {
    const t = useT();
    const isAdmin = usePage<SharedData>().props.auth.user.role === 'admin';
    const list = useList('accounts', route('accounts.index'), filters);
    const allRegions = Array.from(new Set(Object.values(options.regions).flat()));
    const trashed = filters.trashed === '1';

    const columns: Column<Account>[] = [
        { key: 'provider', label: 'Provider', sortKey: 'provider', render: (a) => <ProviderCell provider={a.provider} region={a.region} status={a.status} /> },
        { key: 'type', label: 'Type', sortKey: 'type', render: (a) => <span className="text-muted-foreground text-[11px] uppercase">{[a.type, a.category].filter(Boolean).join(' – ')}</span> },
        { key: 'username', label: 'Username', sortKey: 'username', render: (a) => <span className="font-semibold">{mask(a.username, list.hideSensitive)}</span> },
        { key: 'company', label: 'Company', sortKey: 'company', render: (a) => <span className="text-[11px] uppercase">{a.company}</span> },
        { key: 'balance', label: 'Balance Due', sortKey: 'balance_due', render: (a) => <span className="font-semibold">{money(a.balance_due)}</span> },
        { key: 'invoices', label: 'Invoices', render: (a) => <InvoiceCounts total={a.invoices_count} paid={a.paid_invoices_count} unpaid={a.unpaid_invoices_count} /> },
        {
            key: 'status',
            label: 'Status',
            render: (a) => (
                <div className="flex items-center gap-2" title={options.action_statuses[a.action_status]}>
                    <KeyRound className={`size-3.5 ${a.action_status === 'password_expired' ? 'text-red-500' : 'text-green-600'}`} />
                    <DollarSign className={`size-3.5 ${Number(a.balance_due) > 0 || a.action_status === 'payment_due' ? 'text-red-500' : 'text-muted-foreground'}`} />
                    <FileWarning className={`size-3.5 ${(a.unpaid_invoices_count ?? 0) > 0 ? 'text-red-500' : 'text-muted-foreground'}`} />
                </div>
            ),
        },
        { key: 'tags', label: 'Tags', render: (a) => <Tags tags={a.tags} /> },
        {
            key: 'actions',
            label: 'Actions',
            render: (a) => (
                <div className="flex items-center gap-2">
                    {trashed ? (
                        <ToolbarButton small icon={<RotateCcw />} onClick={() => router.post(route('accounts.restore', a.id))}>
                            {t('Restore')}
                        </ToolbarButton>
                    ) : (
                        <>
                            <LinkButton primary href={route('accounts.show', a.id)}>
                                {t('View Details')}
                            </LinkButton>
                            {isAdmin && (
                                <ToolbarButton
                                    danger
                                    small
                                    className="h-8"
                                    aria-label="Delete"
                                    onClick={() => confirm(`Delete account ${a.username}?`) && router.delete(route('accounts.destroy', a.id), { preserveScroll: true })}
                                    icon={<Trash2 />}
                                />
                            )}
                        </>
                    )}
                </div>
            ),
        },
    ];

    return (
        <AppLayout>
            <Head title="Accounts" />
            <PageHeader title="Accounts" subtitle="Manage auction house accounts and credentials" actions={<ListToolbar list={list} deleted={isAdmin} exportUrl={route('accounts.export', filters)} />} />
            <SearchBar value={list.search} onChange={list.setSearch} placeholder="Search by username, company, provider, region..." onManageColumns={<ColumnsButton columns={columns} hidden={list.hiddenCols} toggle={list.toggleCol} />} />
            <FilterRow list={list}>
                <FilterSelect value={filters.provider} onChange={(v) => list.apply({ provider: v })} placeholder="All Providers" options={Object.entries(options.providers)} />
                <FilterSelect value={filters.region} onChange={(v) => list.apply({ region: v })} placeholder="All Regions" options={allRegions.map((r) => [r, r])} />
                <FilterSelect value={filters.action_status} onChange={(v) => list.apply({ action_status: v })} placeholder="All Action Statuses" options={Object.entries(options.action_statuses)} />
                <FilterSelect value={filters.tag} onChange={(v) => list.apply({ tag: v })} placeholder="All Tags" options={options.tags.map((x) => [x, x])} />
            </FilterRow>
            <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-5">
                <StatCard label="Total Accounts" value={stats.total} hint="Total count" />
                <StatCard label="Action Required" value={stats.action_required} hint="Requiring immediate attention" tone="red" onClick={() => list.apply({ action_status: filters.action_status ? '' : 'payment_due' })} />
                <StatCard label="Total Overdue" value={money(stats.total_overdue)} hint="Requires immediate action" tone="red" />
                <StatCard label="Total Unpaid" value={money(stats.total_unpaid)} hint="Pending payment" tone="orange" />
                <StatCard label="Unpaid Invoices" value={stats.unpaid_invoices} hint="Requiring attention" />
            </div>
            <DataTable columns={columns} rows={accounts.data} list={list} />
            <Pagination page={accounts} list={list} />
        </AppLayout>
    );
}
