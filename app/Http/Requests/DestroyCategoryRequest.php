<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pedido de exclusão de categoria. Se a categoria estiver em uso, o pedido diz para onde vão os
 * gastos e favoritos dela: para outra categoria ativa (`replacement_id`) ou para nenhuma
 * (`without_category`).
 */
class DestroyCategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'replacement_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')
                    ->where('user_id', $this->user()->id)
                    ->where('active', true)
                    ->whereNot('id', $this->route('category')?->getKey()),
            ],
            'without_category' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'replacement_id.integer' => 'Escolha uma categoria válida para receber os gastos.',
            'replacement_id.exists' => 'Escolha outra categoria ativa para receber os gastos.',
            'without_category.boolean' => 'A opção "sem categoria" é inválida.',
        ];
    }
}
