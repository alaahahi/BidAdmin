import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { useT } from '@/lib/i18n';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';

export default function SuperLogin() {
    const t = useT();
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('super.login.store'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <AuthLayout title={t('Sign in to Super Admin')} description={t('Manage all BidAdmin tenants')}>
            <Head title={t('Super Admin')} />
            <form className="flex flex-col gap-6" onSubmit={submit}>
                <div className="grid gap-4">
                    <div className="grid gap-2">
                        <Label htmlFor="email">{t('Email')}</Label>
                        <Input id="email" type="email" required autoFocus value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <InputError message={errors.email} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="password">{t('Password')}</Label>
                        <Input id="password" type="password" required value={data.password} onChange={(e) => setData('password', e.target.value)} />
                        <InputError message={errors.password} />
                    </div>
                </div>
                <Button type="submit" disabled={processing}>
                    {processing && <LoaderCircle className="animate-spin" />}
                    {t('Login')}
                </Button>
            </form>
        </AuthLayout>
    );
}
