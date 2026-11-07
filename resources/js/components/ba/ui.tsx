import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useT } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { Car, ChevronDown, LoaderCircle, Search, Settings2 } from 'lucide-react';
import type { SharedData } from '@/types';
import { ButtonHTMLAttributes, InputHTMLAttributes, ReactNode, SelectHTMLAttributes, TextareaHTMLAttributes, forwardRef } from 'react';

function trNode(t: (s: string) => string, v: ReactNode): ReactNode {
    return typeof v === 'string' ? t(v) : v;
}

export function PageHeader({ title, subtitle, actions, children }: { title: ReactNode; subtitle?: ReactNode; actions?: ReactNode; children?: ReactNode }) {
    const t = useT();
    return (
        <div className="mb-4 flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 className="text-xl font-semibold tracking-tight">{trNode(t, title)}</h1>
                {subtitle && <p className="text-muted-foreground text-sm">{trNode(t, subtitle)}</p>}
                {children}
            </div>
            {actions && <div className="flex flex-wrap items-center gap-2">{actions}</div>}
        </div>
    );
}

type TBProps = ButtonHTMLAttributes<HTMLButtonElement> & { icon?: ReactNode; primary?: boolean; danger?: boolean; small?: boolean };

export const ToolbarButton = forwardRef<HTMLButtonElement, TBProps>(({ icon, children, primary, danger, small, className, ...rest }, ref) => (
    <button
        ref={ref}
        type="button"
        {...rest}
        className={cn(
            'inline-flex items-center gap-1.5 rounded-md border font-medium whitespace-nowrap shadow-xs transition disabled:pointer-events-none disabled:opacity-50 [&_svg]:size-3.5',
            small ? 'h-7 px-2.5 text-[11px]' : 'h-8 px-3 text-xs',
            primary
                ? 'brand-bg border-transparent text-white hover:opacity-90'
                : danger
                  ? 'border-red-200 bg-red-50 text-red-600 hover:bg-red-100 dark:border-red-900 dark:bg-red-950'
                  : 'bg-card hover:bg-muted',
            className,
        )}
    >
        {icon}
        {children}
    </button>
));
ToolbarButton.displayName = 'ToolbarButton';

export function LinkButton({ href, children, className, primary }: { href: string; children: ReactNode; className?: string; primary?: boolean }) {
    return (
        <Link
            href={href}
            className={cn(
                'inline-flex h-8 items-center gap-1.5 rounded-md px-3 text-xs font-medium whitespace-nowrap',
                primary ? 'brand-bg text-white hover:opacity-90' : 'bg-card border hover:bg-muted',
                className,
            )}
        >
            {children}
        </Link>
    );
}

export function Card({ children, className }: { children: ReactNode; className?: string }) {
    return <div className={cn('bg-card rounded-xl border shadow-xs', className)}>{children}</div>;
}

export function CardHeader({ title, subtitle, actions }: { title: ReactNode; subtitle?: ReactNode; actions?: ReactNode }) {
    const t = useT();
    return (
        <div className="flex items-start justify-between gap-3 border-b px-5 py-4">
            <div>
                <h3 className="font-semibold">{trNode(t, title)}</h3>
                {subtitle && <p className="text-muted-foreground text-xs">{trNode(t, subtitle)}</p>}
            </div>
            {actions}
        </div>
    );
}

const TONES: Record<string, string> = {
    default: '',
    red: 'text-red-600',
    orange: 'text-orange-500',
    green: 'text-green-600',
};

export function StatCard({ label, value, hint, tone = 'default', className, onClick, active }: { label: string; value: ReactNode; hint?: string; tone?: keyof typeof TONES; className?: string; onClick?: () => void; active?: boolean }) {
    const t = useT();
    return (
        <div onClick={onClick} className={cn('bg-card rounded-xl border px-4 py-3 shadow-xs', onClick && 'cursor-pointer hover:border-foreground/30', active && 'border-foreground ring-1 ring-foreground/20', className)}>
            <div className="text-[11px] font-semibold">{t(label)}</div>
            <div className={cn('mt-1 text-xl font-semibold', TONES[tone])}>{value}</div>
            {hint && <div className="text-muted-foreground mt-1 text-[11px]">{t(hint)}</div>}
        </div>
    );
}

