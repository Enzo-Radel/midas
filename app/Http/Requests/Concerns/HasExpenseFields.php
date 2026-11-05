<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Campos que gasto e favorito têm em comum: valor, descrição, categoria e tags.
 */
trait HasExpenseFields
{
    /**
     * @return array<string, mixed>
     */
    protected function expenseFieldRules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'description' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'max:50'],
            'category_id' => [
                'nullable',
                'integer',
                // Só categorias do usuário e ativas; a que o gasto/favorito já usa continua valendo.
                Rule::exists('categories', 'id')->where(fn ($query) => $query
                    ->where('user_id', $this->user()->id)
                    ->where(fn ($q) => $q->where('active', true)->orWhere('id', $this->currentCategoryId()))),
            ],
        ];
    }

    /**
     * Categoria que o gasto ou favorito em edição já usa (pode estar desativada); nulo no cadastro.
     */
    protected function currentCategoryId(): ?int
    {
        return null;
    }

    /**
     * @return array<string, string>
     */
    protected function expenseFieldMessages(): array
    {
        return [
            'amount.required' => 'Informe o valor.',
            'amount.numeric' => 'O valor precisa ser um número.',
            'amount.decimal' => 'O valor pode ter no máximo duas casas decimais.',
            'amount.gt' => 'O valor precisa ser maior que zero.',
            'amount.max' => 'O valor é grande demais.',
            'tags.array' => 'As tags estão em um formato inválido.',
            'tags.max' => 'Use no máximo 20 tags.',
            'tags.*.required' => 'Uma tag não pode ficar em branco.',
            'tags.*.string' => 'Uma tag precisa ser um texto.',
            'tags.*.max' => 'Cada tag pode ter no máximo 50 caracteres.',
            'category_id.integer' => 'Escolha uma categoria válida.',
            'category_id.exists' => 'Escolha uma categoria válida.',
            'description.max' => 'A descrição pode ter no máximo 255 caracteres.',
        ];
    }

    /**
     * Valor convertido para centavos, que é como é guardado.
     */
    public function amountInCents(): int
    {
        return (int) round(((float) $this->validated('amount')) * 100);
    }
}
