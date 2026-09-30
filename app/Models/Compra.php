<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Compra extends Model
{
    /** Unidade => [unidade base, quanto vale na base]. O pacote vale `unidades_por_pacote`. */
    public const UNIDADES = [
        'kg' => ['kg', 1],
        'g' => ['kg', 0.001],
        'L' => ['L', 1],
        'ml' => ['L', 0.001],
        'un' => ['un', 1],
        'duzia' => ['un', 12],
        'pacote' => ['un', null],
    ];

    public $timestamps = false;

    protected $fillable = ['produto_id', 'mercado_id', 'data', 'quantidade', 'unidade', 'unidades_por_pacote', 'preco_centavos'];

    protected function casts(): array
    {
        return [
            'data' => 'date:Y-m-d',
            'quantidade' => 'float',
        ];
    }

    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class);
    }

    public function mercado(): BelongsTo
    {
        return $this->belongsTo(Mercado::class);
    }

    protected function precoBaseCentavos(): Attribute
    {
        return Attribute::get(function () {
            $valor = self::UNIDADES[$this->unidade][1] ?? $this->unidades_por_pacote;

            return round($this->preco_centavos / ($this->quantidade * $valor), 2);
        });
    }
}
