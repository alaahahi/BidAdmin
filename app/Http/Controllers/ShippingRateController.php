<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\ShippingRate;
use App\Support\Lists;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShippingRateController extends Controller
{
    public function index(Request $r)
    {
        $q = ShippingRate::query()->with('customers:id,name');
        Lists::search($q, $r->input('search'), ['name']);

        return Inertia::render('shipping/rates', [
            'rates' => Lists::sort($q, $r, ['name', 'valid_from', 'valid_until', 'towing', 'loading', 'surcharges'])->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, []),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
        ]);
    }

    private function validated(Request $r): array
    {
        return $r->validate([
            'name' => ['required', 'string', 'max:190'],
            'valid_from' => ['nullable', 'date'],
            'valid_until' => ['nullable', 'date', 'after_or_equal:valid_from'],
            'towing' => ['nullable', 'numeric', 'min:0'],
            'loading' => ['nullable', 'numeric', 'min:0'],
            'surcharges' => ['nullable', 'numeric', 'min:0'],
            'lines' => ['nullable', 'array'],
            'lines.*.yard' => ['required', 'string', 'max:100'],
            'lines.*.state' => ['nullable', 'string', 'max:10'],
            'lines.*.port' => ['required', 'string', 'max:100'],
            'lines.*.destination' => ['nullable', 'string', 'max:100'],
            'lines.*.price' => ['required', 'numeric', 'min:0'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer', 'exists:customers,id'],
        ]);
    }

    public function store(Request $r)
    {
        $data = $this->validated($r);
        $rate = ShippingRate::create(collect($data)->except('customer_ids')->map(fn ($v, $k) => in_array($k, ['towing', 'loading', 'surcharges']) ? ($v ?? 0) : $v)->all());
        $rate->customers()->sync($data['customer_ids'] ?? []);

        return back()->with('success', 'Shipping rate created.');
    }

    public function update(Request $r, ShippingRate $rate)
    {
        $data = $this->validated($r);
        $rate->update(collect($data)->except('customer_ids')->map(fn ($v, $k) => in_array($k, ['towing', 'loading', 'surcharges']) ? ($v ?? 0) : $v)->all());
        $rate->customers()->sync($data['customer_ids'] ?? []);

        return back()->with('success', 'Shipping rate updated.');
    }

    public function destroy(ShippingRate $rate)
    {
        $rate->delete();

        return back()->with('success', 'Shipping rate deleted.');
    }
}
