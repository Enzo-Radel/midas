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
     * Categorias do usuário, na ordem de criação.
     */
    public static function categories(User $user): Collection
    {
        return $user->categories()->orderBy('id')->get(['id', 'name']);
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
     * @return array{amount: string, description: ?string, category_id: ?int, tags: Collection}
     */
    public static function prefill(Expense|Favorite $source): array
    {
        return [
            'amount' => self::amount($source->amount),
            'description' => $source->description,
            'category_id' => $source->category_id,
            'tags' => $source->tags()->orderBy('name')->pluck('name'),
        ];
    }
}
