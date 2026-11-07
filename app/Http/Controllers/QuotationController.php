<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Quotation;
use App\Models\Setting;
use App\Models\ShippingRate;
use App\Support\Lists;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuotationController extends Controller
{
    public function index(Request $r)
    {
        $q = Quotation::query()->with(['shippingRate:id,name', 'customer:id,name']);
        Lists::search($q, $r->input('search'), ['vin', 'lot_number', 'description']);

        return Inertia::render('shipping/quotations', [
            'quotations' => Lists::sort($q, $r, ['description', 'sale_amount', 'shipping', 'total', 'created_at'])->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, []),
            'rates' => ShippingRate::orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate([
            'shipping_rate_id' => ['required', 'exists:shipping_rates,id'],
            'line' => ['required', 'integer', 'min:0'],
            'customer_id' => ['nullable', 'exists:customers,id'],
            'lot_number' => ['nullable', 'string', 'max:40'],
            'vin' => ['nullable', 'string', 'max:40'],
            'description' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'url', 'max:500'],
            'delivery_port' => ['nullable', 'string', 'max:100'],
            'sale_amount' => ['required', 'numeric', 'min:0'],
        ]);
        $rate = ShippingRate::findOrFail($data['shipping_rate_id']);
        $line = ($rate->lines ?? [])[$data['line']] ?? null;
        abort_unless($line, 422, 'Invalid rate line.');
        $shipping = (float) $line['price'] + (float) $rate->towing + (float) $rate->loading + (float) $rate->surcharges;
        Quotation::create(collect($data)->except('line')->all() + [
            'pickup_yard' => $line['yard'].(! empty($line['state']) ? ', '.$line['state'] : ''),
            'loading_port' => $line['port'],
            'delivery_port' => $data['delivery_port'] ?? ($line['destination'] ?? null),
            'shipping' => $shipping,
        ]);

        return back()->with('success', 'Quote created.');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return back()->with('success', 'Quote deleted.');
    }

    /** Public quote page shared with customers by URL. */
    public function public(string $token)
    {
        $quote = Quotation::where('token', $token)->with('shippingRate')->firstOrFail();

        return view('quote', ['quote' => $quote, 'settings' => Setting::all_settings()]);
    }
}
