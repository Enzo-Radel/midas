<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Compra;
use App\Models\Mercado;
use App\Models\Produto;
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
        $produtos = Produto::withCount('compras as total_compras')->with('ultimaCompra')
            ->when(filled($q), function ($query) use ($q) {
                $padrao = '%'.strtr(mb_strtolower($q), ['!' => '!!', '%' => '!%', '_' => '!_']).'%';
                $query->whereRaw("(lower(nome) like ? escape '!' or lower(marca) like ? escape '!')", [$padrao, $padrao]);
            })
            ->orderByDesc('total_compras')->orderByRaw('lower(nome)')->orderByRaw('lower(marca)')->get();
        $produtos->each(fn ($produto) => $produto->ultimaCompra->makeHidden(['id', 'produto_id', 'mercado_id', 'data', 'promocao', 'preco_original_centavos']));

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
                'em_promocao' => $compras->where('promocao')->count(),
            ],
        ]);
    }

    public function registrar(Request $request): JsonResponse
    {
        $dados = $request->validate([
            'mercado' => ['required', 'string', 'max:255'],
            'data' => ['required', 'date_format:Y-m-d'],
            'itens' => ['required', 'array'],
            'itens.*.produto' => ['required', 'string', 'max:255'],
            'itens.*.marca' => ['nullable', 'string', 'max:255'],
            'itens.*.quantidade' => ['required', 'numeric', 'gt:0'],
            'itens.*.unidade' => ['required', Rule::in(array_keys(Compra::UNIDADES))],
            'itens.*.unidades_por_pacote' => ['required_if:itens.*.unidade,pacote', 'nullable', 'integer', 'min:1'],
            'itens.*.preco_centavos' => ['required', 'integer', 'gt:0'],
            'itens.*.promocao' => ['required', 'boolean'],
            'itens.*.preco_original_centavos' => ['nullable', 'integer', 'gt:0'],
        ]);

        // A unidade base de um produto (nome e marca) vem da primeira compra dele, inclusive dentro do próprio lote.
        $bases = [];
        $erros = [];
        foreach ($dados['itens'] as $i => $item) {
            $familia = Compra::UNIDADES[$item['unidade']][0];
            $base = $bases[json_encode([mb_strtolower($item['produto']), mb_strtolower($item['marca'] ?? '')])] ??=
                $this->buscarProduto($item['produto'], $item['marca'] ?? null)?->unidade_base ?? $familia;
            if ($base !== $familia) {
                $erros["itens.{$i}.unidade"] = "A unidade deve ser compatível com a do produto, que é medido em {$base}.";
            }
        }
        if ($erros) {
            throw ValidationException::withMessages($erros);
        }

        $mercado = $this->buscarMercado($dados['mercado'])
            ?? Mercado::create(['nome' => $dados['mercado']]);

        $compras = array_map(function ($item) use ($dados, $mercado) {
            $marca = $item['marca'] ?? null;
            $produto = $this->buscarProduto($item['produto'], $marca)
                ?? Produto::create(['nome' => $item['produto'], 'marca' => $marca]);

            return Compra::create([
                ...Arr::except($item, ['produto', 'marca']),
                'preco_original_centavos' => $item['promocao'] ? $item['preco_original_centavos'] ?? null : null,
                'data' => $dados['data'],
                'produto_id' => $produto->id,
                'mercado_id' => $mercado->id,
            ]);
        }, $dados['itens']);

        return response()->json(['compras' => $compras], 201);
    }

    public function ultimaIda(Request $request): JsonResponse
    {
        $nome = $request->query('mercado');
        $mercado = filled($nome) ? $this->buscarMercado($nome) : null;
        // Sem mercado, `where(..., null)` vira IS NULL e não acha nada: data nula e itens vazios.
        $compras = Compra::with('produto')->where('mercado_id', $mercado?->id);
        $data = $compras->max('data');

        return response()->json([
            'data' => $data,
            'itens' => $compras->where('data', $data)->orderBy('id')->get()->map(fn ($compra) => [
                'produto' => $compra->produto->nome,
                ...$compra->only(['quantidade', 'unidade', 'unidades_por_pacote', 'preco_centavos']),
                'marca' => $compra->produto->marca,
            ]),
        ]);
    }

    private function buscarMercado(string $nome): ?Mercado
    {
        return Mercado::whereRaw('lower(nome) = ?', [mb_strtolower($nome)])->first();
    }

    private function buscarProduto(string $nome, ?string $marca): ?Produto
    {
        return Produto::whereRaw('lower(nome) = ?', [mb_strtolower($nome)])
            ->when(
                $marca === null,
                fn ($query) => $query->whereNull('marca'),
                fn ($query) => $query->whereRaw('lower(marca) = ?', [mb_strtolower($marca)]),
            )->first();
    }
}
