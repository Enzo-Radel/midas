<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * O nome não pode repetir o de outro registro do mesmo usuário, sem diferenciar maiúsculas de
 * minúsculas. Comparado em PHP (o usuário tem poucos registros) para dar o mesmo resultado em
 * qualquer banco, inclusive com letras acentuadas.
 */
class UniqueNameForUser implements ValidationRule
{
    /**
     * @param  Builder|Relation  $records  os registros do usuário (ex.: `$user->tags()`)
     * @param  Model|null  $ignore  o registro em edição, que pode manter o próprio nome
     */
    public function __construct(
        private readonly Builder|Relation $records,
        private readonly ?Model $ignore,
        private readonly string $message,
    ) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $name = mb_strtolower(trim($value));

        $taken = (clone $this->records)
            ->when($this->ignore, fn ($query) => $query->whereKeyNot($this->ignore->getKey()))
            ->pluck('name')
            ->contains(fn (string $existing) => mb_strtolower($existing) === $name);

        if ($taken) {
            $fail($this->message);
        }
    }
}
