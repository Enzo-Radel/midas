<?php

namespace App\Http\Requests;

use App\Models\Category;
use Closure;
use Illuminate\Foundation\Http\FormRequest;

class CategoryRequest extends FormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50', $this->uniqueNameRule()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Informe o nome da categoria.',
            'name.string' => 'O nome da categoria precisa ser um texto.',
            'name.max' => 'O nome pode ter no máximo 50 caracteres.',
        ];
    }

    /**
     * Não pode haver duas categorias do mesmo usuário com o mesmo nome, sem diferenciar maiúsculas
     * de minúsculas. Comparado em PHP (o usuário tem poucas categorias) para dar o mesmo resultado
     * em qualquer banco, inclusive com letras acentuadas.
     */
    private function uniqueNameRule(): Closure
    {
        return function (string $attribute, mixed $value, Closure $fail) {
            if (! is_string($value)) {
                return;
            }

            /** @var Category|null $current */
            $current = $this->route('category');
            $name = mb_strtolower(trim($value));

            $taken = $this->user()->categories()
                ->when($current, fn ($query) => $query->whereKeyNot($current->getKey()))
                ->pluck('name')
                ->contains(fn (string $existing) => mb_strtolower($existing) === $name);

            if ($taken) {
                $fail('Já existe uma categoria com esse nome.');
            }
        };
    }
}
