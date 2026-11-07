<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\PhoneNumber;
use App\Support\Lists;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/** UI-only number management: no real telephony purchasing happens here. */
class PhoneNumberController extends Controller
{
    public function index(Request $r)
    {
        $q = PhoneNumber::query()->with('account:id,username,provider');

        return Inertia::render('numbers/index', [
            'numbers' => Lists::sort($q, $r, ['number', 'status', 'next_bill_at'])->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, []),
            'accounts' => Account::orderBy('username')->get(['id', 'username', 'provider']),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['country' => ['required', Rule::in(['US', 'CA', 'GB'])], 'account_id' => ['nullable', 'exists:accounts,id']]);
        $prefix = ['US' => '+1 (555) ', 'CA' => '+1 (604) ', 'GB' => '+44 7700 '][$data['country']];
        PhoneNumber::create($data + ['number' => $prefix.random_int(100, 999).'-'.random_int(1000, 9999), 'status' => 'active', 'next_bill_at' => now()->addMonth()->toDateString()]);

        return back()->with('success', 'Number added (demo, no real purchase).');
    }

    public function update(Request $r, PhoneNumber $number)
    {
        $data = $r->validate(['account_id' => ['nullable', 'exists:accounts,id'], 'status' => ['nullable', Rule::in(['active', 'paused'])]]);
        if (empty($data['status'])) {
            unset($data['status']);
        }
        $number->update($data);

        return back()->with('success', 'Number updated.');
    }

    public function destroy(PhoneNumber $number)
    {
        $number->delete();

        return back()->with('success', 'Number released.');
    }
}
