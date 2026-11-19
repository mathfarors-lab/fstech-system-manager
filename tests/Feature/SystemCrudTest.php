<?php

namespace Tests\Feature;

use App\Models\Build;
use App\Models\System;
use App\Models\Task;
use App\Models\User;
use App\Models\Version;
use Barryvdh\DomPDF\Facade\Pdf;
use Database\Seeders\AdminUserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdminUserSeeder::class);
        $this->actingAsUser();
    }

    protected function actingAsUser(): void
    {
        $this->actingAs(User::where('email', 'developer@fstech.com')->first());
    }

    public function test_system_can_be_created_with_slug(): void
    {
        $this->get(route('systems.create'))->assertOk()->assertSee('Payment Stage Plan');

        $response = $this->post(route('systems.store'), [
            'name' => 'My Cool App',
            'stack' => 'laravel',
            'environment' => 'development',
            'status' => 'active',
            'expense_currency' => 'USD',
            'expenses' => [
                ['category' => 'hosting', 'amount' => 100],
            ],
        ]);

        $response->assertRedirect();

        $system = System::where('slug', 'my-cool-app')->first();
        $this->assertNotNull($system);
        $this->assertEquals('My Cool App', $system->name);
    }

    public function test_duplicate_names_get_unique_slugs(): void
    {
        $payload = [
            'name' => 'My App', 'stack' => 'laravel', 'environment' => 'development', 'status' => 'active',
            'expense_currency' => 'USD', 'expenses' => [['category' => 'hosting', 'amount' => 10]],
        ];

        $this->post(route('systems.store'), $payload);
        $this->post(route('systems.store'), $payload);

        $this->assertDatabaseHas('systems', ['slug' => 'my-app']);
        $this->assertDatabaseHas('systems', ['slug' => 'my-app-2']);
    }

    public function test_system_validation_requires_name(): void
    {
        $this->post(route('systems.store'), [
            'stack' => 'laravel', 'environment' => 'development', 'status' => 'active',
        ])->assertSessionHasErrors('name');
    }

    public function test_system_creation_records_project_expenses(): void
    {
        $response = $this->post(route('systems.store'), [
            'name' => 'Invoice App',
            'stack' => 'laravel',
            'environment' => 'development',
            'status' => 'active',
            'client_name' => 'Sokha Client',
            'client_phone' => '+855 12 345 678',
            'paid_amount' => 500,
            'expense_currency' => 'USD',
            'expenses' => [
                ['category' => 'hosting', 'title' => 'VPS server', 'amount' => 120],
                ['category' => 'domain', 'title' => '', 'amount' => 15],
                ['category' => 'setup_fee', 'title' => 'Installation', 'amount' => 45],
            ],
        ]);

        $system = System::where('slug', 'invoice-app')->first();
        $response->assertRedirect(route('systems.show', $system));

        // System price is the total of the project costs
        $this->assertEquals(180, (float) $system->price);
        $this->assertEquals('USD', $system->price_currency);

        $this->assertDatabaseHas('finance_transactions', [
            'system_id' => $system->id,
            'type' => 'expense',
            'category' => 'hosting',
            'title' => 'VPS server',
            'amount' => 120,
            'currency' => 'USD',
        ]);

        $this->assertDatabaseHas('finance_transactions', [
            'system_id' => $system->id,
            'type' => 'expense',
            'category' => 'domain',
            'title' => 'Domain',
            'amount' => 15,
        ]);

        $this->assertEquals(3, $system->financeTransactions()->count());

        $this->get(route('systems.invoice', $system))
            ->assertOk()
            ->assertSee('Sokha Client')
            ->assertSee('Line items');
    }

    public function test_invoice_renders_when_price_is_not_set(): void
    {
        $system = System::create([
            'name' => 'No Price App',
            'slug' => 'no-price-app',
            'stack' => 'laravel',
            'environment' => 'development',
            'status' => 'active',
            'owner_id' => User::first()->id,
            'price' => null,
        ]);

        $this->get(route('systems.invoice', $system))
            ->assertOk()
            ->assertSee('Pricing pending');
    }

    public function test_project_costs_are_required(): void
    {
        $this->post(route('systems.store'), [
            'name' => 'No Costs App',
            'stack' => 'laravel',
            'environment' => 'development',
            'status' => 'active',
        ])->assertSessionHasErrors(['expenses', 'expense_currency']);

        $this->assertDatabaseMissing('systems', ['name' => 'No Costs App']);
    }

    public function test_payment_phases_are_created_from_percentages(): void
    {
        $this->post(route('systems.store'), [
            'name' => 'Phased App',
            'stack' => 'laravel',
            'environment' => 'development',
            'status' => 'active',
            'expense_currency' => 'USD',
            'expenses' => [['category' => 'hosting', 'amount' => 1000]],
            'milestones' => [
                ['title' => 'Start to build', 'percentage' => 40],
                ['title' => 'Testing', 'percentage' => 20],
                ['title' => 'Complete', 'percentage' => 40],
            ],
        ])->assertRedirect();

        $system = System::where('slug', 'phased-app')->first();

        $this->assertEquals(1000, (float) $system->price);
        $this->assertCount(3, $system->paymentMilestones);

        $this->assertDatabaseHas('payment_milestones', [
            'system_id' => $system->id,
            'title' => 'Testing',
            'percentage' => 20,
            'amount' => 200,
            'status' => 'pending',
        ]);

        $this->get(route('systems.invoice', $system))
            ->assertOk()
            ->assertSee('Payment stages')
            ->assertSee('Start to build');

        $this->get(route('systems.invoice.pdf', $system))->assertOk();
    }

    public function test_invoice_pdf_fits_on_one_page(): void
    {
        $system = System::create([
            'name' => 'ChiliPosApp',
            'slug' => 'chiliposapp',
            'stack' => 'dotnet',
            'environment' => 'production',
            'status' => 'active',
            'client_name' => 'Demo Client',
            'client_phone' => '+855 12 345 678',
            'client_email' => 'client@test.com',
            'client_telegram' => '@client',
            'client_address' => 'Phnom Penh',
            'price' => 700,
            'price_currency' => 'USD',
            'paid_amount' => 0,
            'maintenance_amount' => 50,
            'maintenance_cycle' => 'monthly',
            'notes' => 'Some invoice notes here.',
        ]);

        foreach ([
            ['category' => 'setup_fee', 'title' => 'Setup / Installation', 'amount' => 200],
            ['category' => 'developer_fee', 'title' => 'Developer / Dev Fee', 'amount' => 350],
            ['category' => 'domain', 'title' => 'Domain', 'amount' => 30],
            ['category' => 'hosting', 'title' => 'Hosting / Server', 'amount' => 120],
        ] as $exp) {
            $system->financeTransactions()->create([
                'type' => 'expense',
                'category' => $exp['category'],
                'title' => $exp['title'],
                'amount' => $exp['amount'],
                'currency' => 'USD',
                'transaction_date' => now()->toDateString(),
            ]);
        }

        foreach ([
            ['title' => 'Start to build', 'percentage' => 40, 'amount' => 280],
            ['title' => 'Testing', 'percentage' => 20, 'amount' => 140],
            ['title' => 'Complete', 'percentage' => 40, 'amount' => 280],
        ] as $idx => $ms) {
            $system->paymentMilestones()->create([
                'title' => $ms['title'],
                'percentage' => $ms['percentage'],
                'amount' => $ms['amount'],
                'currency' => 'USD',
                'status' => 'pending',
                'sort_order' => $idx,
            ]);
        }

        $pdf = Pdf::loadView('admin.systems.invoice-pdf', [
            'system' => $system,
            'costs' => $system->financeTransactions()->where('type', 'expense')->get(),
            'milestones' => $system->paymentMilestones()->get(),
        ])->setPaper('a4', 'portrait');

        $pdf->render();
        $this->assertEquals(1, $pdf->getDomPDF()->getCanvas()->get_page_count());
    }

    public function test_payment_phases_must_total_100_percent(): void
    {
        $this->post(route('systems.store'), [
            'name' => 'Bad Phases App',
            'stack' => 'laravel',
            'environment' => 'development',
            'status' => 'active',
            'expense_currency' => 'USD',
            'expenses' => [['category' => 'hosting', 'amount' => 500]],
            'milestones' => [
                ['title' => 'Start', 'percentage' => 50],
            ],
        ])->assertSessionHasErrors('milestones');

        $this->assertDatabaseMissing('systems', ['name' => 'Bad Phases App']);
    }

    public function test_milestone_payment_can_be_collected_and_undone(): void
    {
        $this->post(route('systems.store'), [
            'name' => 'Collect App',
            'stack' => 'laravel',
            'environment' => 'development',
            'status' => 'active',
            'expense_currency' => 'USD',
            'expenses' => [['category' => 'hosting', 'amount' => 1000]],
            'milestones' => [
                ['title' => 'Start', 'percentage' => 40],
                ['title' => 'Complete', 'percentage' => 60],
            ],
        ]);

        $system = System::where('slug', 'collect-app')->first();
        $milestone = $system->paymentMilestones()->where('title', 'Start')->first();

        $this->get(route('systems.show', [$system, 'tab' => 'finance']))
            ->assertOk()
            ->assertSee('Payment Stages')
            ->assertSee('Collect Payment');

        $this->post(route('systems.milestones.collect', [$system, $milestone]))->assertRedirect();

        $milestone->refresh();
        $this->assertTrue($milestone->isPaid());
        $this->assertEquals(400, (float) $system->fresh()->paid_amount);
        $this->assertDatabaseHas('finance_transactions', [
            'system_id' => $system->id,
            'type' => 'income',
            'category' => 'client_payment',
            'amount' => 400,
        ]);

        $this->post(route('systems.milestones.undo', [$system, $milestone]))->assertRedirect();

        $milestone->refresh();
        $this->assertFalse($milestone->isPaid());
        $this->assertEquals(0, (float) $system->fresh()->paid_amount);
        $this->assertDatabaseMissing('finance_transactions', [
            'system_id' => $system->id,
            'category' => 'client_payment',
        ]);
    }

    public function test_system_can_be_updated(): void
    {
        $system = System::create([
            'name' => 'Old Name', 'slug' => 'old-name', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);

        $this->put(route('systems.update', $system), [
            'name' => 'New Name', 'stack' => 'dotnet',
            'environment' => 'staging', 'status' => 'maintenance',
        ])->assertRedirect();

        $this->assertDatabaseHas('systems', [
            'id' => $system->id, 'name' => 'New Name', 'stack' => 'dotnet',
        ]);
    }

    public function test_system_can_be_updated_with_project_costs_and_milestones(): void
    {
        $system = System::create([
            'name' => 'ChiliPosApp',
            'slug' => 'chiliposapp',
            'stack' => 'dotnet',
            'environment' => 'production',
            'status' => 'active',
        ]);

        $this->get(route('systems.edit', $system))
            ->assertOk()
            ->assertSee('Project Costs')
            ->assertSee('Payment Stage Plan');

        $response = $this->put(route('systems.update', $system), [
            'name' => 'ChiliPosApp',
            'stack' => 'dotnet',
            'environment' => 'production',
            'status' => 'active',
            'expense_currency' => 'USD',
            'expenses' => [
                ['category' => 'setup_fee', 'title' => 'Setup / Installation', 'amount' => 200],
                ['category' => 'developer_fee', 'title' => 'Developer / Dev Fee', 'amount' => 350],
                ['category' => 'domain', 'title' => 'Domain', 'amount' => 30],
                ['category' => 'hosting', 'title' => 'Hosting / Server', 'amount' => 120],
            ],
            'milestones' => [
                ['title' => 'Start to build', 'percentage' => 40],
                ['title' => 'Testing', 'percentage' => 20],
                ['title' => 'Complete', 'percentage' => 40],
            ],
        ]);

        $response->assertRedirect(route('systems.show', $system));

        $system->refresh();
        $this->assertEquals(700, (float) $system->price);
        $this->assertEquals('USD', $system->price_currency);
        $this->assertEquals(4, $system->financeTransactions()->where('type', 'expense')->count());
        $this->assertEquals(3, $system->paymentMilestones()->count());

        $this->assertDatabaseHas('payment_milestones', [
            'system_id' => $system->id,
            'title' => 'Start to build',
            'percentage' => 40,
            'amount' => 280,
        ]);

        $this->get(route('systems.edit', $system))
            ->assertOk()
            ->assertSee('Setup / Installation')
            ->assertSee('Developer / Dev Fee');
    }

    public function test_system_archive_and_restore(): void
    {
        $system = System::create([
            'name' => 'Archived App', 'slug' => 'archived-app', 'stack' => 'php',
            'environment' => 'development', 'status' => 'active',
        ]);

        $this->delete(route('systems.destroy', $system))->assertRedirect();

        $this->assertSoftDeleted('systems', ['id' => $system->id]);

        $this->post(route('systems.restore', $system->id))->assertRedirect();
        $this->assertDatabaseHas('systems', ['id' => $system->id, 'deleted_at' => null]);
    }

    public function test_versions_belong_to_system(): void
    {
        $system = System::create([
            'name' => 'Versioned App', 'slug' => 'versioned-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);

        $this->post(route('systems.versions.store', $system), [
            'version' => '1.0.0', 'release_date' => '2026-11-12',
        ])->assertRedirect();

        $this->assertDatabaseHas('versions', [
            'system_id' => $system->id, 'version' => '1.0.0',
        ]);

        $version = Version::where('system_id', $system->id)->first();
        $this->put(route('systems.versions.update', [$system, $version]), [
            'version' => '1.1.0', 'changelog' => 'Some changes',
        ])->assertRedirect();

        $this->assertDatabaseHas('versions', ['id' => $version->id, 'version' => '1.1.0']);
    }

    public function test_build_timestamps_are_set_by_status(): void
    {
        $system = System::create([
            'name' => 'Built App', 'slug' => 'built-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);

        $this->post(route('systems.builds.store', $system), [
            'version_label' => '1.0.0',
            'environment' => 'staging',
            'status' => 'deployed',
        ])->assertRedirect();

        $build = Build::where('system_id', $system->id)->first();
        $this->assertNotNull($build->built_at);
        $this->assertNotNull($build->deployed_at);
        $this->assertEquals($this->getDefaultUser()->id, $build->built_by_id);
    }

    public function test_task_completion_sets_completed_at(): void
    {
        $system = System::create([
            'name' => 'Task App', 'slug' => 'task-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);

        $task = Task::create([
            'system_id' => $system->id, 'title' => 'Do work',
            'status' => 'todo', 'priority' => 'medium',
        ]);

        $this->patch(route('tasks.status', $task), ['status' => 'done'])->assertRedirect();

        $this->assertDatabaseHas('tasks', ['id' => $task->id, 'status' => 'done']);
        $this->assertNotNull($task->fresh()->completed_at);
    }

    public function test_operation_log_created(): void
    {
        $system = System::create([
            'name' => 'Logged App', 'slug' => 'logged-app', 'stack' => 'laravel',
            'environment' => 'development', 'status' => 'active',
        ]);

        $this->post(route('systems.logs.store', $system), [
            'type' => 'backup',
            'title' => 'DB dump',
            'performed_at' => now()->format('Y-m-d\TH:i'),
        ])->assertRedirect();

        $this->assertDatabaseHas('operation_logs', [
            'system_id' => $system->id, 'title' => 'DB dump',
        ]);
    }

    protected function getDefaultUser()
    {
        return User::where('email', 'developer@fstech.com')->first();
    }
}
