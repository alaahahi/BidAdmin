<!DOCTYPE html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
<title>Quote — {{ $quote->description }}</title>
<style>
body{font-family:system-ui,-apple-system,Segoe UI,sans-serif;background:#f9fafb;color:#111827;margin:0;padding:32px}
.card{max-width:640px;margin:auto;background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:28px}
h1{font-size:20px;margin:0 0 4px}.muted{color:#6b7280;font-size:13px}
table{width:100%;border-collapse:collapse;margin-top:20px}td{padding:10px 0;border-bottom:1px solid #f3f4f6;font-size:14px}
td:last-child{text-align:right;font-weight:600}.total td{font-size:18px;border:none}
.bar{height:6px;background:{{ $settings['brand_color'] ?? '#111827' }};border-radius:6px 6px 0 0;margin:-28px -28px 24px}
</style></head><body>
<div class="card"><div class="bar"></div>
<div class="muted">{{ $settings['company_name'] ?? 'BidAdmin' }} · Shipping quotation</div>
<h1>{{ $quote->description }}</h1>
<div class="muted">Lot {{ $quote->lot_number }} · VIN {{ $quote->vin }}</div>
<table>
<tr><td>Pickup yard</td><td>{{ $quote->pickup_yard }}</td></tr>
<tr><td>Loading port</td><td>{{ $quote->loading_port }}</td></tr>
<tr><td>Delivery port</td><td>{{ $quote->delivery_port }}</td></tr>
<tr><td>Sale amount</td><td>${{ number_format($quote->sale_amount, 2) }}</td></tr>
<tr><td>Shipping (towing, loading, ocean, surcharges)</td><td>${{ number_format($quote->shipping, 2) }}</td></tr>
<tr class="total"><td>Total</td><td>${{ number_format($quote->total, 2) }}</td></tr>
</table>
<p class="muted">Rate list: {{ $quote->shippingRate?->name }} @if($quote->shippingRate?->valid_until) · valid until {{ $quote->shippingRate->valid_until->format('M j, Y') }} @endif</p>
</div></body></html>
