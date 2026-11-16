import { Card, CardHeader, PageHeader, ToolbarButton } from '@/components/ba/ui';
import SuperLayout from '@/layouts/super-layout';
import { useT } from '@/lib/i18n';
import type { SharedData } from '@/types';
import { router, usePage } from '@inertiajs/react';
import { Download, Play, RefreshCw, Trash2 } from 'lucide-react';
import { useMemo, useState } from 'react';

interface MigrationBucket {
    ran: number;
    pending: number;
    last: string | null;
    tenant_count?: number;
}

interface MigrationStatus {
    separate_databases: boolean;
    central: MigrationBucket;
    tenant: MigrationBucket;
}

interface LogFileMeta {
    name: string;
    path: string;
    size: number;
    modified_at: string | null;
}

interface LogPayload {
    file: string;
    exists: boolean;
    size: number;
    lines: string[];
    truncated: boolean;
}

function formatBytes(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

export default function SystemSettingsPage({
    migrationStatus,
    logFiles,
    log,
    lines,
}: {
    migrationStatus: MigrationStatus;
    logFiles: LogFileMeta[];
    log: LogPayload;
    lines: number;
}) {
    const t = useT();
    const { flash } = usePage<SharedData>().props;
    const [busy, setBusy] = useState<'central' | 'tenant' | 'clear' | null>(null);
    const [selectedFile, setSelectedFile] = useState(log.file || 'laravel.log');
    const [lineCount, setLineCount] = useState(String(lines || 300));

    const logText = useMemo(() => log.lines.join('\n'), [log.lines]);

    const refreshLogs = (file = selectedFile, n = Number(lineCount) || 300) => {
        router.get(
            route('super.system-settings'),
            { file, lines: n },
            { preserveScroll: true, preserveState: true },
        );
    };

    const runMigrate = (scope: 'central' | 'tenant') => {
        const message =
            scope === 'central'
                ? t('Run central migrations now? This cannot be undone easily.')
                : t('Run tenant migrations for all tenants now?');
        if (!confirm(message)) return;

        setBusy(scope);
        router.post(
            route('super.system-settings.migrate'),
            { scope },
            {
                preserveScroll: true,
                onFinish: () => setBusy(null),
            },
        );
    };

    const clearLog = () => {
        if (!confirm(t('Clear this log file? Contents will be permanently removed.'))) return;
        setBusy('clear');
        router.post(
            route('super.system-settings.logs.clear'),
            { file: selectedFile },
            {
                preserveScroll: true,
                onFinish: () => setBusy(null),
            },
        );
    };

    return (
        <SuperLayout title={t('System Settings')}>
            <PageHeader
                title={t('System Settings')}
                subtitle={t('Run migrations and inspect application error logs. Super admin only.')}
            />

            <div className="space-y-6">
                <Card>
                    <CardHeader
                        title={t('Migrations')}
                        subtitle={
                            migrationStatus.separate_databases
                                ? t('Separate databases mode is enabled.')
                                : t('Shared database mode: central and tenant tables may share one DB.')
                        }
                    />
                    <div className="grid gap-4 p-5 md:grid-cols-2">
                        <div className="rounded-lg border p-4">
                            <h3 className="text-sm font-semibold">{t('Central migrations')}</h3>
                            <p className="text-muted-foreground mt-1 text-xs">
                                {t('Pending')}: <span className="text-foreground font-medium">{migrationStatus.central.pending}</span>
                                {' · '}
                                {t('Ran')}: <span className="text-foreground font-medium">{migrationStatus.central.ran}</span>
                            </p>
                            <p className="text-muted-foreground mt-1 truncate text-[11px]">
                                {t('Last ran')}: {migrationStatus.central.last ?? '—'}
                            </p>
                            <div className="mt-3">
                                <ToolbarButton
                                    primary
                                    icon={<Play />}
                                    disabled={busy !== null}
                                    onClick={() => runMigrate('central')}
                                >
                                    {busy === 'central' ? t('Running...') : t('Run central migrations')}
                                </ToolbarButton>
                            </div>
                        </div>

                        <div className="rounded-lg border p-4">
                            <h3 className="text-sm font-semibold">{t('Tenant migrations')}</h3>
                            <p className="text-muted-foreground mt-1 text-xs">
                                {t('Pending')}: <span className="text-foreground font-medium">{migrationStatus.tenant.pending}</span>
                                {' · '}
                                {t('Ran')}: <span className="text-foreground font-medium">{migrationStatus.tenant.ran}</span>
                                {' · '}
                                {t('Tenants')}: <span className="text-foreground font-medium">{migrationStatus.tenant.tenant_count ?? 0}</span>
                            </p>
                            <p className="text-muted-foreground mt-1 truncate text-[11px]">
                                {t('Last ran')}: {migrationStatus.tenant.last ?? '—'}
                            </p>
                            <div className="mt-3">
                                <ToolbarButton
                                    primary
                                    icon={<Play />}
                                    disabled={busy !== null}
                                    onClick={() => runMigrate('tenant')}
                                >
                                    {busy === 'tenant' ? t('Running...') : t('Run tenant migrations')}
                                </ToolbarButton>
                            </div>
                        </div>
                    </div>

                    {flash?.migrate_output && (
                        <div className="border-t px-5 py-4">
                            <h4 className="mb-2 text-xs font-semibold tracking-wide uppercase">
                                {t('Command output')}
                                {flash.migrate_scope ? ` · ${flash.migrate_scope}` : ''}
                            </h4>
                            <pre className="bg-muted max-h-64 overflow-auto rounded-md border p-3 text-[11px] leading-relaxed whitespace-pre-wrap">
                                {flash.migrate_output}
                            </pre>
                        </div>
                    )}
                </Card>

                <Card>
                    <CardHeader
                        title={t('Error logs')}
                        subtitle={t('Shows the last lines of the selected log file without loading the entire file.')}
                        actions={
                            <div className="flex flex-wrap gap-2">
                                <ToolbarButton icon={<RefreshCw />} onClick={() => refreshLogs()} disabled={busy !== null}>
                                    {t('Refresh')}
                                </ToolbarButton>
                                <a href={route('super.system-settings.logs.download', { file: selectedFile })}>
                                    <ToolbarButton icon={<Download />}>{t('Download')}</ToolbarButton>
                                </a>
                                <ToolbarButton danger icon={<Trash2 />} disabled={busy !== null} onClick={clearLog}>
                                    {busy === 'clear' ? t('Clearing...') : t('Clear log')}
                                </ToolbarButton>
                            </div>
                        }
                    />
                    <div className="space-y-3 p-5">
                        <div className="flex flex-wrap items-end gap-3">
                            <label className="text-xs">
                                <span className="text-muted-foreground mb-1 block">{t('Log file')}</span>
                                <select
                                    className="bg-background h-8 min-w-[12rem] rounded-md border px-2 text-sm"
                                    value={selectedFile}
                                    onChange={(e) => {
                                        setSelectedFile(e.target.value);
                                        refreshLogs(e.target.value, Number(lineCount) || 300);
                                    }}
                                >
                                    {(logFiles.length ? logFiles : [{ name: 'laravel.log', path: 'laravel.log', size: 0, modified_at: null }]).map((f) => (
                                        <option key={f.name} value={f.name}>
                                            {f.name} ({formatBytes(f.size)})
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <label className="text-xs">
                                <span className="text-muted-foreground mb-1 block">{t('Lines')}</span>
                                <select
                                    className="bg-background h-8 rounded-md border px-2 text-sm"
                                    value={lineCount}
                                    onChange={(e) => {
                                        setLineCount(e.target.value);
                                        refreshLogs(selectedFile, Number(e.target.value) || 300);
                                    }}
                                >
                                    {[100, 200, 300, 500, 1000].map((n) => (
                                        <option key={n} value={n}>
                                            {n}
                                        </option>
                                    ))}
                                </select>
                            </label>
                            <p className="text-muted-foreground pb-1 text-[11px]">
                                {log.exists
                                    ? `${formatBytes(log.size)}${log.truncated ? ` · ${t('Showing tail only')}` : ''}`
                                    : t('Log file not found.')}
                            </p>
                        </div>

                        <pre className="bg-muted max-h-[28rem] overflow-auto rounded-md border p-3 font-mono text-[11px] leading-relaxed whitespace-pre-wrap">
                            {log.exists ? logText || t('Log is empty.') : t('Log file not found.')}
                        </pre>
                    </div>
                </Card>
            </div>
        </SuperLayout>
    );
}
