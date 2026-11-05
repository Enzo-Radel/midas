<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Categories/Index', [
            'categories' => $request->user()->categories()
                ->withCount(['expenses', 'favorites'])
                ->orderBy('id')
                ->get()
                ->map(fn (Category $category) => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'active' => $category->active,
                    'expenses_count' => $category->expenses_count,
                    'favorites_count' => $category->favorites_count,
                ]),
        ]);
    }

    public function store(CategoryRequest $request): RedirectResponse
    {
        $request->user()->categories()->create(['name' => $request->validated('name')]);

        return redirect()->route('categories.index');
    }

    public function update(CategoryRequest $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $category->update(['name' => $request->validated('name')]);

        return redirect()->route('categories.index');
    }

    /**
     * Desativa a categoria: deixa de ser oferecida para escolha, mas continua nos gastos e
     * favoritos que já a usam.
     */
    public function deactivate(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $category->update(['active' => false]);

        return redirect()->route('categories.index');
    }

    public function reactivate(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $category->update(['active' => true]);

        return redirect()->route('categories.index');
    }

    /**
     * Exclui uma categoria que ninguém usa. Categoria usada por gasto ou favorito não pode ser
     * excluída assim: ela precisa ser desativada ou trocada antes (etapas seguintes).
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        // Checagem e exclusão juntas, com a categoria travada: se um gasto passar a usá-la nesse
        // meio-tempo, ela não é apagada (o banco deixaria o gasto sem categoria em silêncio).
        $deleted = DB::transaction(function () use ($category) {
            $locked = Category::whereKey($category->getKey())->lockForUpdate()->first();

            if (! $locked || $locked->expenses()->exists() || $locked->favorites()->exists()) {
                return false;
            }

            $locked->delete();

            return true;
        });

        if (! $deleted) {
            return back()->withErrors(['category' => 'Esta categoria está em uso em gastos ou favoritos e não pode ser excluída.']);
        }

        return redirect()->route('categories.index');
    }
}
