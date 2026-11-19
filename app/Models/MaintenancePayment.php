<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MaintenancePayment extends Model
{
    protected $fillable = [
        'system_id',
        'finance_transaction_id',
        'due_date',
        'amount',
        'currency',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function financeTransaction()
    {
        return $this->belongsTo(FinanceTransaction::class);
    }

    public function formatAmount(): string
    {
        return System::formatMoney((float) $this->amount, $this->currency);
    }
}
