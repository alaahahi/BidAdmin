import { useT } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { Filters, Paginated } from '@/types';
import { router } from '@inertiajs/react';
import { ArrowDown, ArrowUp, ChevronDown, ChevronsUpDown, Download, EyeOff, Eye, FilterX, LoaderCircle, RefreshCw, RotateCcw, Trash2 } from 'lucide-react';
import { ReactNode, useEffect, useRef, useState } from 'react';
import { ToolbarButton } from './ui';

function useStored<T>(key: string, initial: T): [T, (v: T) => void] {
    const [v, setV] = useState<T>(() => {
        try {
            const s = localStorage.getItem(key);
            return s ? (JSON.parse(s) as T) : initial;
        } catch {
            return initial;
        }
    });
    return [
        v,
        (nv: T) => {
            setV(nv);
            localStorage.setItem(key, JSON.stringify(nv));
        },
    ];
}

export function useSensitive() {
    return useStored<boolean>('ba.hideSensitive', false);
}

/** URL-driven list state: filters, search, sorting, paging, plus local UI prefs. */
export function useList(name: string, url: string, filters: Filters) {
    const [search, setSearch] = useState(String(filters.search ?? ''));
    const [loading, setLoading] = useState(false);
    const [filtersHidden, setFiltersHidden] = useStored<boolean>(`ba.${name}.filtersHidden`, false);
    const [hiddenCols, setHiddenCols] = useStored<string[]>(`ba.${name}.hiddenCols`, []);
    const [hideSensitive, setHideSensitive] = useSensitive();
    const first = useRef(true);

    const apply = (patch: Filters, keepPage = false) => {
        const next: Filters = { ...filters, ...patch };
        if (!keepPage) delete next.page;
        Object.keys(next).forEach((k) => (next[k] === '' || next[k] === undefined || next[k] === null) && delete next[k]);
        router.get(url, next as Record<string, string>, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    useEffect(() => {
        if (first.current) {
            first.current = false;
            return;
        }
        const t = setTimeout(() => apply({ search }), 350);
        return () => clearTimeout(t);
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [search]);

    const reset = () => {
        setSearch('');
        router.get(url, {}, { preserveState: false, replace: true });
    };

    const refresh = () => router.reload({ onStart: () => setLoading(true), onFinish: () => setLoading(false) });

    const sort = (key: string) => {
        const dir = filters.sort === key && filters.dir === 'asc' ? 'desc' : 'asc';
        apply({ sort: key, dir });
    };

    const toggleCol = (k: string) => setHiddenCols(hiddenCols.includes(k) ? hiddenCols.filter((x) => x !== k) : [...hiddenCols, k]);

    return { filters, search, setSearch, apply, reset, refresh, sort, loading, filtersHidden, setFiltersHidden, hiddenCols, toggleCol, hideSensitive, setHideSensitive };
}

export type ListState = ReturnType<typeof useList>;

/** Standard toolbar: Hard Refresh, Show Deleted, Hide Sensitive Info, Reset Filters, Hide Filters, Export. */
export function ListToolbar({ list, exportUrl, deleted, sensitive = true, extra, before }: { list: ListState; exportUrl?: string; deleted?: boolean; sensitive?: boolean; extra?: ReactNode; before?: ReactNode }) {
    const t = useT();
    const trashed = list.filters.trashed === '1';
    return (
        <>
            {before}
            <ToolbarButton icon={<RefreshCw className={cn(list.loading && 'animate-spin')} />} onClick={list.refresh}>
                {t('Hard Refresh')}
            </ToolbarButton>
            {deleted && (
                <ToolbarButton icon={<Trash2 />} onClick={() => list.apply({ trashed: trashed ? '' : '1' })} className={trashed ? 'border-foreground' : ''}>
                    {trashed ? t('Hide Deleted') : t('Show Deleted')}
                </ToolbarButton>
            )}
            {sensitive && (
                <ToolbarButton icon={list.hideSensitive ? <Eye /> : <EyeOff />} onClick={() => list.setHideSensitive(!list.hideSensitive)}>
                    {list.hideSensitive ? t('Show Sensitive Info') : t('Hide Sensitive Info')}
                </ToolbarButton>
            )}
            <ToolbarButton icon={<RotateCcw />} onClick={list.reset}>
                {t('Reset Filters')}
            </ToolbarButton>
            <ToolbarButton icon={list.filtersHidden ? <ChevronDown /> : <FilterX />} onClick={() => list.setFiltersHidden(!list.filtersHidden)}>
                {list.filtersHidden ? t('Show Filters') : t('Hide Filters')}
            </ToolbarButton>
            {exportUrl && (
                <a href={exportUrl} className="bg-card hover:bg-muted inline-flex h-8 items-center gap-1.5 rounded-md border px-3 text-xs font-medium shadow-xs">
                    <Download className="size-3.5" /> {t('Export')}
                </a>
            )}
            {extra}
        </>
    );
}

export interface Column<T> {
    key: string;
    label: string;
    sortKey?: string;
    render: (row: T) => ReactNode;
    className?: string;
    headClassName?: string;
}

export function DataTable<T extends { id: number | string }>({ columns, rows, list, empty, rowClassName }: { columns: Column<T>[]; rows: T[]; list?: ListState; empty?: string; rowClassName?: (r: T) => string }) {
    const t = useT();
    const visible = columns.filter((c) => !list?.hiddenCols.includes(c.key));
    return (
        <div className="bg-card relative overflow-x-auto rounded-xl border shadow-xs">
            <table className="w-full text-xs">
                <thead>
                    <tr className="border-b">
                        {visible.map((c) => {
                            const active = list?.filters.sort === c.sortKey;
                            return (
                                <th key={c.key} className={cn('text-muted-foreground px-3.5 py-3 text-start text-[10px] font-medium tracking-wider whitespace-nowrap uppercase', c.headClassName)}>
                                    {c.sortKey && list ? (
                                        <button type="button" onClick={() => list.sort(c.sortKey!)} className="hover:text-foreground inline-flex items-center gap-1 uppercase">
                                            {t(c.label)}
                                            {active ? list.filters.dir === 'asc' ? <ArrowUp className="size-3" /> : <ArrowDown className="size-3" /> : <ChevronsUpDown className="size-3 opacity-60" />}
                                        </button>
                                    ) : (
                                        t(c.label)
                                    )}
                                </th>
                            );
                        })}
                    </tr>
                </thead>
                <tbody>
                    {rows.length === 0 && (
                        <tr>
                            <td colSpan={visible.length} className="text-muted-foreground py-6 text-center">
                                {list?.loading ? (
                                    <span className="inline-flex items-center gap-2">
                                        <LoaderCircle className="size-4 animate-spin" /> {t('Loading...')}
                                    </span>
                                ) : (
                                    (empty ?? t('No results found'))
                                )}
                            </td>
                        </tr>
                    )}
                    {rows.map((r) => (
                        <tr key={r.id} className={cn('hover:bg-muted/40 border-b last:border-0', rowClassName?.(r))}>
                            {visible.map((c) => (
                                <td key={c.key} className={cn('px-3.5 py-3 align-middle', c.className)}>
                                    {c.render(r)}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
            {list?.loading && rows.length > 0 && <div className="bg-background/40 absolute inset-0" />}
        </div>
    );
}

export function Pagination({ page, list, pageParam = 'page' }: { page: Paginated<unknown>; list?: ListState; pageParam?: string }) {
    const t = useT();
    const go = (p: number) => {
        if (list && pageParam === 'page') list.apply({ page: p }, true);
        else router.get(window.location.pathname, { ...Object.fromEntries(new URLSearchParams(window.location.search)), [pageParam]: p }, { preserveState: true, preserveScroll: true });
    };
    const btn = 'bg-card h-8 rounded-md border px-3 text-xs text-muted-foreground hover:bg-muted disabled:opacity-50 disabled:hover:bg-card';
    return (
        <div className="mt-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div>{page.total ? `${t('Showing')} ${page.from} ${t('to')} ${page.to} ${t('of')} ${page.total} ${t('results')}` : t('No results found')}</div>
            <div className="flex items-center gap-2">
                {list && (
                    <label className="flex items-center gap-2">
                        {t('per page:')}
                        <select value={page.per_page} onChange={(e) => list.apply({ per_page: e.target.value })} className="bg-card h-8 rounded-md border px-2">
                            {[10, 25, 50, 100].map((n) => (
                                <option key={n}>{n}</option>
                            ))}
                        </select>
                    </label>
                )}
                <button className={btn} disabled={page.current_page <= 1} onClick={() => go(1)}>
                    {t('First')}
                </button>
                <button className={btn} disabled={page.current_page <= 1} onClick={() => go(page.current_page - 1)}>
                    {t('Previous')}
                </button>
                {page.total > 0 && (
                    <span>
                        {t('Page')} {page.current_page} {t('of')} {page.last_page}
                    </span>
                )}
                <button className={btn} disabled={page.current_page >= page.last_page} onClick={() => go(page.current_page + 1)}>
                    {t('Next')}
                </button>
                <button className={btn} disabled={page.current_page >= page.last_page} onClick={() => go(page.last_page)}>
                    {t('Last')}
                </button>
            </div>
        </div>
    );
}

export function FilterRow({ list, children, cols = 4 }: { list: ListState; children: ReactNode; cols?: number }) {
    if (list.filtersHidden) return null;
    const grid: Record<number, string> = { 3: 'md:grid-cols-3', 4: 'md:grid-cols-4', 5: 'md:grid-cols-5', 6: 'md:grid-cols-6', 7: 'md:grid-cols-7' };
    return <div className={cn('mb-4 grid grid-cols-2 gap-2', grid[cols])}>{children}</div>;
}
