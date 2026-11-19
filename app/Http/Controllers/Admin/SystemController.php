<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\MaintenancePayment;
use App\Models\PaymentMilestone;
use App\Models\System;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SystemController extends Controller
{
    public function index(Request $request)
    {
        $view = $request->query('view', 'grid');
        $query = System::query()
            ->with(['owner', 'latestVersion'])
            ->withCount(['tasks as open_tasks_count' => fn ($q) => $q->where('status', '!=', 'done')]);

        if ($search = $request->query('q')) {
            $query->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('description', 'like', "%{$search}%"));
        }

        foreach (['status', 'stack', 'environment'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        $systems = $request->query('all') === '1'
            ? $query->withTrashed()->orderBy('name')->paginate(24)
            : $query->orderBy('name')->paginate(24);

        $counts = [
            'status' => System::selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status'),
            'stack' => System::selectRaw('stack, count(*) total')->groupBy('stack')->pluck('total', 'stack'),
        ];

        return view('admin.systems.index', compact('systems', 'counts', 'view'));
    }

    public function create()
    {
        $users = User::orderBy('name')->get();

        return view('admin.systems.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $expenses = $request->validate([
            'expense_currency' => ['required', 'in:USD,KHR'],
            'expenses' => ['required', 'array', 'min:1'],
            'expenses.*.category' => ['required', 'in:'.implode(',', FinanceTransaction::EXPENSE_CATEGORIES)],
            'expenses.*.title' => ['nullable', 'string', 'max:255'],
            'expenses.*.amount' => ['required', 'numeric', 'min:0.01'],
        ]);

        $currency = $expenses['expense_currency'];
        $rows = $expenses['expenses'];
        $total = array_sum(array_map(fn ($row) => (float) $row['amount'], $rows));

        $milestoneData = $request->validate([
            'milestones' => ['nullable', 'array'],
            'milestones.*.title' => ['nullable', 'string', 'max:100'],
            'milestones.*.percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ])['milestones'] ?? [];

        $milestones = collect($milestoneData)
            ->filter(fn ($row) => trim((string) ($row['title'] ?? '')) !== '' && is_numeric($row['percentage'] ?? null) && (float) $row['percentage'] > 0)
            ->map(fn ($row) => ['title' => trim($row['title']), 'percentage' => (float) $row['percentage']])
            ->values();

        if ($milestones->isNotEmpty() && abs($milestones->sum('percentage') - 100) > 0.01) {
            throw ValidationException::withMessages([
                'milestones' => 'Payment phases must add up to exactly 100% (currently '.rtrim(rtrim(number_format($milestones->sum('percentage'), 2, '.', ''), '0'), '.').'%).',
            ]);
        }

        $data['price'] = $total;
        $data['price_currency'] = $currency;
        $data['slug'] = $this->uniqueSlug($data['name']);
        $data['owner_id'] = $data['owner_id'] ?? $request->user()->id;

        $system = DB::transaction(function () use ($data, $rows, $currency, $milestones, $total, $request) {
            $system = System::create($data);

            foreach ($rows as $row) {
                $category = $row['category'];

                $system->financeTransactions()->create([
                    'type' => 'expense',
                    'category' => $category,
                    'title' => ($row['title'] ?? null) ?: (FinanceTransaction::CATEGORIES[$category] ?? 'Expense'),
                    'amount' => $row['amount'],
                    'currency' => $currency,
                    'transaction_date' => now()->toDateString(),
                    'created_by_id' => $request->user()->id,
                ]);
            }

            foreach ($milestones as $index => $milestone) {
                $system->paymentMilestones()->create([
                    'title' => $milestone['title'],
                    'percentage' => $milestone['percentage'],
                    'amount' => round($total * $milestone['percentage'] / 100, 2),
                    'currency' => $currency,
                    'status' => 'pending',
                    'sort_order' => $index,
                ]);
            }

            return $system;
        });

        return redirect()
            ->route('systems.show', $system)
            ->with('success', "System \"{$system->name}\" created. Its invoice is ready to print or save as PDF.");
    }

    public function show(System $system, Request $request)
    {
        $system->load(['owner', 'latestVersion']);

        $tab = $request->query('tab', 'overview');

        $data = match ($tab) {
            'versions' => [
                'versions' => $system->versions()->orderByDesc('release_date')->orderByDesc('id')->get(),
            ],
            'builds' => [
                'builds' => $system->builds()->with(['builtBy', 'version'])->paginate(10),
                'versions' => $system->versions()->orderByDesc('id')->get(),
            ],
            'tasks' => [
                'tasks' => $system->tasks()->with(['assignee', 'notes'])->orderBy('sort_order')->get(),
                'users' => User::orderBy('name')->get(),
            ],
            'logs' => [
                'logs' => $system->operationLogs()->with('performedBy')->paginate(10),
            ],
            'finance' => [
                'transactions' => $system->financeTransactions()->with('createdBy')->paginate(10),
                'milestones' => $system->paymentMilestones()->get(),
                'maintenancePayments' => $system->maintenancePayments()->orderByDesc('due_date')->get(),
                'maintenanceDue' => $system->maintenanceDuePeriods(),
                'maintenanceNextDue' => $system->nextUnpaidMaintenanceDate(),
                'summary' => $system->financeTransactions()
                    ->selectRaw('currency, type, SUM(amount) as total')
                    ->groupBy('currency', 'type')
                    ->get()
                    ->mapToGroups(fn ($row) => [$row->currency => $row]),
                'categorySummary' => $system->financeTransactions()
                    ->selectRaw('currency, category, SUM(amount) as total')
                    ->groupBy('currency', 'category')
                    ->get()
                    ->mapToGroups(fn ($row) => [$row->category => $row]),
                'incomeCategories' => FinanceTransaction::INCOME_CATEGORIES,
            ],
            default => [
                'openTasks' => $system->tasks()->where('status', '!=', 'done')->with('assignee')->limit(5)->get(),
                'recentBuilds' => $system->builds()->with('builtBy')->limit(5)->get(),
                'openTasksCount' => $system->tasks()->where('status', '!=', 'done')->count(),
                'buildsCount' => $system->builds()->count(),
                'versionsCount' => $system->versions()->count(),
                'users' => User::orderBy('name')->get(),
            ],
        };

        return view('admin.systems.show', array_merge(
            ['system' => $system, 'tab' => $tab],
            $data
        ));
    }

    public function edit(System $system)
    {
        $users = User::orderBy('name')->get();

        return view('admin.systems.edit', ['system' => $system, 'users' => $users]);
    }

    public function update(System $system, Request $request)
    {
        $data = $this->validated($request);
        if (strtolower($data['name']) !== strtolower($system->name)) {
            $data['slug'] = $this->uniqueSlug($data['name'], $system->id);
        }

        $system->update($data);

        if ($system->price !== null && $system->paymentMilestones()->where('status', 'pending')->exists()) {
            $system->paymentMilestones()->where('status', 'pending')->get()->each(function (PaymentMilestone $milestone) use ($system) {
                $milestone->update([
                    'amount' => round((float) $system->price * (float) $milestone->percentage / 100, 2),
                    'currency' => $system->price_currency ?? $milestone->currency,
                ]);
            });
        }

        return redirect()
            ->route('systems.show', $system)
            ->with('success', 'System updated.');
    }

    public function collectMilestonePayment(System $system, PaymentMilestone $milestone, Request $request)
    {
        abort_unless($milestone->system_id === $system->id, 404);

        if ($milestone->isPaid()) {
            return back()->with('error', "Phase \"{$milestone->title}\" is already paid.");
        }

        DB::transaction(function () use ($system, $milestone, $request) {
            $transaction = $system->financeTransactions()->create([
                'type' => 'income',
                'category' => 'client_payment',
                'title' => $system->name.' — '.$milestone->title,
                'amount' => $milestone->amount,
                'currency' => $milestone->currency,
                'transaction_date' => now()->toDateString(),
                'description' => 'Payment phase: '.number_format((float) $milestone->percentage, 2).'% of '.$system->formatPrice(),
                'created_by_id' => $request->user()->id,
            ]);

            $milestone->update([
                'status' => 'paid',
                'paid_at' => now(),
                'finance_transaction_id' => $transaction->id,
            ]);

            $system->update([
                'paid_amount' => (float) $system->paid_amount + (float) $milestone->amount,
            ]);
        });

        return back()->with('success', "Payment collected: {$milestone->title} ({$milestone->formatAmount()}).");
    }

    public function undoMilestonePayment(System $system, PaymentMilestone $milestone)
    {
        abort_unless($milestone->system_id === $system->id, 404);

        if (! $milestone->isPaid()) {
            return back()->with('error', 'This phase is not paid yet.');
        }

        DB::transaction(function () use ($system, $milestone) {
            $milestone->financeTransaction?->delete();

            $milestone->update([
                'status' => 'pending',
                'paid_at' => null,
                'finance_transaction_id' => null,
            ]);

            $system->update([
                'paid_amount' => max(0, (float) $system->paid_amount - (float) $milestone->amount),
            ]);
        });

        return back()->with('success', "Payment for \"{$milestone->title}\" was undone.");
    }

    public function collectMaintenancePayment(System $system, Request $request)
    {
        $data = $request->validate([
            'due_date' => ['required', 'date'],
        ]);

        if (! $system->hasMaintenancePlan()) {
            return back()->with('error', 'This system has no maintenance plan set up.');
        }

        $dueDate = Carbon::parse($data['due_date'])->startOfDay();

        $isScheduled = $system->maintenancePeriods($dueDate)
            ->contains(fn (Carbon $date) => $date->toDateString() === $dueDate->toDateString());

        if (! $isScheduled) {
            return back()->with('error', 'That date is not part of the maintenance schedule.');
        }

        if ($system->maintenancePayments()->whereDate('due_date', $dueDate)->exists()) {
            return back()->with('error', 'This maintenance period is already paid.');
        }

        $currency = $system->price_currency ?? 'USD';

        DB::transaction(function () use ($system, $dueDate, $currency, $request) {
            $transaction = $system->financeTransactions()->create([
                'type' => 'income',
                'category' => 'maintenance_fee',
                'title' => $system->name.' — Maintenance ('.$dueDate->format('M Y').')',
                'amount' => $system->maintenance_amount,
                'currency' => $currency,
                'transaction_date' => now()->toDateString(),
                'description' => 'Maintenance '.strtolower($system->maintenanceCycleLabel()).' due '.$dueDate->format('M j, Y'),
                'created_by_id' => $request->user()->id,
            ]);

            $system->maintenancePayments()->create([
                'finance_transaction_id' => $transaction->id,
                'due_date' => $dueDate,
                'amount' => $system->maintenance_amount,
                'currency' => $currency,
                'paid_at' => now(),
            ]);

            $system->update(['maintenance_collected_at' => now()]);
        });

        return back()->with('success', 'Maintenance payment collected for '.$dueDate->format('M j, Y').'.');
    }

    public function undoMaintenancePayment(System $system, MaintenancePayment $payment)
    {
        abort_unless($payment->system_id === $system->id, 404);

        DB::transaction(function () use ($payment) {
            $payment->financeTransaction?->delete();
            $payment->delete();
        });

        return back()->with('success', 'Maintenance payment for '.$payment->due_date->format('M j, Y').' was undone.');
    }

    public function invoice(System $system, Request $request)
    {
        return view('admin.systems.invoice', [
            'system' => $system,
            'costs' => $this->invoiceCosts($system),
            'milestones' => $system->paymentMilestones()->get(),
            'autoPrint' => $request->boolean('print'),
        ]);
    }

    public function invoicePdf(System $system)
    {
        $pdf = Pdf::loadView('admin.systems.invoice-pdf', [
            'system' => $system,
            'costs' => $this->invoiceCosts($system),
            'milestones' => $system->paymentMilestones()->get(),
        ])->setPaper('a4', 'portrait');

        return $pdf->download('invoice-'.$system->slug.'-'.now()->format('Ymd').'.pdf');
    }

    private function invoiceCosts(System $system)
    {
        return $system->financeTransactions()
            ->where('type', 'expense')
            ->orderBy('id')
            ->get();
    }

    public function destroy(System $system)
    {
        $name = $system->name;
        $system->delete();

        return redirect()
            ->route('systems.index')
            ->with('success', "System \"{$name}\" archived.");
    }

    public function restore($id)
    {
        $system = System::withTrashed()->findOrFail($id);
        $system->restore();

        return back()->with('success', "System \"{$system->name}\" restored.");
    }

    public function forceDelete($id)
    {
        $system = System::withTrashed()->findOrFail($id);
        $name = $system->name;
        $system->forceDelete();

        return redirect()
            ->route('systems.index')
            ->with('success', "System \"{$name}\" permanently deleted.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:5000'],
            'stack' => ['required', 'in:'.implode(',', array_keys(System::STACKS))],
            'folder_path' => ['nullable', 'string', 'max:500'],
            'repo_url' => ['nullable', 'url', 'max:500'],
            'deploy_url' => ['nullable', 'url', 'max:500'],
            'environment' => ['required', 'in:'.implode(',', array_keys(System::ENVIRONMENTS))],
            'status' => ['required', 'in:'.implode(',', array_keys(System::STATUSES))],
            'icon' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'maintenance_starts_at' => ['nullable', 'date'],
            'payment_notes' => ['nullable', 'string', 'max:2000'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'price_currency' => ['required_with:price', 'in:'.implode(',', System::CURRENCIES)],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'maintenance_amount' => ['nullable', 'numeric', 'min:0'],
            'maintenance_cycle' => ['nullable', 'in:'.implode(',', array_keys(System::MAINTENANCE_CYCLES))],
            'client_name' => ['nullable', 'string', 'max:150'],
            'client_phone' => ['nullable', 'string', 'max:50'],
            'client_telegram' => ['nullable', 'string', 'max:100'],
            'client_email' => ['nullable', 'email', 'max:150'],
            'client_address' => ['nullable', 'string', 'max:500'],
            'owner_id' => ['nullable', 'exists:users,id'],
        ]);
    }

    private function uniqueSlug(string $name, ?int $ignoreId = null): string
    {
        $base = Str::slug($name) ?: 'system';
        $slug = $base;
        $i = 2;

        while (System::where('slug', $slug)->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
