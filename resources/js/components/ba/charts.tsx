import { money, num } from '@/lib/format';
import type { AnalyticsData } from '@/types';
import { Area, AreaChart, Bar, BarChart, CartesianGrid, Cell, Legend, Pie, PieChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { useState } from 'react';
import { Card, CardHeader, StatCard } from './ui';

export const COLORS = ['#111827', '#2563eb', '#16a34a', '#f59e0b', '#dc2626', '#7c3aed', '#0891b2', '#db2777'];

export function MetricCards({ data, label }: { data: AnalyticsData; label: string }) {
    const m = data.metrics;
    const cards: [string, number, string?][] = [
        ['Total Actions', m.total_actions],
        ['Logins', m.logins],
        ['Lots Viewed', m.lots_viewed],
        ['Bids Placed', m.bids_placed],
        ['Cars Won', m.cars_won],
        ['Watchlist Adds', m.watchlist_adds],
        ['Watchlist Removes', m.watchlist_removes],
        ['Active Customers', m.active_customers, 'Unique in period'],
    ];
    return (
        <div className="mb-4 grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-8">
            {cards.map(([l, v, h], i) => (
                <StatCard key={l} label={l} value={num(v)} hint={h ?? label} className={i === 0 ? 'border-foreground/60' : ''} />
            ))}
        </div>
    );
}

export function DailyChart({ data, height = 280 }: { data: AnalyticsData['daily']; height?: number }) {
    const short = data.map((d) => ({ ...d, label: String(d.date).slice(5) }));
    return (
        <ResponsiveContainer width="100%" height={height}>
            <AreaChart data={short} margin={{ left: -20, right: 8, top: 8 }}>
                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" vertical={false} />
                <XAxis dataKey="label" fontSize={10} tickLine={false} axisLine={false} />
                <YAxis fontSize={10} tickLine={false} axisLine={false} allowDecimals={false} />
                <Tooltip contentStyle={{ fontSize: 12 }} />
                <Legend wrapperStyle={{ fontSize: 11 }} />
                <Area type="monotone" dataKey="lot_view" name="Lots viewed" stroke="#2563eb" fill="#2563eb22" />
                <Area type="monotone" dataKey="bid" name="Bids" stroke="#111827" fill="#11182722" />
                <Area type="monotone" dataKey="watchlist_add" name="Watchlist adds" stroke="#f59e0b" fill="#f59e0b22" />
                <Area type="monotone" dataKey="won" name="Won" stroke="#16a34a" fill="#16a34a33" />
            </AreaChart>
        </ResponsiveContainer>
    );
}

export function Donut({ rows, valueKey = 'count' }: { rows: { name: string; count: number; value?: number }[]; valueKey?: 'count' | 'value' }) {
    if (!rows.length) return <p className="text-muted-foreground py-10 text-center text-sm">No data in this period.</p>;
    return (
        <div className="flex flex-wrap items-center gap-6">
            <div className="h-40 w-40">
                <ResponsiveContainer>
                    <PieChart>
                        <Pie data={rows} dataKey={valueKey} nameKey="name" innerRadius={40} outerRadius={70} paddingAngle={2}>
                            {rows.map((_, i) => (
                                <Cell key={i} fill={COLORS[i % COLORS.length]} />
                            ))}
                        </Pie>
                        <Tooltip contentStyle={{ fontSize: 12 }} />
                    </PieChart>
                </ResponsiveContainer>
            </div>
            <ul className="min-w-48 flex-1 space-y-2 text-sm">
                {rows.map((r, i) => (
                    <li key={r.name} className="flex items-center justify-between gap-3">
                        <span className="flex items-center gap-2">
                            <span className="size-2.5 rounded-full" style={{ background: COLORS[i % COLORS.length] }} />
                            {r.name}
                        </span>
                        <span className="text-muted-foreground text-xs">
                            {r.count} {r.value !== undefined && `· ${money(r.value)}`}
                        </span>
                    </li>
                ))}
            </ul>
        </div>
    );
}

function TopList({ title, rows }: { title: string; rows: { label: string; actions: number; bids: number; won: number }[] }) {
    return (
        <Card>
            <CardHeader title={title} />
            <div className="divide-y">
                {rows.length === 0 && <p className="text-muted-foreground p-5 text-sm">No activity.</p>}
                {rows.map((r, i) => (
                    <div key={r.label + i} className="flex items-center justify-between px-5 py-2.5 text-sm">
                        <span className="flex items-center gap-2">
                            <span className="text-muted-foreground w-4 text-xs">{i + 1}</span>
                            <span className="font-medium">{r.label}</span>
                        </span>
                        <span className="text-muted-foreground text-xs">
                            {r.actions} actions · {r.bids} bids · {r.won} won
                        </span>
                    </div>
                ))}
            </div>
        </Card>
    );
}

export function AnalyticsView({ data, periodLabel = 'In period' }: { data: AnalyticsData; periodLabel?: string }) {
    const [mode, setMode] = useState<'all' | 'inside'>('all');
    return (
        <div className="space-y-4">
            <MetricCards data={data} label={periodLabel} />
            <Card>
                <CardHeader
                    title="Purchased Vehicles"
                    subtitle="Every car your accounts won"
                    actions={
                        <div className="bg-muted flex rounded-full p-0.5 text-[11px]">
                            {(['all', 'inside'] as const).map((m) => (
                                <button key={m} onClick={() => setMode(m)} className={`rounded-full px-3 py-1 ${mode === m ? 'brand-bg text-white' : ''}`}>
                                    {m === 'all' ? 'All won' : 'By customer'}
                                </button>
                            ))}
                        </div>
                    }
                />
                <div className="p-5">
                    <Donut rows={data.purchased[mode]} />
                </div>
            </Card>
            <Card>
                <CardHeader title="Platform Activity" subtitle="All customers & accounts combined" />
                <div className="p-5">
                    <DailyChart data={data.daily} />
                </div>
            </Card>
            <div className="grid gap-4 lg:grid-cols-3">
                <TopList title="Top Customers" rows={data.top_customers} />
                <TopList title="Top Accounts" rows={data.top_accounts} />
                <Card>
                    <CardHeader title="Top Vehicles" />
                    <div className="divide-y">
                        {data.top_vehicles.length === 0 && <p className="text-muted-foreground p-5 text-sm">No activity.</p>}
                        {data.top_vehicles.map((v) => (
                            <div key={v.lot} className="px-5 py-2.5 text-sm">
                                <div className="font-medium">{v.description ?? v.lot}</div>
                                <div className="text-muted-foreground text-xs">
                                    Lot {v.lot} · {v.views} views · {v.bids} bids
                                </div>
                            </div>
                        ))}
                    </div>
                </Card>
            </div>
            <div className="grid gap-4 lg:grid-cols-3">
                <Card>
                    <CardHeader title="Activity Breakdown" />
                    <div className="h-56 p-4">
                        <ResponsiveContainer>
                            <BarChart data={data.by_type} layout="vertical" margin={{ left: 30 }}>
                                <XAxis type="number" hide />
                                <YAxis type="category" dataKey="label" fontSize={10} width={90} tickLine={false} axisLine={false} />
                                <Tooltip contentStyle={{ fontSize: 12 }} />
                                <Bar dataKey="count" fill="#111827" radius={[0, 4, 4, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                </Card>
                <Card>
                    <CardHeader title="By Provider" />
                    <div className="p-5">
                        <Donut rows={data.by_provider} />
                    </div>
                </Card>
                <Card>
                    <CardHeader title="By App" />
                    <div className="p-5">
                        <Donut rows={data.by_app} />
                    </div>
                </Card>
            </div>
            <Card>
                <CardHeader title="Daily Breakdown" />
                <div className="overflow-x-auto">
                    <table className="w-full text-xs">
                        <thead>
                            <tr className="text-muted-foreground border-b text-[10px] uppercase">
                                {['Date', 'Logins', 'Lots Viewed', 'Bids', 'Watchlist +', 'Watchlist −', 'Won', 'Total'].map((h) => (
                                    <th key={h} className="px-4 py-2 text-start font-medium">
                                        {h}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {[...data.daily].reverse().map((d) => (
                                <tr key={String(d.date)} className="border-b last:border-0">
                                    <td className="px-4 py-2 font-medium">{d.date}</td>
                                    <td className="px-4 py-2">{d.login}</td>
                                    <td className="px-4 py-2">{d.lot_view}</td>
                                    <td className="px-4 py-2">{d.bid}</td>
                                    <td className="px-4 py-2">{d.watchlist_add}</td>
                                    <td className="px-4 py-2">{d.watchlist_remove}</td>
                                    <td className="px-4 py-2 text-green-600">{d.won}</td>
                                    <td className="px-4 py-2 font-semibold">{d.total}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </Card>
        </div>
    );
}
