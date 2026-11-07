<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'amount_due' => 'decimal:2', 'sale_price' => 'decimal:2', 'fees' => 'decimal:2', 'invoice_date' => 'date', 'due_date' => 'date', 'paid_at' => 'date'];
    }

    protected static function booted(): void
    {
        static::saving(function (Invoice $invoice) {
            if (! $invoice->won_by_customer_id) {
                $invoice->won_by_customer_id = static::resolveWinner($invoice);
            }
            if ($invoice->status === 'paid') {
                $invoice->amount_due = 0;
                $invoice->paid_at ??= now();
            } elseif ($invoice->status === 'pending' && $invoice->due_date && $invoice->due_date->isPast() && ! $invoice->due_date->isToday()) {
                $invoice->status = 'overdue';
            }
            if (! $invoice->provider && $invoice->account_id) {
                $invoice->provider = Account::withTrashed()->find($invoice->account_id)?->provider;
            }
        });
    }

    /** Customer that placed the winning (or otherwise highest/latest) bid on this lot. */
    public static function resolveWinner(Invoice $invoice): ?int
    {
        if (! $invoice->invoice_number) {
            return null;
        }
        $q = Bid::where('lot_number', $invoice->invoice_number)->whereNotNull('customer_id');
        if ($invoice->account_id) {
            $q->where(fn ($w) => $w->where('account_id', $invoice->account_id)->orWhereNull('account_id'));
        }
        $won = (clone $q)->where('status', 'won')->latest('placed_at')->first();

        return ($won ?? $q->orderByDesc('amount')->latest('placed_at')->first())?->customer_id;
    }

    public function account()
    {
        return $this->belongsTo(Account::class)->withTrashed();
    }

    public function wonBy()
    {
        return $this->belongsTo(Customer::class, 'won_by_customer_id')->withTrashed();
    }

    public function bids()
    {
        return $this->hasMany(Bid::class, 'lot_number', 'invoice_number');
    }

    public function activities()
    {
        return $this->hasMany(ActivityLog::class, 'target', 'invoice_number');
    }
}
