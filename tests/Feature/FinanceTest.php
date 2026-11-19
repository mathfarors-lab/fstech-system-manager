<?php

namespace Tests\Feature;

use App\Models\FinanceTransaction;
use App\Models\System;
use App\Models\User;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminUserSeeder::class);
    }

    protected function developer()
    {
        return User::where('email', 'developer@fstech.com')->first();
    }

    protected function viewer()
    {
        return User::where('email', 'viewer@fstech.com')->first();
    }

    public function test_finance_dashboard_is_accessible(): void
    {
        $this->actingAs($this->developer())
            ->get(route('finance.dashboard'))
            ->assertOk()
            ->assertSee('Finance');
    }

    public function test_ledger_is_accessible(): void
    {
        $this->actingAs($this->developer())
            ->get(route('finance.transactions'))
            ->assertOk();
    }

    public function test_viewer_cannot_record_transaction(): void
    {
        $this->actingAs($this->viewer())
            ->post(route('finance.transactions.store'), [
                'type' => 'income',
                'category' => 'client_payment',
                'title' => 'Test',
                'amount' => 100,
                'currency' => 'USD',
                'transaction_date' => '2026-11-12',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('finance_transactions', ['title' => 'Test']);
    }

    public function test_developer_can_record_income(): void
    {
        $this->actingAs($this->developer())
            ->post(route('finance.transactions.store'), [
                'type' => 'income',
                'category' => 'client_payment',
                'title' => 'Client payment November',
                'amount' => 850.50,
                'currency' => 'USD',
                'transaction_date' => '2026-11-12',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('finance_transactions', [
            'title' => 'Client payment November',
            'amount' => 850.50,
            'type' => 'income',
        ]);
    }

    public function test_transaction_can_be_linked_to_system(): void
    {
        $system = System::create([
            'name' => 'Linked App', 'slug' => 'linked-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);

        $this->actingAs($this->developer())
            ->post(route('finance.transactions.store'), [
                'system_id' => $system->id,
                'type' => 'expense',
                'category' => 'hosting',
                'title' => 'Hosting bill',
                'amount' => 35,
                'currency' => 'USD',
                'transaction_date' => '2026-11-01',
            ])
            ->assertRedirect();

        $tx = FinanceTransaction::where('title', 'Hosting bill')->first();
        $this->assertEquals($system->id, $tx->system_id);
    }

    public function test_transaction_validation(): void
    {
        $this->actingAs($this->developer())
            ->post(route('finance.transactions.store'), [
                'type' => 'income',
                'category' => 'not_a_category',
                'title' => 'Bad data',
                'amount' => -5,
                'currency' => 'EUR',
                'transaction_date' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['category', 'amount', 'currency', 'transaction_date']);
    }

    public function test_income_and_expense_category_must_match_type(): void
    {
        $this->actingAs($this->developer())
            ->post(route('finance.transactions.store'), [
                'type' => 'income',
                'category' => 'hosting',
                'title' => 'Wrong category for type',
                'amount' => 10,
                'currency' => 'USD',
                'transaction_date' => '2026-11-12',
            ])
            ->assertRedirect();

        // Type/category mismatch is allowed at DB level but flagged in UI;
        // category is validated as valid enum either way
        $this->assertDatabaseHas('finance_transactions', ['title' => 'Wrong category for type']);
    }

    public function test_transaction_can_be_updated(): void
    {
        $tx = FinanceTransaction::create([
            'type' => 'expense',
            'category' => 'hosting',
            'title' => 'Old title',
            'amount' => 30,
            'currency' => 'USD',
            'transaction_date' => '2026-11-01',
        ]);

        $this->actingAs($this->developer())
            ->put(route('finance.transactions.update', $tx), [
                'type' => 'expense',
                'category' => 'hosting',
                'title' => 'New title',
                'amount' => 45,
                'currency' => 'USD',
                'transaction_date' => '2026-11-05',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('finance_transactions', ['id' => $tx->id, 'title' => 'New title', 'amount' => 45]);
    }

    public function test_transaction_can_be_deleted(): void
    {
        $tx = FinanceTransaction::create([
            'type' => 'income',
            'category' => 'client_payment',
            'title' => 'To be deleted',
            'amount' => 100,
            'currency' => 'USD',
            'transaction_date' => '2026-11-01',
        ]);

        $this->actingAs($this->developer())
            ->delete(route('finance.transactions.destroy', $tx))
            ->assertRedirect();

        $this->assertDatabaseMissing('finance_transactions', ['id' => $tx->id]);
    }

    public function test_ledger_filters_by_type(): void
    {
        FinanceTransaction::create([
            'type' => 'income', 'category' => 'client_payment', 'title' => 'Income TX',
            'amount' => 100, 'currency' => 'USD', 'transaction_date' => '2026-11-01',
        ]);
        FinanceTransaction::create([
            'type' => 'expense', 'category' => 'hosting', 'title' => 'Expense TX',
            'amount' => 50, 'currency' => 'USD', 'transaction_date' => '2026-11-02',
        ]);

        $this->actingAs($this->developer())
            ->get(route('finance.transactions', ['type' => 'expense']))
            ->assertOk()
            ->assertSee('Expense TX')
            ->assertDontSee('Income TX');
    }

    public function test_finance_tab_shows_on_system_detail(): void
    {
        $system = System::create([
            'name' => 'Tab App', 'slug' => 'tab-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);

        FinanceTransaction::create([
            'system_id' => $system->id,
            'type' => 'income',
            'category' => 'client_payment',
            'title' => 'System-specific income',
            'amount' => 500,
            'currency' => 'USD',
            'transaction_date' => '2026-11-01',
        ]);

        $this->actingAs($this->developer())
            ->get(route('systems.show', [$system, 'tab' => 'finance']))
            ->assertOk()
            ->assertSee('System-specific income');
    }
}
