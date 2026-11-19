<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMilestone extends Model
{
    protected $fillable = [
        'system_id',
        'finance_transaction_id',
        'title',
        'percentage',
        'amount',
        'currency',
        'status',
        'paid_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:2',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'sort_order' => 'integer',
        ];
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function financeTransaction()
    {
        return $this->belongsTo(FinanceTransaction::class, 'finance_transaction_id');
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function formatAmount(): string
    {
        return System::formatMoney((float) $this->amount, $this->currency);
    }
}
