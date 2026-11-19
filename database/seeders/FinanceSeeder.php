<?php

namespace Database\Seeders;

use App\Models\FinanceTransaction;
use App\Models\System;
use App\Models\User;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::where('email', 'admin@fstech.com')->first();
        $systemIds = System::pluck('id', 'name');

        $rows = [
            ['Poss-System', 'income', 'build_fee', 'Poss-System build charge — initial development', 1500, 'USD', now()->subDays(90)],
            ['Poss-System', 'income', 'client_payment', 'Poss-System license — September', 450, 'USD', now()->subDays(40)],
            ['Poss-System', 'income', 'maintenance_fee', 'Monthly maintenance retainer', 200, 'USD', now()->subDays(10)],
            ['Poss-System', 'expense', 'hosting', 'VPS hosting — September', 35, 'USD', now()->subDays(35)],
            ['Poss-System', 'expense', 'software_license', 'KHQR payment gateway license', 60, 'USD', now()->subDays(30)],
            ['School System', 'income', 'build_fee', 'School System build charge — full deployment', 2000, 'USD', now()->subDays(80)],
            ['School System', 'income', 'client_payment', 'School System deployment payment', 1200, 'USD', now()->subDays(55)],
            ['School System', 'income', 'maintenance_fee', 'Annual support contract', 500, 'USD', now()->subDays(20)],
            ['School System', 'expense', 'hosting', 'School server hosting', 45, 'USD', now()->subDays(50)],
            ['School System', 'expense', 'salary', 'Freelance developer — enrollment module', 300, 'USD', now()->subDays(15)],
            ['News CMS', 'income', 'subscription', 'Market analysis subscriptions', 180, 'USD', now()->subDays(5)],
            ['News CMS', 'expense', 'tools', 'Data API subscription', 80, 'USD', now()->subDays(7)],
            ['CamQuran', 'income', 'product_sale', 'App store revenue', 120, 'USD', now()->subDays(12)],
            ['CamQuran', 'expense', 'other_expense', 'Audio recording studio', 150, 'USD', now()->subDays(14)],
            ['CamQuran', 'expense', 'advertising', 'Facebook page boost', 30, 'USD', now()->subDays(6)],
            [null, 'income', 'other_income', 'Computer repair walk-in customers', 75, 'USD', now()->subDays(3)],
            [null, 'expense', 'tools', 'SSD stock purchase', 220, 'USD', now()->subDays(9)],
            [null, 'expense', 'domain', 'fstech.com domain renewal', 12, 'USD', now()->subDays(60)],
            ['Poss-System', 'income', 'client_payment', 'Local client payment', 1800000, 'KHR', now()->subDays(2)],
            [null, 'expense', 'other_expense', 'Office supplies', 120000, 'KHR', now()->subDays(4)],
        ];

        foreach ($rows as [$systemName, $type, $category, $title, $amount, $currency, $date]) {
            FinanceTransaction::create([
                'system_id' => $systemName ? $systemIds[$systemName] ?? null : null,
                'type' => $type,
                'category' => $category,
                'title' => $title,
                'amount' => $amount,
                'currency' => $currency,
                'transaction_date' => $date,
                'description' => null,
                'created_by_id' => $admin->id,
            ]);
        }
    }
}
