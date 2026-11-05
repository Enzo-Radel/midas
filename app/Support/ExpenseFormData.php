<?php

namespace App\Support;

use App\Models\Expense;
use App\Models\Favorite;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Dados que alimentam o formulário de gasto: opções de categoria e tag do usuário e o
 * preenchimento a partir de um gasto ou favorito existente.
 */
class ExpenseFormData
{
    /**
     * Categorias para escolher, na ordem de criação: só as ativas, mais a que o gasto ou favorito
     * em edição já usa (`$keepId`), mesmo desativada, para ela permanecer até ser trocada.
     */
    public static function categories(User $user, ?int $keepId = null): Collection
    {
        return $user->categories()
            ->where(fn ($query) => $query->where('active', true)->when($keepId, fn ($q) => $q->orWhere('id', $keepId)))
            ->orderBy('id')
            ->get(['id', 'name', 'active']);
    }

    /**
     * Nomes das tags do usuário, para sugerir na tela.
     */
    public static function tagNames(User $user): Collection
    {
        return $user->tags()->orderBy('name')->pluck('name');
    }

    /**
     * Valor em texto ("25.50") para os campos do formulário.
     */
    public static function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Preenchimento do cadastro a partir de um gasto ou favorito. A data não é levada:
     * o cadastro sempre usa a de hoje.
     *
     * Categoria desativada não é levada; `inactive_category` traz o nome dela para a tela avisar.
     *
     * @return array{amount: string, description: ?string, category_id: ?int, inactive_category: ?string, tags: Collection}
     */
    public static function prefill(Expense|Favorite $source): array
    {
        $category = $source->category;
        $inactive = $category !== null && ! $category->active;

        return [
            'amount' => self::amount($source->amount),
            'description' => $source->description,
            'category_id' => $inactive ? null : $source->category_id,
            'inactive_category' => $inactive ? $category->name : null,
            'tags' => $source->tags()->orderBy('name')->pluck('name'),
        ];
    }
}
