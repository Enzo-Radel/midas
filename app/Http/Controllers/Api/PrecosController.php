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
use Illuminate\Validation\ValidationException;

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
            ->makeHidden(['produto_id', 'mercado_id'])->append('preco_base_centavos');

        $precos = $compras->pluck('preco_base_centavos');
        $ultima = $compras->first();

        return response()->json([
            'produto' => $produto->append('unidade_base'),
            'compras' => $compras,
            'resumo' => [
                'mediana_centavos' => round($precos->median(), 2),
                'minimo_centavos' => $precos->min(),
                'maximo_centavos' => $precos->max(),
                'contagem' => $compras->count(),
                'ultima' => [
                    'preco_base_centavos' => $ultima->preco_base_centavos,
                    'mercado' => $ultima->mercado,
                    'data' => $ultima->data->toDateString(),
                ],
                'periodo' => [
                    'inicio' => $compras->last()->data->toDateString(),
                    'fim' => $ultima->data->toDateString(),
                ],
            ],
        ]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'produto' => ['required', 'string', 'max:255'],
            'mercado' => ['required', 'string', 'max:255'],
            'data' => ['required', 'date_format:Y-m-d'],
            'quantidade' => ['required', 'numeric', 'gt:0'],
            'unidade' => ['required', Rule::in(array_keys(Compra::UNIDADES))],
            'unidades_por_pacote' => ['required_if:unidade,pacote', 'nullable', 'integer', 'min:1'],
            'preco_centavos' => ['required', 'integer', 'gt:0'],
        ]);

        $produto = $this->buscarPorNome(Produto::class, $dados['produto']);
        $base = $produto?->unidade_base;
        if ($base && $base !== Compra::UNIDADES[$dados['unidade']][0]) {
            throw ValidationException::withMessages([
                'unidade' => "A unidade deve ser compatível com a do produto, que é medido em {$base}.",
            ]);
        }

        $produto ??= Produto::create(['nome' => $dados['produto']]);
        $mercado = $this->buscarPorNome(Mercado::class, $dados['mercado'])
            ?? Mercado::create(['nome' => $dados['mercado']]);

        $compra = Compra::create([
            ...Arr::except($dados, ['produto', 'mercado']),
            'produto_id' => $produto->id,
            'mercado_id' => $mercado->id,
        ]);

        return response()->json(['compra' => $compra], 201);
    }

    private function buscarPorNome(string $modelo, string $nome): ?Model
    {
        return $modelo::whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->first();
    }
}
