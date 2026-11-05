<?php

namespace App\Http\Controllers;

use App\Http\Requests\CategoryRequest;
use App\Http\Requests\DestroyCategoryRequest;
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
     * Exclui a categoria. Se ninguém a usa, é só excluir. Se ela está em uso por gastos ou
     * favoritos, o pedido precisa dizer para onde eles vão (outra categoria ativa ou nenhuma);
     * sem isso a categoria não é excluída.
     */
    public function destroy(DestroyCategoryRequest $request, Category $category): RedirectResponse
    {
        abort_unless($category->user_id === $request->user()->id, 404);

        $replacementId = $request->validated('replacement_id');
        $withoutCategory = $request->boolean('without_category');

        // Tudo numa transação, com a categoria travada: mover os gastos e favoritos e excluir a
        // categoria acontecem juntos, e um gasto que passe a usá-la no meio do caminho não é perdido.
        $deleted = DB::transaction(function () use ($category, $replacementId, $withoutCategory) {
            $locked = Category::whereKey($category->getKey())->lockForUpdate()->first();

            if (! $locked) {
                return false;
            }

            $inUse = $locked->expenses()->exists() || $locked->favorites()->exists();

            if ($inUse) {
                if ($replacementId === null && ! $withoutCategory) {
                    return false;
                }

                $newId = $replacementId; // nulo = sem categoria

                $locked->expenses()->update(['category_id' => $newId]);
                $locked->favorites()->update(['category_id' => $newId]);
            }

            $locked->delete();

            return true;
        });

        if (! $deleted) {
            return back()->withErrors(['category' => 'Esta categoria está em uso em gastos ou favoritos. Escolha para onde eles vão para poder excluí-la.']);
        }

        return redirect()->route('categories.index');
    }
}