export function MultiStat({ title, rows, className }: { title?: string; rows: { label: string; value: ReactNode; tone?: keyof typeof TONES }[]; className?: string }) {
    const t = useT();
    return (
        <div className={cn('bg-card space-y-1.5 rounded-xl border px-4 py-3 shadow-xs', className)}>
            {title && <div className="text-[11px] font-semibold">{t(title)}</div>}
            {rows.map((r) => (
                <div key={r.label} className="flex items-center justify-between gap-4 text-[13px] font-semibold">
                    <span>{t(r.label)}</span>
                    <span className={TONES[r.tone ?? 'default']}>{r.value}</span>
                </div>
            ))}
        </div>
    );
}

export function SearchBar({ value, onChange, placeholder, onManageColumns, right }: { value: string; onChange: (v: string) => void; placeholder: string; onManageColumns?: ReactNode; right?: ReactNode }) {
    const t = useT();
    return (
        <div className="mb-2 flex gap-2">
            <div className="bg-card flex h-9 flex-1 items-center gap-2 rounded-md border px-3 shadow-xs">
                <Search className="text-muted-foreground size-4" />
                <input value={value} onChange={(e) => onChange(e.target.value)} placeholder={t(placeholder)} className="h-full w-full bg-transparent text-sm outline-none" />
            </div>
            {onManageColumns}
            {right}
        </div>
    );
}

export function FilterSelect({ value, onChange, placeholder, options, className }: { value: string | number | undefined; onChange: (v: string) => void; placeholder: string; options: [string | number, string][]; className?: string }) {
    const t = useT();
    return (
        <div className={cn('relative', className)}>
            <select
                value={value ?? ''}
                onChange={(e) => onChange(e.target.value)}
                className="bg-card h-9 w-full appearance-none rounded-md border ps-3 pe-8 text-xs shadow-xs outline-none focus:ring-1 focus:ring-foreground/20"
            >
                <option value="">{t(placeholder)}</option>
                {options.map(([v, l]) => (
                    <option key={v} value={v}>
                        {t(String(l))}
                    </option>
                ))}
            </select>
            <ChevronDown className="text-muted-foreground pointer-events-none absolute end-2.5 top-1/2 size-4 -translate-y-1/2" />
        </div>
    );
}

export function DateFilter({ from, to, onChange }: { from?: string; to?: string; onChange: (from: string, to: string) => void }) {
    return (
        <div className="bg-card flex h-9 items-center gap-1 rounded-md border px-2 text-xs shadow-xs">
            <input type="date" value={from ?? ''} onChange={(e) => onChange(e.target.value, to ?? '')} className="w-full min-w-0 bg-transparent outline-none" title="From" />
            <span className="text-muted-foreground">–</span>
            <input type="date" value={to ?? ''} onChange={(e) => onChange(from ?? '', e.target.value)} className="w-full min-w-0 bg-transparent outline-none" title="To" />
        </div>
    );
}

export function Field({ label, error, children, hint, className }: { label: string; error?: string; children: ReactNode; hint?: string; className?: string }) {
    const t = useT();
    return (
        <label className={cn('block space-y-1.5', className)}>
            <span className="text-xs font-medium">{t(label)}</span>
            {children}
            {hint && <span className="text-muted-foreground block text-[11px]">{t(hint)}</span>}
            {error && <span className="block text-xs text-red-600">{error}</span>}
        </label>
    );
}

