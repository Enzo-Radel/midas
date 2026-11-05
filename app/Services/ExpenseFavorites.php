<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Favorite;
use Illuminate\Support\Facades\DB;

class ExpenseFavorites
{
    /**
     * Favorita um gasto criando uma cópia dos dados dele. Favoritar de novo o mesmo gasto
     * não cria outro favorito.
     */
    public function favorite(Expense $expense): Favorite
    {
        return DB::transaction(function () use ($expense) {
            $favorite = Favorite::firstOrCreate(
                ['source_expense_id' => $expense->id],
                [
                    'user_id' => $expense->user_id,
                    'name' => $expense->description,
                    'description' => $expense->description,
                    'amount' => $expense->amount,
                    'category_id' => $expense->category_id,
                ],
            );

            if ($favorite->wasRecentlyCreated) {
                $favorite->tags()->sync($expense->tags()->pluck('tags.id')->all());
            }

            return $favorite;
        });
    }

    /**
     * Remove o favorito criado a partir do gasto, sem mexer no gasto.
     */
    public function unfavorite(Expense $expense): void
    {
        Favorite::where('source_expense_id', $expense->id)->delete();
    }
}
