import { Field, PageHeader, TextInput, ToolbarButton } from '@/components/ba/ui';
import SuperLayout from '@/layouts/super-layout';
import { useT } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import { FormEvent } from 'react';

export default function CreateTenant() {
    const t = useT();
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        slug: '',
        admin_email: '',
        admin_password: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('super.tenants.store'));
    };

    return (
        <SuperLayout title={t('Create Tenant')}>
            <PageHeader title={t('Create Tenant')} subtitle={t('Manage all BidAdmin tenants')} />
            <form onSubmit={submit} className="bg-card max-w-lg space-y-4 rounded-xl border p-5 shadow-xs">
                <Field label="Tenant name" error={errors.name}>
                    <TextInput value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                </Field>
                <Field label="Slug" error={errors.slug} hint="demo → /demo/login · DB bidadmin_demo">
                    <TextInput
                        value={data.slug}
                        onChange={(e) => setData('slug', e.target.value.toLowerCase().replace(/[^a-z0-9-]/g, ''))}
                        required
                    />
                </Field>
                <Field label="Admin Email" error={errors.admin_email}>
                    <TextInput type="email" value={data.admin_email} onChange={(e) => setData('admin_email', e.target.value)} required />
                </Field>
                <Field label="Admin Password" error={errors.admin_password}>
                    <TextInput type="password" value={data.admin_password} onChange={(e) => setData('admin_password', e.target.value)} required />
                </Field>
                <ToolbarButton type="submit" primary disabled={processing}>
                    {t('Create Tenant')}
                </ToolbarButton>
            </form>
        </SuperLayout>
    );
}
