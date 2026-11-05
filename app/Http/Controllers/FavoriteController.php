<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Support\ExpenseFormData;
use Illuminate\Http\Request;
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
        abort_unless($favorite->user_id === $request->user()->id, 404);

        return Inertia::render('Expenses/Create', [
            'source' => 'favorite',
            'prefill' => ExpenseFormData::prefill($favorite),
            'categories' => ExpenseFormData::categories($request->user()),
            'tags' => ExpenseFormData::tagNames($request->user()),
        ]);
    }
}
