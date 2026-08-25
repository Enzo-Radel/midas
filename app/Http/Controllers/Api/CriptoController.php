<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesUpstreamErrors;
use App\Http\Controllers\Controller;
use App\Services\Finance\CriptoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CriptoController extends Controller
{
    use HandlesUpstreamErrors;

    public function __construct(private readonly CriptoService $cripto)
    {
    }

    public function precos(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'moedas' => ['required', 'string'],
            'vs' => ['nullable', 'string'],
        ]);

        $moedas = array_values(array_filter(array_map('trim', explode(',', $dados['moedas']))));

        return $this->safeJson(fn () => $this->cripto->precos($moedas, $dados['vs'] ?? 'brl'));
    }

    public function historico(Request $request, string $moeda): JsonResponse
    {
        $dados = $request->validate([
            'dias' => ['nullable', 'integer', 'min:1', 'max:365'],
            'vs' => ['nullable', 'string'],
        ]);

        return $this->safeJson(fn () => $this->cripto->historico(
            $moeda,
            $dados['dias'] ?? 30,
            $dados['vs'] ?? 'brl'
        ));
    }

    public function binance(string $par): JsonResponse
    {
        return $this->safeJson(fn () => $this->cripto->precoBinance($par));
    }
}
