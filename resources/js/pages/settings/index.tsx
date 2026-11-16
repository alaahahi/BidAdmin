import { DataTable } from '@/components/ba/table';
import { Badge, Card, CardHeader, Field, Modal, ModalFooter, PageHeader, SelectInput, Tabs, TextInput, ToolbarButton } from '@/components/ba/ui';
import { useAppearance } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import { fmtDate, fmtDateTime, useTz } from '@/lib/format';
import type { SharedData } from '@/types';
import { Head, router, useForm, usePage } from '@inertiajs/react';
import { CheckCircle2, Copy, Moon, Plus, Sun, Trash2, Upload } from 'lucide-react';
import { ReactNode, useMemo, useRef, useState } from 'react';

interface Token {
    id: number;
    name: string;
    preview: string;
    last_used_at: string | null;
    created_at: string;
    expires_at: string | null;
}
interface Props {
    tab: string;
    values: Record<string, string | null>;
    timezones: string[];
    tokens: Token[];
    team: { id: number; name: string; email: string; created_at: string }[];
}

function Section({ title, subtitle, children }: { title: string; subtitle?: string; children: ReactNode }) {
    return (
        <div className="mb-6">
            <h2 className="font-semibold">{title}</h2>
            {subtitle && <p className="text-muted-foreground mb-3 text-xs">{subtitle}</p>}
            {children}
        </div>
    );
}

function Branding({ values }: { values: Props['values'] }) {
    const { settings } = usePage<SharedData>().props;
    const { appearance, updateAppearance } = useAppearance();
    const fileRef = useRef<HTMLInputElement>(null);
    const { data, setData, put, processing, errors } = useForm({ section: 'branding', company_name: values.company_name ?? '', brand_color: values.brand_color ?? '#111827' });
    const upload = (f: File) => router.post(route('settings.logo'), { logo: f }, { forceFormData: true, preserveScroll: true });
    return (
        <>
            <Section title="Branding" subtitle="Customize your company branding settings">
                <h3 className="text-sm font-semibold">Appearance</h3>
                <p className="text-muted-foreground mb-2 text-xs">Choose how the dashboard looks.</p>
                <div className="flex gap-2">
                    {(['light', 'dark'] as const).map((a) => (
                        <button
                            key={a}
                            onClick={() => updateAppearance(a)}
                            className={`flex h-10 items-center gap-2 rounded-lg border px-5 text-sm font-medium ${appearance === a ? 'brand-bg text-white shadow' : 'bg-card'}`}
                        >
                            {a === 'light' ? <Sun className="size-4" /> : <Moon className="size-4" />}
                            {a === 'light' ? 'Light' : 'Dark'}
                            {appearance === a && <CheckCircle2 className="size-4" />}
                        </button>
                    ))}
                </div>
            </Section>
            <form
                onSubmit={(e) => {
                    e.preventDefault();
                    put(route('settings.update'), { preserveScroll: true });
                }}
                className="space-y-4"
            >
                <div className="grid gap-4 md:grid-cols-2">
                    <Card className="p-5">
                        <h3 className="font-semibold">Company Logo</h3>
                        <p className="text-muted-foreground mb-3 text-xs">Square (e.g. 256x256) or 2:1 rectangle (e.g. 512x256). PNG or SVG recommended. Max 2MB.</p>
                        <div className="flex items-center gap-3">
                            <div className="bg-muted flex h-16 w-24 items-center justify-center overflow-hidden rounded-md border">{settings.logo_url && <img src={settings.logo_url} className="max-h-full max-w-full object-contain" />}</div>
                            <input ref={fileRef} type="file" accept="image/*" hidden onChange={(e) => e.target.files?.[0] && upload(e.target.files[0])} />
                            <ToolbarButton icon={<Upload />} onClick={() => fileRef.current?.click()}>
                                Upload
                            </ToolbarButton>
                            {settings.logo_url && (
                                <ToolbarButton danger onClick={() => router.delete(route('settings.logo.destroy'), { preserveScroll: true })}>
                                    Remove
                                </ToolbarButton>
                            )}
                        </div>
                    </Card>
                    <Card className="p-5">
                        <h3 className="font-semibold">Company Name</h3>
                        <p className="text-muted-foreground mb-3 text-xs">This will be displayed across your branding materials</p>
                        <TextInput value={data.company_name} onChange={(e) => setData('company_name', e.target.value)} />
                        {errors.company_name && <p className="text-xs text-red-600">{errors.company_name}</p>}
                    </Card>
                </div>
                <Card className="p-5">
                    <h3 className="font-semibold">Brand Color</h3>
                    <p className="text-muted-foreground mb-3 text-xs">Set your primary brand color. Used for buttons, PDFs and quote pages.</p>
                    <div className="flex items-center gap-2">
                        <input type="color" value={data.brand_color} onChange={(e) => setData('brand_color', e.target.value)} className="h-9 w-12 rounded border" />
                        <TextInput className="w-32" value={data.brand_color} onChange={(e) => setData('brand_color', e.target.value)} />
                    </div>
                    {errors.brand_color && <p className="text-xs text-red-600">{errors.brand_color}</p>}
                </Card>
                <ToolbarButton type="submit" primary disabled={processing}>
                    Save Branding
                </ToolbarButton>
            </form>
        </>
    );
}

