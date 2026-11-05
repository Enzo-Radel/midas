<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'decimal:0,2', 'gt:0', 'max:99999999.99'],
            'date' => ['required', 'date_format:Y-m-d'],
            'description' => ['nullable', 'string', 'max:255'],
            'favorite' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'array', 'max:20'],
            'tags.*' => ['required', 'string', 'max:50'],
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where('user_id', $this->user()->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.required' => 'Informe o valor.',
            'amount.numeric' => 'O valor precisa ser um número.',
            'amount.decimal' => 'O valor pode ter no máximo duas casas decimais.',
            'amount.gt' => 'O valor precisa ser maior que zero.',
            'amount.max' => 'O valor é grande demais.',
            'date.required' => 'Informe a data.',
            'date.date_format' => 'Informe uma data válida.',
            'favorite.boolean' => 'O valor de favorito é inválido.',
            'tags.array' => 'As tags estão em um formato inválido.',
            'tags.max' => 'Use no máximo 20 tags por gasto.',
            'tags.*.required' => 'Uma tag não pode ficar em branco.',
            'tags.*.string' => 'Uma tag precisa ser um texto.',
            'tags.*.max' => 'Cada tag pode ter no máximo 50 caracteres.',
            'category_id.integer' => 'Escolha uma categoria válida.',
            'category_id.exists' => 'Escolha uma categoria válida.',
            'description.max' => 'A descrição pode ter no máximo 255 caracteres.',
        ];
    }

    /**
     * Valor convertido para centavos, que é como o gasto é guardado.
     */
    public function amountInCents(): int
    {
        return (int) round(((float) $this->validated('amount')) * 100);
    }
}
