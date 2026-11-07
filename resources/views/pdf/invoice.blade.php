<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Invoice {{ $invoice->invoice_number }}</title>
<style>
body{font-family:DejaVu Sans,sans-serif;font-size:12px;color:#111827}
h1{font-size:22px;margin:0}.muted{color:#6b7280}
table{width:100%;border-collapse:collapse;margin-top:16px}
th{background:{{ $settings['brand_color'] ?? '#111827' }};color:#fff;text-align:left;padding:8px;font-size:11px}
td{padding:8px;border-bottom:1px solid #e5e7eb}.right{text-align:right}
.badge{display:inline-block;padding:2px 8px;border-radius:4px;background:#f3f4f6;text-transform:uppercase;font-size:10px}
</style></head>
<body>
<table style="margin-top:0"><tr>
<td style="border:none"><h1>{{ $settings['company_name'] ?? 'BidAdmin' }}</h1><div class="muted">Vehicle invoice</div></td>
<td style="border:none" class="right"><strong>Invoice #{{ $invoice->invoice_number }}</strong><br>
<span class="muted">Date: {{ optional($invoice->invoice_date)->format('M j, Y') }}</span><br>
<span class="muted">Due: {{ optional($invoice->due_date)->format('M j, Y') }}</span><br>
<span class="badge">{{ $invoice->status }}</span></td></tr></table>

<table>
<tr><th>Account</th><th>Provider</th><th>Won By</th></tr>
<tr><td>{{ $invoice->account?->username }}<br><span class="muted">{{ $invoice->account?->company }}</span></td>
<td>{{ \App\Models\Account::PROVIDERS[$invoice->provider] ?? $invoice->provider }}</td>
<td>{{ $invoice->wonBy?->name ?? '—' }}<br><span class="muted">{{ $invoice->wonBy?->code }}</span></td></tr>
</table>

<table>
<tr><th>Description</th><th>VIN</th><th>Location</th><th class="right">Amount</th></tr>
<tr><td>{{ $invoice->description }}</td><td>{{ $invoice->vin }}</td><td>{{ $invoice->location }}</td><td class="right">${{ number_format($invoice->amount, 2) }}</td></tr>
@if($invoice->sale_price)<tr><td colspan="3" class="right muted">Sale price</td><td class="right">${{ number_format($invoice->sale_price, 2) }}</td></tr>@endif
@if($invoice->fees)<tr><td colspan="3" class="right muted">Fees</td><td class="right">${{ number_format($invoice->fees, 2) }}</td></tr>@endif
<tr><td colspan="3" class="right"><strong>Amount due</strong></td><td class="right"><strong>${{ number_format($invoice->amount_due, 2) }}</strong></td></tr>
</table>
@if($invoice->notes)<p class="muted" style="margin-top:20px">Notes: {{ $invoice->notes }}</p>@endif
</body></html>
