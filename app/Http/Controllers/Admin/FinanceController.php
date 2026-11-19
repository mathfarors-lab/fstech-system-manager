<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinanceTransaction;
use App\Models\System;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    public function dashboard(Request $request)
    {
        $year = (int) $request->query('year', now()->year);

        // Totals grouped by currency (never mix USD and KHR)
        $totals = FinanceTransaction::selectRaw('currency, type, SUM(amount) as total')
            ->when($year, fn ($q) => $q->whereYear('transaction_date', $year))
            ->groupBy('currency', 'type')
            ->get()
            ->groupBy('currency');

        $summary = $totals->map(function ($rows, $currency) {
            $income = (float) ($rows->where('type', 'income')->sum('total'));
            $expense = (float) ($rows->where('type', 'expense')->sum('total'));

            return [
                'currency' => $currency,
                'income' => $income,
                'expense' => $expense,
                'net' => $income - $expense,
            ];
        });

        // Monthly trend (last 12 months, per currency) — grouped in PHP for driver portability
        $monthly = FinanceTransaction::query()
            ->where('transaction_date', '>=', now()->subMonths(11)->startOfMonth()->toDateString())
            ->get(['currency', 'type', 'transaction_date', 'amount'])
            ->map(fn ($t) => (object) [
                'currency' => $t->currency,
                'ym' => $t->transaction_date->format('Y-m'),
                'type' => $t->type,
                'total' => (float) $t->amount,
            ]);

        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'))->values();

        $chartPerCurrency = $monthly->groupBy('currency')->map(function ($rows, $currency) use ($months) {
            $income = $months->map(fn ($ym) => (float) $rows->where('ym', $ym)->where('type', 'income')->sum('total'));
            $expense = $months->map(fn ($ym) => (float) $rows->where('ym', $ym)->where('type', 'expense')->sum('total'));

            return ['currency' => $currency, 'labels' => $months, 'income' => $income, 'expense' => $expense];
        });

        // Per-system breakdown (main currency = USD)
        $bySystem = FinanceTransaction::with('system')
            ->selectRaw('currency, system_id, type, SUM(amount) as total')
            ->when($year, fn ($q) => $q->whereYear('transaction_date', $year))
            ->groupBy('currency', 'system_id', 'type')
            ->get();

        $mainCurrency = $summary->keys()->contains('USD') ? 'USD' : ($summary->keys()->first() ?? 'USD');

        $systemBreakdown = $bySystem
            ->where('currency', $mainCurrency)
            ->groupBy(fn ($row) => $row->system?->name ?? 'General (no system)')
            ->map(function ($rows) {
                $income = (float) $rows->where('type', 'income')->sum('total');
                $expense = (float) $rows->where('type', 'expense')->sum('total');

                return ['name' => $rows->first()->system?->name ?? 'General', 'income' => $income, 'expense' => $expense, 'net' => $income - $expense];
            })
            ->sortByDesc('net')
            ->values();

        // Category breakdown
        $categoryBreakdown = FinanceTransaction::selectRaw('type, category, SUM(amount) as total')
            ->where('currency', $mainCurrency)
            ->when($year, fn ($q) => $q->whereYear('transaction_date', $year))
            ->groupBy('type', 'category')
            ->orderByDesc('total')
            ->get();

        $recent = FinanceTransaction::with(['system', 'createdBy'])
            ->when($year, fn ($q) => $q->whereYear('transaction_date', $year))
            ->orderByDesc('transaction_date')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        $systems = System::orderBy('name')->get();

        return view('admin.finance.dashboard', compact(
            'summary', 'chartPerCurrency', 'months', 'systemBreakdown', 'categoryBreakdown', 'recent', 'systems', 'year', 'mainCurrency'
        ));
    }

    public function index(Request $request)
    {
        $query = FinanceTransaction::with(['system', 'createdBy']);

        if ($systemId = $request->query('system')) {
            $query->where('system_id', $systemId);
        }

        foreach (['type', 'category', 'currency'] as $filter) {
            if ($value = $request->query($filter)) {
                $query->where($filter, $value);
            }
        }

        if ($month = $request->query('month')) {
            $query->whereYear('transaction_date', substr($month, 0, 4))
                ->whereMonth('transaction_date', substr($month, 5, 2));
        }

        if ($search = $request->query('q')) {
            $query->where('title', 'like', "%{$search}%");
        }

        $transactions = $query->orderByDesc('transaction_date')->orderByDesc('id')->paginate(20);

        $systems = System::orderBy('name')->get();

        return view('admin.finance.transactions', [
            'transactions' => $transactions,
            'systems' => $systems,
            'filters' => $request->only(['system', 'type', 'category', 'currency', 'month', 'q']),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validated($request);
        $validated['created_by_id'] = $request->user()->id;

        FinanceTransaction::create($validated);

        return back()->with('success', 'Transaction recorded.');
    }

    public function update(Request $request, FinanceTransaction $transaction)
    {
        $transaction->update($this->validated($request));

        return back()->with('success', 'Transaction updated.');
    }

    public function destroy(FinanceTransaction $transaction)
    {
        $transaction->delete();

        return back()->with('success', 'Transaction deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'system_id' => ['nullable', 'exists:systems,id'],
            'type' => ['required', 'in:income,expense'],
            'category' => ['required', 'in:'.implode(',', array_keys(FinanceTransaction::CATEGORIES))],
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:999999999999.99'],
            'currency' => ['required', 'in:USD,KHR'],
            'transaction_date' => ['required', 'date'],
            'description' => ['nullable', 'string', 'max:5000'],
        ]);
    }
}
