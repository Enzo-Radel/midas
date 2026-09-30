<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compra extends Model
{
    public $timestamps = false;

    protected $fillable = ['produto_id', 'mercado_id', 'data', 'quantidade', 'unidade', 'preco_centavos'];

    protected function casts(): array
    {
        return [
            'data' => 'date:Y-m-d',
            'quantidade' => 'float',
        ];
    }

    public function mercado(): BelongsTo
    {
        return $this->belongsTo(Mercado::class);
    }
}
