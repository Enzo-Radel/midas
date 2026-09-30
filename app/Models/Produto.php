<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    public $timestamps = false;

    protected $fillable = ['nome'];

    public function compras(): HasMany
    {
        return $this->hasMany(Compra::class);
    }

    /** Definida pela primeira compra do produto. */
    protected function unidadeBase(): Attribute
    {
        return Attribute::get(fn () => Compra::UNIDADES[$this->compras()->oldest('id')->value('unidade')][0]);
    }
}