export const TextInput = forwardRef<HTMLInputElement, InputHTMLAttributes<HTMLInputElement>>(({ className, ...p }, ref) => (
    <input ref={ref} {...p} className={cn('bg-card h-9 w-full rounded-md border px-3 text-sm outline-none focus:ring-1 focus:ring-foreground/20', className)} />
));
TextInput.displayName = 'TextInput';

export function TextArea(p: TextareaHTMLAttributes<HTMLTextAreaElement>) {
    return <textarea {...p} className={cn('bg-card min-h-20 w-full rounded-md border px-3 py-2 text-sm outline-none focus:ring-1 focus:ring-foreground/20', p.className)} />;
}

export function SelectInput({ options, placeholder, ...p }: SelectHTMLAttributes<HTMLSelectElement> & { options: [string | number, string][]; placeholder?: string }) {
    return (
        <select {...p} className={cn('bg-card h-9 w-full rounded-md border px-3 text-sm outline-none focus:ring-1 focus:ring-foreground/20', p.className)}>
            {placeholder !== undefined && <option value="">{placeholder}</option>}
            {options.map(([v, l]) => (
                <option key={v} value={v}>
                    {l}
                </option>
            ))}
        </select>
    );
}

export function Modal({ open, onClose, title, description, children, wide }: { open: boolean; onClose: () => void; title: string; description?: string; children: ReactNode; wide?: boolean }) {
    const t = useT();
    return (
        <Dialog open={open} onOpenChange={(o) => !o && onClose()}>
            <DialogContent className={cn('max-h-[90vh] overflow-y-auto', wide ? 'sm:max-w-3xl' : 'sm:max-w-md')}>
                <DialogHeader>
                    <DialogTitle>{t(title)}</DialogTitle>
                    {description && <DialogDescription>{t(description)}</DialogDescription>}
                </DialogHeader>
                {children}
            </DialogContent>
        </Dialog>
    );
}

export function ModalFooter({ onCancel, submitLabel = 'Save', processing }: { onCancel: () => void; submitLabel?: string; processing?: boolean }) {
    const t = useT();
    return (
        <div className="flex justify-end gap-2 pt-2">
            <ToolbarButton onClick={onCancel}>{t('Cancel')}</ToolbarButton>
            <ToolbarButton type="submit" primary disabled={processing}>
                {processing && <LoaderCircle className="animate-spin" />}
                {t(submitLabel)}
            </ToolbarButton>
        </div>
    );
}

const BADGES: Record<string, string> = {
    paid: 'bg-green-50 text-green-700 border-green-200',
    completed: 'bg-green-50 text-green-700 border-green-200',
    won: 'bg-green-50 text-green-700 border-green-200',
    winning: 'bg-green-50 text-green-700 border-green-200',
    active: 'bg-green-50 text-green-700 border-green-200',
    pending: 'bg-orange-50 text-orange-600 border-orange-200',
    outbid: 'bg-orange-50 text-orange-600 border-orange-200',
    paused: 'bg-orange-50 text-orange-600 border-orange-200',
    overdue: 'bg-red-50 text-red-600 border-red-200',
    lost: 'bg-red-50 text-red-600 border-red-200',
    cancelled: 'bg-gray-100 text-gray-600 border-gray-200',
    lot: 'bg-blue-50 text-blue-700 border-blue-200',
};

export function Badge({ children, tone, className }: { children: ReactNode; tone?: string; className?: string }) {
    const key = (tone ?? String(children)).toLowerCase();
    return (
        <span className={cn('inline-flex items-center rounded border px-1.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase', BADGES[key] ?? 'bg-muted text-muted-foreground border-transparent', className)}>
            {children}
        </span>
    );
}

export function Tabs({ tabs, current, hrefFor }: { tabs: [string, string][]; current: string; hrefFor: (key: string) => string }) {
    return (
        <div className="mb-4 flex gap-1 overflow-x-auto border-b">
            {tabs.map(([k, l]) => (
                <Link
                    key={k}
                    href={hrefFor(k)}
                    preserveScroll
                    className={cn('-mb-px border-b-2 px-2.5 py-2.5 text-xs font-medium whitespace-nowrap', current === k ? 'border-foreground text-foreground' : 'text-muted-foreground hover:text-foreground border-transparent')}
                >
                    {l}
                </Link>
            ))}
        </div>
    );
}

