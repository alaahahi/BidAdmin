import { Field, PageHeader, TextInput, ToolbarButton } from '@/components/ba/ui';
import SuperLayout from '@/layouts/super-layout';
import { useT } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

interface AdminRow {
    id: number;
    name: string;
    email: string;
    created_at: string | null;
}

interface Tenant {
    id: number;
    name: string;
    slug: string;
    db_name: string;
    admin_email: string | null;
    status: string;
}

export default function EditTenant({ tenant, admins = [] }: { tenant: Tenant; admins?: AdminRow[] }) {
    const t = useT();
    const { data, setData, put, processing, errors } = useForm({
        name: tenant.name,
        status: tenant.status,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(route('super.tenants.update', tenant.id));
    };

    return (
        <SuperLayout title={t('Edit Tenant')}>
            <PageHeader title={t('Edit Tenant')} subtitle={`${tenant.slug} · ${tenant.db_name}`} />
            <div className="grid max-w-3xl gap-4 lg:grid-cols-2">
                <form onSubmit={submit} className="bg-card space-y-4 rounded-xl border p-5 shadow-xs">
                    <Field label="Tenant name" error={errors.name}>
                        <TextInput value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                    </Field>
                    <Field label="Slug">
                        <TextInput value={tenant.slug} disabled />
                    </Field>
                    <Field label="Admin Email">
                        <TextInput value={tenant.admin_email ?? ''} disabled />
                    </Field>
                    <Field label="Status" error={errors.status}>
                        <select className="bg-card h-9 w-full rounded-md border px-3 text-sm" value={data.status} onChange={(e) => setData('status', e.target.value)}>
                            <option value="active">{t('active')}</option>
                            <option value="suspended">{t('suspended')}</option>
                        </select>
                    </Field>
                    <ToolbarButton type="submit" primary disabled={processing}>
                        {t('Save')}
                    </ToolbarButton>
                </form>
                <div className="bg-card rounded-xl border p-5 shadow-xs">
                    <h3 className="mb-3 text-sm font-semibold">{t('Tenant Admins')}</h3>
                    {admins.length === 0 ? (
                        <p className="text-muted-foreground text-xs">{t('No admin accounts found in this tenant database.')}</p>
                    ) : (
                        <ul className="space-y-2 text-sm">
                            {admins.map((admin) => (
                                <li key={admin.id} className="rounded-md border px-3 py-2">
                                    <div className="font-medium">{admin.name}</div>
                                    <div className="text-muted-foreground text-xs">{admin.email}</div>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>
            </div>
        </SuperLayout>
    );
}