function SimpleForm({ section, initial, children }: { section: string; initial: Record<string, string | boolean>; children: (data: Record<string, string | boolean>, set: (k: string, v: string | boolean) => void) => ReactNode }) {
    const { data, setData, put, processing } = useForm({ section, ...initial } as Record<string, string | boolean>);
    return (
        <form
            className="max-w-xl space-y-4"
            onSubmit={(e) => {
                e.preventDefault();
                put(route('settings.update'), { preserveScroll: true });
            }}
        >
            {children(data, (k, v) => setData(k, v))}
            <ToolbarButton type="submit" primary disabled={processing}>
                Save
            </ToolbarButton>
        </form>
    );
}

function Development({ tokens }: { tokens: Token[] }) {
    const tz = useTz();
    const [open, setOpen] = useState(false);
    const [copied, setCopied] = useState<'url' | 'snippet' | null>(null);
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', expires_in_days: '' });
    const { tenant } = usePage<SharedData>().props;
    const base = route('home').replace(/\/$/, '');
    const mcpUrl = useMemo(() => {
        const origin = typeof window !== 'undefined' ? window.location.origin : base.replace(/\/[^/]+$/, '') || base;
        const slug = tenant?.slug ?? base.split('/').filter(Boolean).pop() ?? 'tenant';
        return `${origin.replace(/\/$/, '')}/${slug}/mcp`;
    }, [base, tenant?.slug]);
    const mcpSnippet = useMemo(
        () =>
            JSON.stringify(
                {
                    mcpServers: {
                        bidadmin: {
                            url: mcpUrl,
                            headers: {
                                Authorization: 'Bearer <your-api-token>',
                            },
                        },
                    },
                },
                null,
                2,
            ),
        [mcpUrl],
    );
    const copyText = async (value: string, which: 'url' | 'snippet') => {
        try {
            await navigator.clipboard?.writeText(value);
            setCopied(which);
            window.setTimeout(() => setCopied(null), 1500);
        } catch {
            //
        }
    };
    return (
        <>
            <Section title="API Keys" subtitle="Admin keys can post events for any customer by passing customer_id. Customer tokens are created on each customer's Credentials tab.">
                <div className="mb-3 flex justify-end">
                    <ToolbarButton primary icon={<Plus />} onClick={() => setOpen(true)}>
                        Create API Key
                    </ToolbarButton>
                </div>
                <DataTable
                    rows={tokens}
                    columns={[
                        { key: 'name', label: 'Name', render: (t) => <span className="font-semibold">{t.name}</span> },
                        { key: 'key', label: 'API Key', render: (t) => <code className="text-[11px]">{t.preview}</code> },
                        { key: 'created', label: 'Created', render: (t) => fmtDate(t.created_at, tz) },
                        { key: 'expires', label: 'Expires', render: (t) => (t.expires_at ? fmtDate(t.expires_at, tz) : 'Never') },
                        { key: 'status', label: 'Status', render: (t) => <Badge tone={t.expires_at && new Date(t.expires_at) < new Date() ? 'overdue' : 'active'}>{t.expires_at && new Date(t.expires_at) < new Date() ? 'expired' : 'active'}</Badge> },
                        {
                            key: 'actions',
                            label: 'Actions',
                            render: (t) => <ToolbarButton danger small icon={<Trash2 />} onClick={() => confirm('Revoke key?') && router.delete(route('settings.tokens.destroy', t.id), { preserveScroll: true })} />,
                        },
                    ]}
                    empty="No API keys yet."
                />
            </Section>
            <Card className="p-5 text-xs">
                <h3 className="mb-2 text-sm font-semibold">Endpoints (for the browser extension)</h3>
                <pre className="bg-muted overflow-x-auto rounded p-3 leading-relaxed">{`POST ${base}/api/activity   {"type":"lot_view","account_id":1,"lot_number":"46121395","description":"2025 TOYOTA CAMRY"}
POST ${base}/api/bids       {"account_id":1,"lot_number":"46121395","amount":5000,"max_bid":6500,"type":"prebid"}
POST ${base}/api/watchlist  {"account_id":1,"lot_number":"46121395","action":"add"}
Headers: Authorization: Bearer <token>, Accept: application/json`}</pre>
                <p className="text-muted-foreground mt-2">The customer is taken from the token and the request IP is recorded automatically.</p>
            </Card>
            <Section title="MCP" subtitle="Connect Cursor or other MCP clients to this tenant with a Sanctum API token.">
                <Card className="space-y-4 p-5 text-xs">
                    <div>
                        <h3 className="mb-1 text-sm font-semibold">Tenant MCP URL</h3>
                        <div className="flex flex-wrap items-center gap-2">
                            <code className="bg-muted rounded px-2 py-1 text-[11px] break-all">{mcpUrl}</code>
                            <ToolbarButton small icon={<Copy />} onClick={() => copyText(mcpUrl, 'url')}>
                                {copied === 'url' ? 'Copied' : 'Copy'}
                            </ToolbarButton>
                        </div>
                    </div>
                    <div>
                        <h3 className="mb-1 text-sm font-semibold">Authentication</h3>
                        <p className="text-muted-foreground mb-2">
                            Send an admin API token from the table above as{' '}
                            <code className="bg-muted rounded px-1">Authorization: Bearer &lt;token&gt;</code>.
                        </p>
                    </div>
                    <div>
                        <div className="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <h3 className="text-sm font-semibold">Cursor <code className="text-[11px]">.cursor/mcp.json</code></h3>
                            <ToolbarButton small icon={<Copy />} onClick={() => copyText(mcpSnippet, 'snippet')}>
                                {copied === 'snippet' ? 'Copied' : 'Copy snippet'}
                            </ToolbarButton>
                        </div>
                        <pre className="bg-muted overflow-x-auto rounded p-3 leading-relaxed">{mcpSnippet}</pre>
                        <p className="text-muted-foreground mt-2">Replace <code className="bg-muted rounded px-1">&lt;your-api-token&gt;</code> with a real API key. Save as <code className="bg-muted rounded px-1">.cursor/mcp.json</code> in your project.</p>
                    </div>
                </Card>
            </Section>
            <Modal open={open} onClose={() => setOpen(false)} title="Create API Key">
                <form
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('settings.tokens.store'), {
                            preserveScroll: true,
                            onSuccess: () => {
                                reset();
                                setOpen(false);
                            },
                        });
                    }}
                >
                    <Field label="Name" error={errors.name}>
                        <TextInput value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                    </Field>
                    <Field label="Expires in (days)" hint="Leave empty for no expiry">
                        <TextInput type="number" value={data.expires_in_days} onChange={(e) => setData('expires_in_days', e.target.value)} />
                    </Field>
                    <ModalFooter onCancel={() => setOpen(false)} processing={processing} submitLabel="Create" />
                </form>
            </Modal>
        </>
    );
}