export function Tags({ tags }: { tags?: string[] | null }) {
    if (!tags?.length) return <span className="inline-flex size-5 items-center justify-center rounded border text-xs text-muted-foreground">+</span>;
    return (
        <div className="flex flex-wrap gap-1">
            {tags.map((t) => (
                <span key={t} className="rounded bg-indigo-50 px-1.5 py-0.5 text-[10px] font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                    {t}
                </span>
            ))}
        </div>
    );
}

export function TagInput({ value, onChange }: { value: string[]; onChange: (v: string[]) => void }) {
    return (
        <TextInput
            defaultValue={value.join(', ')}
            onBlur={(e) =>
                onChange(
                    e.target.value
                        .split(',')
                        .map((s) => s.trim())
                        .filter(Boolean),
                )
            }
            placeholder="comma, separated, tags"
        />
    );
}

export function CarImage({ src, className }: { src?: string | null; className?: string }) {
    const low = usePage<SharedData>().props.settings?.low_data_mode;
    if (src && !low) return <img src={src} loading="lazy" className={cn('h-11 w-16 rounded border object-cover', className)} />;
    return (
        <div className={cn('bg-muted/60 flex h-11 w-16 items-center justify-center rounded border', className)}>
            <Car className="text-muted-foreground/60 size-5" />
        </div>
    );
}

export function CheckList<T extends { id: number }>({ items, selected, onChange, render }: { items: T[]; selected: number[]; onChange: (ids: number[]) => void; render: (i: T) => ReactNode }) {
    const t = useT();
    return (
        <div className="max-h-72 space-y-1 overflow-y-auto rounded-md border p-2">
            {items.length === 0 && <p className="text-muted-foreground p-2 text-xs">{t('Nothing to choose from.')}</p>}
            {items.map((i) => (
                <label key={i.id} className="hover:bg-muted flex cursor-pointer items-center gap-2 rounded px-2 py-1.5 text-sm">
                    <input type="checkbox" checked={selected.includes(i.id)} onChange={(e) => onChange(e.target.checked ? [...selected, i.id] : selected.filter((x) => x !== i.id))} />
                    {render(i)}
                </label>
            ))}
        </div>
    );
}

export function InfoRow({ label, children }: { label: string; children: ReactNode }) {
    const t = useT();
    return (
        <div className="flex items-center justify-between gap-4 border-b px-5 py-3.5 text-sm last:border-0">
            <span className="text-muted-foreground text-xs">{t(label)}</span>
            <span className="text-end font-medium">{children ?? <span className="text-muted-foreground">{t('Not provided')}</span>}</span>
        </div>
    );
}

export function ColumnsButton({ columns, hidden, toggle }: { columns: { key: string; label: string }[]; hidden: string[]; toggle: (k: string) => void }) {
    const t = useT();
    return (
        <details className="relative">
            <summary className="bg-card hover:bg-muted flex h-9 cursor-pointer list-none items-center gap-1.5 rounded-md border px-3 text-xs font-medium shadow-xs">
                <Settings2 className="size-3.5" /> {t('Manage Columns')}
            </summary>
            <div className="bg-popover absolute end-0 z-30 mt-1 w-52 rounded-md border p-2 shadow-lg">
                {columns.map((c) => (
                    <label key={c.key} className="hover:bg-muted flex cursor-pointer items-center gap-2 rounded px-2 py-1 text-xs">
                        <input type="checkbox" checked={!hidden.includes(c.key)} onChange={() => toggle(c.key)} />
                        {t(c.label)}
                    </label>
                ))}
            </div>
        </details>
    );
}
