<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceTransaction extends Model
{
    public const TYPES = [
        'income' => 'Income',
        'expense' => 'Expense',
    ];

    public const CATEGORIES = [
        'build_fee' => 'Build / Development Fee',
        'client_payment' => 'Client Payment',
        'subscription' => 'Subscription Revenue',
        'product_sale' => 'Product Sale',
        'maintenance_fee' => 'Maintenance Fee',
        'other_income' => 'Other Income',
        'hosting' => 'Hosting / Server',
        'domain' => 'Domain',
        'software_license' => 'Software License',
        'setup_fee' => 'Setup / Installation',
        'developer_fee' => 'Developer / Dev Fee',
        'tools' => 'Tools / Equipment',
        'advertising' => 'Advertising',
        'salary' => 'Salary / Contract',
        'other_expense' => 'Other Expense',
    ];

    public const INCOME_CATEGORIES = [
        'build_fee', 'client_payment', 'subscription', 'product_sale', 'maintenance_fee', 'other_income',
    ];

    public const EXPENSE_CATEGORIES = [
        'hosting', 'domain', 'software_license', 'setup_fee', 'developer_fee', 'tools', 'advertising', 'salary', 'other_expense',
    ];

    protected $fillable = [
        'system_id',
        'type',
        'category',
        'title',
        'amount',
        'currency',
        'transaction_date',
        'description',
        'created_by_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function system()
    {
        return $this->belongsTo(System::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function categoryLabel(): string
    {
        return self::CATEGORIES[$this->category] ?? 'Other';
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? 'Income';
    }

    public function formatAmount(): string
    {
        $formatted = number_format((float) $this->amount, $this->currency === 'KHR' ? 0 : 2);

        return match ($this->currency) {
            'KHR' => '៛'.$formatted,
            'USD' => '$'.$formatted,
            default => $this->currency.' '.$formatted,
        };
    }
}
