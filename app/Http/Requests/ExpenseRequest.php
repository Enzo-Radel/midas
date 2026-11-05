<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasExpenseFields;
use Illuminate\Foundation\Http\FormRequest;

class ExpenseRequest extends FormRequest
{
    use HasExpenseFields;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->expenseFieldRules() + [
            'date' => ['required', 'date_format:Y-m-d'],
            'favorite' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->expenseFieldMessages() + [
            'date.required' => 'Informe a data.',
            'date.date_format' => 'Informe uma data válida.',
            'favorite.boolean' => 'O valor de favorito é inválido.',
        ];
    }

    protected function currentCategoryId(): ?int
    {
        return $this->route('expense')?->category_id;
    }
}
