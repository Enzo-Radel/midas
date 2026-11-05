<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\User;

class TagResolver
{
    /**
     * Converte nomes de tag em ids de tags do usuário. Reaproveita a tag que já existe (sem
     * diferenciar maiúsculas e espaços nas pontas) e cria as que ainda não existem.
     *
     * @param  iterable<string>  $names
     * @return list<int>
     */
    public function idsFor(User $user, iterable $names): array
    {
        $existing = $user->tags()->get()->keyBy(fn (Tag $tag) => mb_strtolower($tag->name));

        return collect($names)
            ->map(fn (string $name) => trim($name))
            ->filter(fn (string $name) => $name !== '')
            ->unique(fn (string $name) => mb_strtolower($name))
            ->map(fn (string $name) => ($existing->get(mb_strtolower($name)) ?? $user->tags()->create(['name' => $name]))->id)
            ->values()
            ->all();
    }
}
