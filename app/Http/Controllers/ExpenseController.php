<?php

namespace App\Http\Controllers;

use App\Http\Requests\ExpenseRequest;
use App\Models\Expense;
use App\Models\Tag;
use App\Services\ExpenseFavorites;
use App\Support\ExpenseFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ExpenseController extends Controller
{
    public function index(Request $request): Response
    {
        $days = $request->user()->expenses()
            ->with(['category', 'tags'])
            ->withExists('favorite')
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
                    'category' => $expense->category?->name,
                    'tags' => $expense->tags->pluck('name')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
                    'favorited' => $expense->favorite_exists,
                ])->values(),
            ])
            ->values();

        return Inertia::render('Expenses/Index', ['days' => $days]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Expenses/Create', [
            'categories' => ExpenseFormData::categories($request->user()),
            'tags' => ExpenseFormData::tagNames($request->user()),
        ]);
    }

    /**
     * Abre o cadastro preenchido com os dados de um gasto existente. Não grava nada;
     * a data não é levada, o cadastro usa a de hoje.
     */
    public function repeat(Request $request, Expense $expense): Response
    {
        $this->authorizeOwner($request, $expense);

        return Inertia::render('Expenses/Create', [
            'source' => 'expense',
            'prefill' => ExpenseFormData::prefill($expense),
            'categories' => ExpenseFormData::categories($request->user()),
            'tags' => ExpenseFormData::tagNames($request->user()),
        ]);
    }

    public function store(ExpenseRequest $request, ExpenseFavorites $favorites): RedirectResponse
    {
        DB::transaction(function () use ($request, $favorites) {
            $expense = $request->user()->expenses()->create([
                'amount' => $request->amountInCents(),
                'date' => $request->validated('date'),
                'description' => $request->validated('description'),
                'category_id' => $request->validated('category_id'),
            ]);

            $this->syncTags($request, $expense);
            $this->syncFavorite($request, $expense, $favorites);
        });

        return redirect()->route('expenses.index');
    }

    public function edit(Request $request, Expense $expense): Response
    {
        $this->authorizeOwner($request, $expense);

        return Inertia::render('Expenses/Edit', [
            'expense' => [
                'id' => $expense->id,
                'amount' => ExpenseFormData::amount($expense->amount),
                'date' => $expense->date->toDateString(),
                'description' => $expense->description,
                'category_id' => $expense->category_id,
                'tags' => $expense->tags()->orderBy('name')->pluck('name'),
                'favorited' => $expense->favorite()->exists(),
            ],
            'categories' => ExpenseFormData::categories($request->user()),
            'tags' => ExpenseFormData::tagNames($request->user()),
        ]);
    }

    public function update(ExpenseRequest $request, Expense $expense, ExpenseFavorites $favorites): RedirectResponse
    {
        $this->authorizeOwner($request, $expense);

        DB::transaction(function () use ($request, $expense, $favorites) {
            $expense->update([
                'amount' => $request->amountInCents(),
                'date' => $request->validated('date'),
                'description' => $request->validated('description'),
                'category_id' => $request->validated('category_id'),
            ]);

            $this->syncTags($request, $expense);
            $this->syncFavorite($request, $expense, $favorites);
        });

        return redirect()->route('expenses.index');
    }

    public function favorite(Request $request, Expense $expense, ExpenseFavorites $favorites): RedirectResponse
    {
        $this->authorizeOwner($request, $expense);

        $favorites->favorite($expense);

        return back();
    }

    public function unfavorite(Request $request, Expense $expense, ExpenseFavorites $favorites): RedirectResponse
    {
        $this->authorizeOwner($request, $expense);

        $favorites->unfavorite($expense);

        return back();
    }

    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorizeOwner($request, $expense);

        $expense->delete();

        return redirect()->route('expenses.index');
    }

    /**
     * Aplica a opção "favoritar" do formulário: marcada cria o favorito (uma cópia do gasto já
     * com os dados salvos), desmarcada remove o favorito, ausente não muda nada.
     */
    private function syncFavorite(ExpenseRequest $request, Expense $expense, ExpenseFavorites $favorites): void
    {
        if ($request->input('favorite') === null) {
            return;
        }

        $request->boolean('favorite') ? $favorites->favorite($expense) : $favorites->unfavorite($expense);
    }

    /**
     * Atribui ao gasto as tags informadas por nome. Reaproveita a tag que já existe
     * (sem diferenciar maiúsculas e espaços nas pontas) e cria as que ainda não existem.
     */
    private function syncTags(ExpenseRequest $request, Expense $expense): void
    {
        $user = $request->user();
        $existing = $user->tags()->get()->keyBy(fn (Tag $tag) => mb_strtolower($tag->name));

        $ids = collect($request->validated('tags') ?? [])
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique(fn (string $name) => mb_strtolower($name))
            ->map(fn (string $name) => ($existing->get(mb_strtolower($name)) ?? $user->tags()->create(['name' => $name]))->id);

        $expense->tags()->sync($ids->all());
    }

    private function authorizeOwner(Request $request, Expense $expense): void
    {
        abort_unless($expense->user_id === $request->user()->id, 404);
    }
}
