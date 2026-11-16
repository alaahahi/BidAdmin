import { useT } from '@/lib/i18n';
import type { SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ReactNode } from 'react';

export default function SuperLayout({ children, title }: { children: ReactNode; title?: string }) {
    const t = useT();
    const { auth, flash } = usePage<SharedData>().props;

    return (
        <div className="bg-muted/30 min-h-screen">
            <Head title={title ? `${title} · ${t('Super Admin')}` : t('Super Admin')} />
            <header className="bg-card border-b">
                <div className="mx-auto flex max-w-5xl items-center justify-between px-4 py-3">
                    <div className="flex items-center gap-4">
                        <Link href={route('super.dashboard')} className="text-sm font-semibold">
                            {t('Super Admin')}
                        </Link>
                        <Link href={route('super.tenants.create')} className="text-muted-foreground hover:text-foreground text-sm">
                            {t('Create Tenant')}
                        </Link>
                        <Link href={route('super.system-settings')} className="text-muted-foreground hover:text-foreground text-sm">
                            {t('System Settings')}
                        </Link>
                    </div>
                    <div className="flex items-center gap-3 text-sm">
                        <button
                            type="button"
                            className="bg-primary text-primary-foreground hover:opacity-90 rounded-md px-3 py-1.5 text-xs font-medium"
                            onClick={() => router.post(route('super.enter'))}
                        >
                            {t('Enter System')}
                        </button>
                        <span className="text-muted-foreground">{auth.super?.email}</span>
                        <button type="button" className="hover:underline" onClick={() => router.post(route('super.logout'))}>
                            {t('Sign Out')}
                        </button>
                    </div>
                </div>
            </header>
            <main className="mx-auto max-w-5xl px-4 py-6">
                {flash?.success && <div className="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-sm text-green-800">{flash.success}</div>}
                {flash?.error && <div className="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800">{flash.error}</div>}
                {children}
            </main>
        </div>
    );
}
