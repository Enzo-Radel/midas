<?php

namespace App\Models;

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
}
