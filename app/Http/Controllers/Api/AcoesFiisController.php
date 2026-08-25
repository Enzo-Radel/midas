<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesUpstreamErrors;
use App\Http\Controllers\Controller;
use App\Services\Finance\BrapiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AcoesFiisController extends Controller
{
    use HandlesUpstreamErrors;

    public function __construct(private readonly BrapiService $brapi)
    {
    }

    public function show(string $ticker): JsonResponse
    {
        return $this->safeJson(fn () => $this->brapi->cotacao($ticker));
    }

    public function historico(Request $request, string $ticker): JsonResponse
    {
        $dados = $request->validate([
            'range' => ['nullable', 'in:1d,5d,1mo,3mo,6mo,1y,2y,5y,10y,ytd,max'],
            'interval' => ['nullable', 'in:1m,2m,5m,15m,30m,60m,90m,1h,1d,5d,1wk,1mo,3mo'],
        ]);

        return $this->safeJson(fn () => $this->brapi->historico(
            $ticker,
            $dados['range'] ?? '3mo',
            $dados['interval'] ?? '1d'
        ));
    }
}
