<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Mercado;
use App\Models\Produto;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class PrecosController extends Controller
{
    public function produtos(): JsonResponse
    {
        return response()->json([
            'produtos' => Produto::orderByRaw('lower(nome)')->get(),
        ]);
    }

    public function produto(Produto $produto): JsonResponse
    {
        $compras = $produto->compras()->with('mercado')
            ->orderByDesc('data')->orderByDesc('id')->get()
            ->makeHidden(['produto_id', 'mercado_id']);

        return response()->json(['produto' => $produto, 'compras' => $compras]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'produto' => ['required', 'string', 'max:255'],
            'mercado' => ['required', 'string', 'max:255'],
            'data' => ['required', 'date_format:Y-m-d'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'unidade' => ['required', Rule::in(['kg', 'g', 'L', 'ml', 'un'])],
            'preco_centavos' => ['required', 'integer', 'gt:0'],
        ]);

        $compra = Compra::create([
            ...Arr::except($dados, ['produto', 'mercado']),
            'produto_id' => $this->porNome(Produto::class, $dados['produto'])->id,
            'mercado_id' => $this->porNome(Mercado::class, $dados['mercado'])->id,
        ]);

        return response()->json(['compra' => $compra], 201);
    }

    private function porNome(string $modelo, string $nome): Model
    {
        return $modelo::whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->first()
            ?? $modelo::create(['nome' => $nome]);
    }
}