function Team({ team }: { team: Props['team'] }) {
    const tz = useTz();
    const me = usePage<SharedData>().props.auth.user.id;
    const [open, setOpen] = useState(false);
    const { data, setData, post, processing, errors, reset } = useForm({ name: '', email: '', password: '' });
    return (
        <Section title="Team" subtitle="Admins who can manage accounts, customers and invoices">
            <div className="mb-3 flex justify-end">
                <ToolbarButton primary icon={<Plus />} onClick={() => setOpen(true)}>
                    Add Member
                </ToolbarButton>
            </div>
            <DataTable
                rows={team}
                columns={[
                    { key: 'name', label: 'Name', render: (u) => <span className="font-semibold">{u.name}</span> },
                    { key: 'email', label: 'Email', render: (u) => u.email },
                    { key: 'role', label: 'Role', render: () => <Badge>admin</Badge> },
                    { key: 'joined', label: 'Added', render: (u) => fmtDateTime(u.created_at, tz) },
                    {
                        key: 'actions',
                        label: 'Actions',
                        render: (u) => (u.id === me ? <span className="text-muted-foreground text-xs">You</span> : <ToolbarButton small danger icon={<Trash2 />} onClick={() => confirm(`Remove ${u.name}?`) && router.delete(route('settings.team.destroy', u.id), { preserveScroll: true })} />),
                    },
                ]}
            />
            <Modal open={open} onClose={() => setOpen(false)} title="Add Team Member">
                <form
                    className="space-y-4"
                    onSubmit={(e) => {
                        e.preventDefault();
                        post(route('settings.team.store'), {
                            preserveScroll: true,
                            onSuccess: () => {
                                reset();
                                setOpen(false);
                            },
                        });
                    }}
                >
                    <Field label="Name" error={errors.name}>
                        <TextInput value={data.name} onChange={(e) => setData('name', e.target.value)} required />
                    </Field>
                    <Field label="Email" error={errors.email}>
                        <TextInput type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} required />
                    </Field>
                    <Field label="Temporary password" error={errors.password}>
                        <TextInput type="password" autoComplete="new-password" value={data.password} onChange={(e) => setData('password', e.target.value)} required />
                    </Field>
                    <ModalFooter onCancel={() => setOpen(false)} processing={processing} submitLabel="Add Member" />
                </form>
            </Modal>
        </Section>
    );
}

