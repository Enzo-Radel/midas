<?php

namespace App\Http\Requests;

use App\Rules\UniqueNameForUser;
use Illuminate\Foundation\Http\FormRequest;

class TagRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:50',
                new UniqueNameForUser($this->user()->tags(), $this->route('tag'), 'Já existe uma tag com esse nome.'),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da tag.',
            'name.string' => 'O nome da tag precisa ser um texto.',
            'name.max' => 'O nome pode ter no máximo 50 caracteres.',
        ];
    }
}
