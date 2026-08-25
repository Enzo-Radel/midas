<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Concerns\HandlesUpstreamErrors;
use App\Http\Controllers\Controller;
use App\Services\Finance\BcbSgsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RendaFixaController extends Controller
{
    use HandlesUpstreamErrors;

    public function __construct(private readonly BcbSgsService $bcb)
    {
    }

    public function indices(): JsonResponse
    {
        return response()->json([
            'indices' => $this->bcb->indicesDisponiveis(),
        ]);
    }

    public function show(Request $request, string $indice): JsonResponse
    {
        $dados = $request->validate([
            'ultimos' => ['nullable', 'integer', 'min:1', 'max:20'],
            'data_inicial' => ['nullable', 'date_format:d/m/Y', 'required_with:data_final'],
            'data_final' => ['nullable', 'date_format:d/m/Y', 'required_with:data_inicial'],
        ]);

        return $this->safeJson(function () use ($indice, $dados) {
            if (isset($dados['data_inicial'], $dados['data_final'])) {
                return $this->bcb->porPeriodo($indice, $dados['data_inicial'], $dados['data_final']);
            }

            return $this->bcb->ultimos($indice, $dados['ultimos'] ?? 1);
        });
    }
}
