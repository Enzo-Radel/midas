<?php

namespace App\Http\Controllers;

use App\Http\Requests\FavoriteRequest;
use App\Models\Favorite;
use App\Services\TagResolver;
use App\Support\ExpenseFormData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class FavoriteController extends Controller
{
    public function index(Request $request): Response
    {
        $favorites = $request->user()->favorites()
            ->with(['category', 'tags'])
            ->get()
            ->sortBy([
                fn (Favorite $a, Favorite $b) => ($a->name === null) <=> ($b->name === null),
                // Ordem alfabética sem diferenciar maiúsculas nem acentos ("Água" fica junto do "A").
                fn (Favorite $a, Favorite $b) => strcmp(Str::lower(Str::ascii($a->name ?? '')), Str::lower(Str::ascii($b->name ?? ''))),
            ])
            ->values()
            ->map(fn (Favorite $favorite) => [
                'id' => $favorite->id,
                'name' => $favorite->name,
                'description' => $favorite->description,
                'amount' => $favorite->amount,
                'category' => $favorite->category?->name,
                'tags' => $favorite->tags->pluck('name')->sort(SORT_NATURAL | SORT_FLAG_CASE)->values(),
            ]);

        return Inertia::render('Favorites/Index', ['favorites' => $favorites]);
    }

    /**
     * Abre o cadastro preenchido com os dados do favorito. Não grava nada nem altera o favorito.
     */
    public function use(Request $request, Favorite $favorite): Response
    {
        $this->authorizeOwner($request, $favorite);

        return Inertia::render('Expenses/Create', [
            'source' => 'favorite',
            'prefill' => ExpenseFormData::prefill($favorite),
            'categories' => ExpenseFormData::categories($request->user()),
            'tags' => ExpenseFormData::tagNames($request->user()),
        ]);
    }

    public function edit(Request $request, Favorite $favorite): Response
    {
        $this->authorizeOwner($request, $favorite);

        return Inertia::render('Favorites/Edit', [
            'favorite' => [
                'id' => $favorite->id,
                'name' => $favorite->name,
                'amount' => ExpenseFormData::amount($favorite->amount),
                'description' => $favorite->description,
                'category_id' => $favorite->category_id,
                'tags' => $favorite->tags()->orderBy('name')->pluck('name'),
            ],
            'categories' => ExpenseFormData::categories($request->user(), $favorite->category_id),
            'tags' => ExpenseFormData::tagNames($request->user()),
        ]);
    }

    public function update(FavoriteRequest $request, Favorite $favorite, TagResolver $tags): RedirectResponse
    {
        $this->authorizeOwner($request, $favorite);

        DB::transaction(function () use ($request, $favorite, $tags) {
            $favorite->update([
                'name' => $request->validated('name'),
                'amount' => $request->amountInCents(),
                'description' => $request->validated('description'),
                'category_id' => $request->validated('category_id'),
            ]);

            $favorite->tags()->sync($tags->idsFor($request->user(), $request->validated('tags') ?? []));
        });

        return redirect()->route('favorites.index');
    }

    /**
     * Remove o favorito; os gastos já cadastrados, categorias e tags não são afetados.
     */
    public function destroy(Request $request, Favorite $favorite): RedirectResponse
    {
        $this->authorizeOwner($request, $favorite);

        $favorite->delete();

        return redirect()->route('favorites.index');
    }

    private function authorizeOwner(Request $request, Favorite $favorite): void
    {
        abort_unless($favorite->user_id === $request->user()->id, 404);
    }
}
