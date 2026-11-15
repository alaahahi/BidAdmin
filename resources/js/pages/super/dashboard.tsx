import { Badge, PageHeader, ToolbarButton } from '@/components/ba/ui';
import SuperLayout from '@/layouts/super-layout';
import { useT } from '@/lib/i18n';
import { Link, router } from '@inertiajs/react';
import { LayoutDashboard, Plus } from 'lucide-react';

interface AdminRow {
    id: number;
    name: string;
    email: string;
    role?: string;
    created_at: string | null;
}

interface TenantRow {
    id: number;
    name: string;
    slug: string;
    db_name: string;
    admin_email: string | null;
    status: string;
    created_at: string;
    deleted_at: string | null;
    admins: AdminRow[];
}

export default function SuperDashboard({ tenants }: { tenants: TenantRow[] }) {
    const t = useT();

    return (
        <SuperLayout title={t('Tenants')}>
            <PageHeader
                title={t('Tenants')}
                subtitle={t('Shared database mode: super admin and all company data live in one DB. Separate DBs stay available later.')}
                actions={
                    <div className="flex flex-wrap gap-2">
                        <ToolbarButton
                            primary
                            icon={<LayoutDashboard />}
                            onClick={() => router.post(route('super.enter'))}
                        >
                            {t('Enter System')}
                        </ToolbarButton>
                        <Link href={route('super.tenants.create')}>
                            <ToolbarButton icon={<Plus />}>{t('Create Tenant')}</ToolbarButton>
                        </Link>
                    </div>
                }
            />
            <div className="space-y-4">
                {tenants.length === 0 && (
                    <div className="bg-card text-muted-foreground rounded-xl border px-4 py-8 text-center text-sm shadow-xs">{t('No tenants yet.')}</div>
                )}
                {tenants.map((tenant) => (
                    <div key={tenant.id} className="bg-card rounded-xl border shadow-xs">
                        <div className="flex flex-wrap items-start justify-between gap-3 border-b px-4 py-3">
                            <div>
                                <div className="flex flex-wrap items-center gap-2">
                                    <h2 className="font-semibold">{tenant.name}</h2>
                                    <Badge tone={tenant.deleted_at ? 'cancelled' : tenant.status === 'active' ? 'active' : 'paused'}>
                                        {tenant.deleted_at ? t('Delete') : t(tenant.status)}
                                    </Badge>
                                </div>
                                <p className="text-muted-foreground mt-1 text-xs">
                                    <code>{tenant.slug}</code> · <code>{tenant.db_name}</code>
                                    {tenant.admin_email && (
                                        <>
                                            {' '}
                                            · {t('Admin Email')}: <span className="text-foreground font-medium">{tenant.admin_email}</span>
                                        </>
                                    )}
                                </p>
                            </div>
                            {!tenant.deleted_at && (
                                <div className="flex flex-wrap gap-2">
                                    <a href={`/${tenant.slug}/login`} className="bg-card hover:bg-muted inline-flex h-8 items-center rounded-md border px-3 text-xs font-medium">
                                        {t('Open Tenant')}
                                    </a>
                                    <Link href={route('super.tenants.edit', tenant.id)} className="bg-card hover:bg-muted inline-flex h-8 items-center rounded-md border px-3 text-xs font-medium">
                                        {t('Edit')}
                                    </Link>
                                    {tenant.status === 'active' ? (
                                        <ToolbarButton small onClick={() => router.post(route('super.tenants.suspend', tenant.id))}>
                                            {t('Suspend')}
                                        </ToolbarButton>
                                    ) : (
                                        <ToolbarButton small onClick={() => router.post(route('super.tenants.activate', tenant.id))}>
                                            {t('Activate')}
                                        </ToolbarButton>
                                    )}
                                    <ToolbarButton small danger onClick={() => confirm(t('Soft delete keeps the tenant database.')) && router.delete(route('super.tenants.destroy', tenant.id))}>
                                        {t('Delete')}
                                    </ToolbarButton>
                                </div>
                            )}
                        </div>
                        <div className="px-4 py-3">
                            <h3 className="mb-2 text-xs font-semibold tracking-wide uppercase">{t('Tenant Admins')}</h3>
                            {tenant.admins.length === 0 ? (
                                <p className="text-muted-foreground text-xs">{t('No admin accounts found in this tenant database.')}</p>
                            ) : (
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="text-muted-foreground border-b text-xs">
                                            <th className="py-2 text-start font-medium">{t('Name')}</th>
                                            <th className="py-2 text-start font-medium">{t('Email')}</th>
                                            <th className="py-2 text-start font-medium">{t('Role')}</th>
                                            <th className="py-2 text-start font-medium">{t('Created')}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {tenant.admins.map((admin) => (
                                            <tr key={admin.id} className="border-b last:border-0">
                                                <td className="py-2 font-medium">{admin.name}</td>
                                                <td className="py-2">{admin.email}</td>
                                                <td className="py-2 text-xs">{admin.role ?? 'admin'}</td>
                                                <td className="text-muted-foreground py-2 text-xs">{admin.created_at ?? '—'}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </div>
                ))}
            </div>
        </SuperLayout>
    );
}
