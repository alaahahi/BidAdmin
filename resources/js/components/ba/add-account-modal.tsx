import { useT } from '@/lib/i18n';
import { useForm } from '@inertiajs/react';
import { Eye, EyeOff, Info } from 'lucide-react';
import { FormEvent, useState } from 'react';
import { Field, Modal, ModalFooter, TextInput } from './ui';
import { ProviderMark } from './provider';

export const REGIONS: Record<string, string[]> = {
    copart: ['USA', 'CAN', 'UK', 'UAE', 'DE'],
    iaai: ['USA', 'CAN', 'UK'],
    manheim: ['USA', 'CAN'],
    edgepipeline: ['USA'],
    npa: ['USA'],
};

export default function AddAccountModal({ open, onClose }: { open: boolean; onClose: () => void }) {
    const t = useT();
    const { data, setData, post, processing, errors, reset } = useForm({ provider: '', region: '', username: '', password: '' });
    const [show, setShow] = useState(false);

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(route('accounts.store'), {
            onSuccess: () => {
                reset();
                onClose();
            },
        });
    };

    return (
        <Modal open={open} onClose={onClose} title="Add New Account" description="Connect a new auction platform account to your dashboard.">
            <form onSubmit={submit} className="space-y-5">
                <div>
                    <div className="mb-2 text-xs font-medium">{t('Select Platform')}</div>
                    <div className="grid grid-cols-2 gap-2">
                        {Object.keys(REGIONS).map((p) => (
                            <button
                                type="button"
                                key={p}
                                onClick={() => setData((d) => ({ ...d, provider: p, region: REGIONS[p][0] }))}
                                className={`flex h-16 items-center justify-center rounded-lg border transition ${data.provider === p ? 'border-foreground ring-2 ring-foreground/10' : 'hover:border-foreground/40'}`}
                            >
                                <ProviderMark provider={p} size="lg" />
                            </button>
                        ))}
                    </div>
                    {errors.provider && <p className="mt-1 text-xs text-red-600">{errors.provider}</p>}
                </div>
                <Field label="Select Region" error={errors.region}>
                    {data.provider ? (
                        <div className="flex flex-wrap gap-2">
                            {REGIONS[data.provider].map((r) => (
                                <button
                                    type="button"
                                    key={r}
                                    onClick={() => setData('region', r)}
                                    className={`rounded-md border px-3 py-1.5 text-xs ${data.region === r ? 'border-foreground bg-foreground text-background' : 'hover:bg-muted'}`}
                                >
                                    {r}
                                </button>
                            ))}
                        </div>
                    ) : (
                        <p className="text-muted-foreground text-xs italic">{t('Please select a platform first')}</p>
                    )}
                </Field>
                <Field label="Username" error={errors.username}>
                    <TextInput value={data.username} onChange={(e) => setData('username', e.target.value)} placeholder={t('Enter your username')} autoComplete="off" />
                </Field>
                <Field label="Password" error={errors.password}>
                    <div className="relative">
                        <TextInput
                            type={show ? 'text' : 'password'}
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                            placeholder={t('Enter your password')}
                            autoComplete="new-password"
                        />
                        <button type="button" onClick={() => setShow(!show)} className="text-muted-foreground absolute top-1/2 end-3 -translate-y-1/2">
                            {show ? <EyeOff className="size-4" /> : <Eye className="size-4" />}
                        </button>
                    </div>
                </Field>
                <p className="text-muted-foreground flex gap-2 text-xs">
                    <Info className="mt-0.5 size-4 shrink-0" />
                    {t("The password is encrypted with your application key and is only visible to admins on the account's Credentials tab.")}
                </p>
                <ModalFooter onCancel={onClose} submitLabel="Add Account" processing={processing} />
            </form>
        </Modal>
    );
}
