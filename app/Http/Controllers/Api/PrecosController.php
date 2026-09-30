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
    public function produtos(Request $request): JsonResponse
    {
        $q = $request->query('q');
        $produtos = Produto::withCount('compras as total_compras')
            ->when(filled($q), fn ($query) => $query
                ->whereRaw("lower(nome) like ? escape '!'", ['%'.strtr(mb_strtolower($q), ['!' => '!!', '%' => '!%', '_' => '!_']).'%']))
            ->orderByDesc('total_compras')->orderByRaw('lower(nome)')->get();

        return response()->json(['produtos' => $produtos]);
    }

    public function produto(Produto $produto): JsonResponse
    {
        $compras = $produto->compras()->with('mercado')
            ->orderByDesc('data')->orderByDesc('id')->get()
            ->makeHidden(['produto_id', 'mercado_id'])->append('preco_base_centavos');

        $mediana = fn ($lista) => round($lista->pluck('preco_base_centavos')->median(), 2);
        $ultima = $compras->first();

        $mercados = $compras->groupBy('mercado_id')
            ->map(fn ($lista) => [
                'mercado' => $lista->first()->mercado,
                'mediana_centavos' => $mediana($lista),
                'contagem' => $lista->count(),
            ])
            ->sort(fn ($a, $b) => [$a['mediana_centavos'], $a['mercado']->nome] <=> [$b['mediana_centavos'], $b['mercado']->nome])
            ->values();
        $menor = $mercados->first()['mediana_centavos'];
        $mercados = $mercados->map(fn ($m) => [
            ...$m,
            'diferenca_percentual' => $mercados->count() > 1 ? round(($m['mediana_centavos'] - $menor) / $menor * 100, 1) : null,
        ]);

        return response()->json([
            'produto' => $produto->append('unidade_base'),
            'compras' => $compras,
            'mercados' => $mercados,
            'resumo' => [
                'mediana_centavos' => $mediana($compras),
                'minimo_centavos' => $compras->min('preco_base_centavos'),
                'maximo_centavos' => $compras->max('preco_base_centavos'),
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
