<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Broker;
use App\Support\Csv;
use App\Support\Lists;
use Illuminate\Http\Request;
use Inertia\Inertia;

class BrokerController extends Controller
{
    private function query(Request $r)
    {
        $q = Broker::query()->with('accounts:id,username,provider,region');
        if ($r->input('trashed') === '1') {
            $q->onlyTrashed();
        }
        Lists::search($q, $r->input('search'), ['name', 'email', 'accounts.username']);
        if ($r->filled('account_id')) {
            $q->whereHas('accounts', fn ($a) => $a->where('accounts.id', $r->input('account_id')));
        }

        return Lists::sort($q, $r, ['name', 'email', 'created_at']);
    }

    public function index(Request $r)
    {
        return Inertia::render('brokers/index', [
            'brokers' => $this->query($r)->paginate(Lists::perPage($r))->withQueryString(),
            'filters' => Lists::filters($r, ['account_id']),
            'stats' => ['active' => Broker::count()],
            'allAccounts' => Account::orderBy('username')->get(['id', 'username', 'provider', 'region']),
        ]);
    }

    public function export(Request $r)
    {
        return Csv::download('brokers', $this->query($r)->cursor(), [
            'Name' => 'name', 'Email' => 'email', 'Covered' => fn ($b) => $b->covered ? 'yes' : 'no',
            'Accounts' => fn ($b) => $b->accounts->pluck('username')->implode('|'),
        ]);
    }

    public function store(Request $r)
    {
        $data = $r->validate(['name' => ['required', 'string', 'max:190'], 'email' => ['nullable', 'email', 'max:190'], 'covered' => ['boolean'], 'account_ids' => ['array'], 'account_ids.*' => ['integer', 'exists:accounts,id']]);
        $broker = Broker::create(collect($data)->except('account_ids')->all());
        $this->assign($broker, $data['account_ids'] ?? []);

        return back()->with('success', 'Broker added.');
    }

    public function update(Request $r, Broker $broker)
    {
        $data = $r->validate(['name' => ['required', 'string', 'max:190'], 'email' => ['nullable', 'email', 'max:190'], 'covered' => ['boolean'], 'account_ids' => ['array'], 'account_ids.*' => ['integer', 'exists:accounts,id']]);
        $broker->update(collect($data)->except('account_ids')->all());
        if ($r->has('account_ids')) {
            $this->assign($broker, $data['account_ids'] ?? []);
        }

        return back()->with('success', 'Broker updated.');
    }

    private function assign(Broker $broker, array $ids): void
    {
        $ids = array_map('intval', $ids);
        $current = $broker->accounts()->pluck('accounts.id')->all();
        foreach (Account::whereIn('id', array_unique(array_merge($ids, $current)))->get() as $account) {
            $bIds = $account->brokers()->pluck('brokers.id')->all();
            $bIds = in_array($account->id, $ids) ? array_unique(array_merge($bIds, [$broker->id])) : array_values(array_diff($bIds, [$broker->id]));
            AccountController::syncWithHistory($account, 'broker', $bIds);
        }
    }

    public function destroy(Broker $broker)
    {
        $broker->delete();

        return back()->with('success', 'Broker deleted.');
    }

    public function restore(int $id)
    {
        Broker::onlyTrashed()->findOrFail($id)->restore();

        return back()->with('success', 'Broker restored.');
    }
}
