import { usePage } from '@inertiajs/react';
import type { SharedData } from '@/types';

export const PROVIDERS: Record<string, string> = { copart: 'Copart', iaai: 'IAAI', manheim: 'Manheim', edgepipeline: 'EdgePipeline', npa: 'NPA' };

export const REGION_FLAGS: Record<string, string> = { USA: '🇺🇸', CAN: '🇨🇦', UK: '🇬🇧', UAE: '🇦🇪', DE: '🇩🇪' };

export function money(v: string | number | null | undefined, opts: { cents?: boolean; currency?: string } = {}) {
    const n = Number(v ?? 0);
    const cents = opts.cents ?? !Number.isInteger(n);
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: opts.currency ?? 'USD',
        minimumFractionDigits: cents ? 2 : 0,
        maximumFractionDigits: cents ? 2 : 0,
    }).format(n);
}

export function num(v: number | string | null | undefined) {
    return new Intl.NumberFormat('en-US').format(Number(v ?? 0));
}

export function useTz() {
    return usePage<SharedData>().props.settings?.timezone || 'UTC';
}

function safeTz(tz: string) {
    try {
        new Intl.DateTimeFormat('en-US', { timeZone: tz });
        return tz;
    } catch {
        return 'UTC';
    }
}

/** Plain dates (Y-m-d) are formatted without timezone shifting. */
export function fmtDate(v: string | null | undefined, tz = 'UTC', style: 'long' | 'short' = 'short') {
    if (!v) return '—';
    const dateOnly = /^\d{4}-\d{2}-\d{2}$/.test(v) || /T00:00:00(\.0+)?Z$/.test(v);
    const d = new Date(dateOnly ? v.slice(0, 10) + 'T12:00:00Z' : v);
    if (isNaN(d.getTime())) return v;
    return new Intl.DateTimeFormat('en-US', {
        timeZone: dateOnly ? 'UTC' : safeTz(tz),
        month: style === 'long' ? 'long' : 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(d);
}

export function fmtDateTime(v: string | null | undefined, tz = 'UTC') {
    if (!v) return '—';
    const d = new Date(v);
    if (isNaN(d.getTime())) return v;
    return new Intl.DateTimeFormat('en-US', {
        timeZone: safeTz(tz),
        month: 'short',
        day: 'numeric',
        year: 'numeric',
        hour: 'numeric',
        minute: '2-digit',
    }).format(d);
}

export function relative(v: string | null | undefined) {
    if (!v) return '—';
    const diff = (Date.now() - new Date(v).getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    return `${Math.floor(diff / 86400)}d ago`;
}

export function mask(v: string | null | undefined, hide: boolean) {
    if (!v) return v ?? '';
    if (!hide) return v;
    if (v.length <= 3) return '•••';
    return v.slice(0, 2) + '•'.repeat(Math.min(8, v.length - 3)) + v.slice(-1);
}

export function titleCase(s: string | null | undefined) {
    return (s ?? '').replace(/_/g, ' ').replace(/\b\w/g, (c) => c.toUpperCase());
}
