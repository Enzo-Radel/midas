<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $expenses = $request->user()->expenses()
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Expense $expense) => [
                'id' => $expense->id,
                'amount' => $expense->amount,
                'date' => $expense->date->toDateString(),
                'description' => $expense->description,
            ]);

        return Inertia::render('Expenses/Index', ['expenses' => $expenses]);
    }

    public function create(): Response
    {
        return Inertia::render('Expenses/Create');
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $request->user()->expenses()->create([
            'amount' => $request->amountInCents(),
            'date' => $request->validated('date'),
            'description' => $request->validated('description'),
        ]);

        return redirect()->route('expenses.index');
    }
}
