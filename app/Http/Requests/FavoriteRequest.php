<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasExpenseFields;
use Illuminate\Foundation\Http\FormRequest;

class FavoriteRequest extends FormRequest
{
    use HasExpenseFields;

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return $this->expenseFieldRules() + [
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return $this->expenseFieldMessages() + [
            'name.max' => 'O nome pode ter no máximo 255 caracteres.',
        ];
    }
}
