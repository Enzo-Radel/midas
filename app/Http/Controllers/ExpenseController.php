<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $days = $request->user()->expenses()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->groupBy(fn (Expense $expense) => $expense->date->toDateString())
            ->map(fn (Collection $expenses, string $date) => [
                'date' => $date,
                'total' => $expenses->sum('amount'),
                'expenses' => $expenses->map(fn (Expense $expense) => [
                    'id' => $expense->id,
                    'amount' => $expense->amount,
                    'description' => $expense->description,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Expenses/Index', ['days' => $days]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Create');
    }

    public function store(ExpenseRequest $request): RedirectResponse
    {
        $request->user()->expenses()->create([
            'amount' => $request->amountInCents(),
            'date' => $request->validated('date'),
            'description' => $request->validated('description'),
        ]);

        return redirect()->route('expenses.index');
    }

    public function edit(Request $request, Expense $expense): Response
    {
        $this->authorizeOwner($request, $expense);

        return Inertia::render('Expenses/Edit', [
            'expense' => [
                'id' => $expense->id,
                'amount' => number_format($expense->amount / 100, 2, '.', ''),
                'date' => $expense->date->toDateString(),
                'description' => $expense->description,
            ],
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->authorizeOwner($request, $expense);

        $expense->update([
            'amount' => $request->amountInCents(),
            'date' => $request->validated('date'),
            'description' => $request->validated('description'),
        ]);

        return redirect()->route('expenses.index');
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeOwner($request, $expense);

        $expense->delete();

        return redirect()->route('expenses.index');
    }

    private function authorizeOwner(Request $request, Expense $expense): void
    {
        abort_unless($expense->user_id === $request->user()->id, 404);
    }
}