export default function SettingsPage({ tab, values, timezones, tokens, team }: Props) {
    const tabs: [string, string][] = [
        ['branding', 'Branding'],
        ['app', 'App Configuration'],
        ['timezone', 'Timezone'],
        ['performance', 'Performance'],
        ['language', 'Language'],
        ['development', 'Development'],
        ['team', 'Team'],
    ];
    const bool = (k: string) => values[k] === '1';
    return (
        <AppLayout>
            <Head title="Settings" />
            <PageHeader title="Settings" subtitle="Manage your account settings and preferences" />
            <Tabs tabs={tabs} current={tab} hrefFor={(k) => route('settings.index', { tab: k })} />
            {tab === 'branding' && <Branding values={values} />}
            {tab === 'app' && (
                <Section title="App Configuration" subtitle="General behaviour of the dashboard and customer portal">
                    <SimpleForm
                        section="app"
                        initial={{ currency: values.currency ?? 'USD', customer_portal: bool('customer_portal'), customers_can_see_invoices: bool('customers_can_see_invoices'), default_per_page: values.default_per_page ?? '10', session_lifetime: values.session_lifetime ?? '120', blocked_countries: values.blocked_countries ?? '' }}
                    >
                        {(d, set) => (
                            <>
                                <Field label="Currency">
                                    <SelectInput value={String(d.currency)} onChange={(e) => set('currency', e.target.value)} options={['USD', 'CAD', 'EUR', 'GBP', 'AED', 'IQD'].map((c) => [c, c])} />
                                </Field>
                                <Field label="Default rows per page">
                                    <SelectInput value={String(d.default_per_page)} onChange={(e) => set('default_per_page', e.target.value)} options={['10', '25', '50', '100'].map((c) => [c, c])} />
                                </Field>
                                <Field label="Customer session lifetime (minutes)">
                                    <TextInput type="number" value={String(d.session_lifetime)} onChange={(e) => set('session_lifetime', e.target.value)} />
                                </Field>
                                <Field label="Blocked Countries" hint="Comma-separated ISO codes (e.g. IR,KP). Blocks Flutter app login by IP country.">
                                    <TextInput value={String(d.blocked_countries ?? '')} onChange={(e) => set('blocked_countries', e.target.value)} placeholder="IR,KP" />
                                </Field>
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={!!d.customer_portal} onChange={(e) => set('customer_portal', e.target.checked)} /> Enable customer portal logins
                                </label>
                                <label className="flex items-center gap-2 text-sm">
                                    <input type="checkbox" checked={!!d.customers_can_see_invoices} onChange={(e) => set('customers_can_see_invoices', e.target.checked)} /> Customers can see invoices they won
                                </label>
                            </>
                        )}
                    </SimpleForm>
                </Section>
            )}
            {tab === 'timezone' && (
                <Section title="Timezone" subtitle="All dates and times in the dashboard are shown in this timezone">
                    <SimpleForm section="timezone" initial={{ timezone: values.timezone ?? 'UTC', invoice_timezone: values.invoice_timezone ?? 'account' }}>
                        {(d, set) => (
                            <>
                                <Field label="Timezone">
                                    <SelectInput value={String(d.timezone)} onChange={(e) => set('timezone', e.target.value)} options={timezones.map((z) => [z, z])} />
                                </Field>
                                <Field label="Invoice dates" hint="Which timezone invoice dates are recorded in">
                                    <SelectInput value={String(d.invoice_timezone)} onChange={(e) => set('invoice_timezone', e.target.value)} options={[['account', 'Account timezone (auction region)'], ['invoice', 'Invoice timezone (as issued)']]} />
                                </Field>
                            </>
                        )}
                    </SimpleForm>
                </Section>
            )}
            {tab === 'performance' && (
                <Section title="Performance" subtitle="Reduce bandwidth on slow connections">
                    <SimpleForm section="performance" initial={{ low_data_mode: bool('low_data_mode') }}>
                        {(d, set) => (
                            <label className="flex items-start gap-2 text-sm">
                                <input type="checkbox" className="mt-1" checked={!!d.low_data_mode} onChange={(e) => set('low_data_mode', e.target.checked)} />
                                <span>
                                    <b>Low Data Mode</b>
                                    <span className="text-muted-foreground block text-xs">Hide vehicle images in tables and detail pages.</span>
                                </span>
                            </label>
                        )}
                    </SimpleForm>
                </Section>
            )}
            {tab === 'language' && (
                <Section title="Language" subtitle="Interface language. Arabic and Kurdish Sorani switch the layout to right-to-left.">
                    <SimpleForm section="language" initial={{ language: values.language ?? 'en' }}>
                        {(d, set) => (
                            <div className="flex flex-wrap gap-2">
                                {[
                                    ['en', 'English'],
                                    ['ar', 'العربية'],
                                    ['ckb', 'کوردی سۆرانی'],
                                ].map(([k, l]) => (
                                    <button type="button" key={k} onClick={() => set('language', k)} className={`rounded-lg border px-5 py-2 text-sm ${d.language === k ? 'brand-bg text-white' : 'bg-card'}`}>
                                        {l}
                                    </button>
                                ))}
                            </div>
                        )}
                    </SimpleForm>
                </Section>
            )}
            {tab === 'development' && <Development tokens={tokens} />}
            {tab === 'team' && <Team team={team} />}
        </AppLayout>
    );
}

