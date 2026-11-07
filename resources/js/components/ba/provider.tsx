import { PROVIDERS, REGION_FLAGS } from '@/lib/format';

const STYLES: Record<string, string> = {
    copart: 'bg-[#1d4ed8] text-[#facc15]',
    iaai: 'bg-[#dc2626] text-white',
    manheim: 'bg-[#0f172a] text-white',
    edgepipeline: 'bg-[#059669] text-white',
    npa: 'bg-[#7c3aed] text-white',
};

/** Generic text badge for an auction provider (not the provider's trademark logo). */
export function ProviderMark({ provider, size = 'sm' }: { provider: string | null | undefined; size?: 'sm' | 'lg' }) {
    if (!provider) return <span className="text-muted-foreground">—</span>;
    const label = PROVIDERS[provider] ?? provider;
    return (
        <span
            className={`inline-flex items-center justify-center rounded font-bold tracking-tight italic ${STYLES[provider] ?? 'bg-gray-200 text-gray-800'} ${
                size === 'lg' ? 'px-3 py-1.5 text-sm' : 'px-1.5 py-0.5 text-[10px]'
            }`}
        >
            {label}
        </span>
    );
}

export function Flag({ region }: { region: string | null | undefined }) {
    if (!region) return null;
    return (
        <span title={region} className="text-sm leading-none">
            {REGION_FLAGS[region] ?? region}
        </span>
    );
}

export function StatusDot({ status }: { status: string }) {
    const color = status === 'active' ? 'bg-green-500' : status === 'error' ? 'bg-red-500' : 'bg-gray-400';
    return <span className={`inline-block size-2 rounded-full ${color}`} title={status} />;
}

/** Provider + status dot + flag, as in the accounts table. */
export function ProviderCell({ provider, region, status = 'active' }: { provider: string | null; region?: string | null; status?: string }) {
    return (
        <div className="flex items-center gap-2">
            <StatusDot status={status} />
            <div className="flex flex-col items-start gap-1">
                <ProviderMark provider={provider} />
                <Flag region={region} />
            </div>
        </div>
    );
}

export function AccountChip({ account }: { account?: { username: string; provider: string; region?: string; status?: string } | null }) {
    if (!account) return <span className="text-muted-foreground">—</span>;
    return (
        <span className="bg-muted/70 inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-[11px] font-medium whitespace-nowrap">
            <StatusDot status={account.status ?? 'active'} />
            <ProviderMark provider={account.provider} />
            <Flag region={account.region} />
            {account.username}
        </span>
    );
}

export function InvoiceCounts({ total, paid, unpaid }: { total?: number; paid?: number; unpaid?: number }) {
    return (
        <div className="flex gap-1">
            <span className="rounded border px-1.5 py-0.5 text-[10px] font-semibold">{total ?? 0}</span>
            <span className="rounded border border-green-300 bg-green-50 px-1.5 py-0.5 text-[10px] font-semibold text-green-700">{paid ?? 0}</span>
            <span className="rounded border border-orange-300 bg-orange-50 px-1.5 py-0.5 text-[10px] font-semibold text-orange-600">{unpaid ?? 0}</span>
        </div>
    );
}

