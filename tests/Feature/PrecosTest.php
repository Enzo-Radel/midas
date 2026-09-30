<?php

namespace Tests\Feature;

use App\Models\Compra;
use App\Models\Mercado;
use App\Models\Produto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\Support\AssertsContract;
use Tests\TestCase;

class PrecosTest extends TestCase
{
    use AssertsContract;
    use RefreshDatabase;

    private function item(array $campos = []): array
    {
        return [...$this->contractRequest('compras.store')['itens'][0], ...$campos];
    }

    private function lote(array $itens, array $topo = []): TestResponse
    {
        $corpo = Arr::except($this->contractRequest('compras.store'), 'itens');

        return $this->postJson('/api/precos/compras', [...$corpo, ...$topo, 'itens' => $itens]);
    }

    /** Registra um único item; `mercado` e `data` vão no topo do lote, o resto no item. */
    private function registrar(array $campos = []): TestResponse
    {
        return $this->lote([$this->item(Arr::except($campos, ['mercado', 'data']))], Arr::only($campos, ['mercado', 'data']));
    }

    private function erro(TestResponse $response, string $chave): ?string
    {
        return $response->json('errors')[$chave][0] ?? null;
    }

    /** @return array{int, int, int} */
    private function contagens(): array
    {
        return [Produto::count(), Mercado::count(), Compra::count()];
    }

    public function test_registra_compra_no_formato_do_contrato(): void
    {
        $response = $this->postJson('/api/precos/compras', $this->contractRequest('compras.store'));

        $this->assertContract($response, 'compras.store');
        $this->assertDatabaseHas('compras', ['unidade' => 'g', 'preco_centavos' => 1890]);
        $this->assertDatabaseHas('produtos', ['nome' => 'Café']);
        $this->assertDatabaseHas('mercados', ['nome' => 'Atacadão']);
    }

    public function test_tres_compras_do_mesmo_produto_aparecem_da_mais_recente_para_a_mais_antiga(): void
    {
        $this->registrar(['data' => '2026-09-01', 'preco_centavos' => 100]);
        $this->registrar(['data' => '2026-09-20', 'preco_centavos' => 300]);
        $this->registrar(['data' => '2026-09-10', 'preco_centavos' => 200]);

        $response = $this->getJson('/api/precos/produtos/1');

        $this->assertContract($response, 'produtos.show');
        $this->assertSame(
            ['2026-09-20', '2026-09-10', '2026-09-01'],
            array_column($response->json('compras'), 'data'),
        );
    }

    public function test_empate_de_data_mostra_a_registrada_por_ultimo_primeiro(): void
    {
        $this->registrar(['preco_centavos' => 100]);
        $this->registrar(['preco_centavos' => 200]);

        $this->assertSame(
            [200, 100],
            array_column($this->getJson('/api/precos/produtos/1')->json('compras'), 'preco_centavos'),
        );
    }

    public function test_compra_registrada_persiste_para_uma_requisicao_nova(): void
    {
        $this->registrar();

        $response = $this->getJson('/api/precos/produtos/1');

        $this->assertContract($response, 'produtos.show');
        $this->assertCount(1, $response->json('compras'));
        $this->assertSame(1890, $response->json('compras.0.preco_centavos'));
    }

    public function test_lista_produtos_mais_comprados_primeiro_com_empate_alfabetico(): void
    {
        foreach (['feijão', 'Arroz', 'Café', 'Café', 'Café', 'feijão', 'Arroz'] as $produto) {
            $this->registrar(['produto' => $produto]);
        }

        $response = $this->getJson('/api/precos/produtos');

        $this->assertContract($response, 'produtos.index');
        $this->assertSame(['Café', 'Arroz', 'feijão'], array_column($response->json('produtos'), 'nome'));
        $this->assertSame([3, 2, 2], array_column($response->json('produtos'), 'total_compras'));
        $this->assertSame(
            array_column($response->json('produtos'), 'nome'),
            array_column($this->getJson('/api/precos/produtos?q=')->json('produtos'), 'nome'),
        );
    }

    public function test_busca_por_nome_contem_o_texto_sem_diferenciar_caixa(): void
    {
        foreach (['Café', 'Cafezinho', 'Café', 'Arroz'] as $produto) {
            $this->registrar(['produto' => $produto]);
        }

        foreach (['caf', 'CAF', 'fé'] as $q) {
            $response = $this->getJson('/api/precos/produtos?q='.urlencode($q));

            $this->assertContract($response, 'produtos.index');
            $this->assertSame($q === 'fé' ? ['Café'] : ['Café', 'Cafezinho'], array_column($response->json('produtos'), 'nome'));
        }
    }

    public function test_porcentagem_e_sublinhado_na_busca_valem_como_texto(): void
    {
        foreach (['50% Cacau', 'a_b', 'Arroz'] as $produto) {
            $this->registrar(['produto' => $produto]);
        }

        $this->assertSame(['50% Cacau'], array_column($this->getJson('/api/precos/produtos?q=%25')->json('produtos'), 'nome'));
        $this->assertSame(['a_b'], array_column($this->getJson('/api/precos/produtos?q=_')->json('produtos'), 'nome'));
    }

    public function test_consultar_nao_cria_nem_altera_registros(): void
    {
        $this->registrar();
        $antes = $this->contagens();

        $this->getJson('/api/precos/produtos?q=caf');
        $this->getJson('/api/precos/produtos?q=inexistente');
        $this->getJson('/api/precos/produtos');

        $this->assertSame($antes, $this->contagens());
    }

    public function test_produto_inexistente_retorna_404(): void
    {
        $this->getJson('/api/precos/produtos/999')->assertNotFound();
    }

    public function test_reaproveita_produto_e_mercado_pelo_nome_sem_diferenciar_caixa_nem_espacos(): void
    {
        $this->registrar();
        $this->registrar(['produto' => '  café ', 'mercado' => ' ATACADÃO  ']);

        $this->assertDatabaseCount('produtos', 1);
        $this->assertDatabaseCount('mercados', 1);
        $this->assertDatabaseCount('compras', 2);

        $this->registrar(['produto' => 'Arroz', 'mercado' => 'Extra']);

        $this->assertDatabaseCount('produtos', 2);
        $this->assertDatabaseCount('mercados', 2);
    }

    public function test_rejeita_campos_invalidos_com_422(): void
    {
        $topo = [
            'mercado' => [null, '   ', str_repeat('a', 256)],
            'data' => [null, '14/09/2026', '2026-13-45'],
        ];
        $itens = [
            'produto' => [null, '   ', str_repeat('a', 256)],
            'quantidade' => [null, 'abc', 0, -1],
            'unidade' => [null, 'xícara'],
            'unidades_por_pacote' => [null, 0, 'abc'],
            'preco_centavos' => [null, 'abc', 0, -5, 18.9],
            'promocao' => [null, 'abc'],
            'preco_original_centavos' => ['abc', 0, -5, 18.9],
        ];

        foreach ($topo as $campo => $valores) {
            foreach ($valores as $valor) {
                $this->assertArrayHasKey($campo, $this->registrar([$campo => $valor])->assertUnprocessable()->json('errors'));
            }
        }

        foreach ($itens as $campo => $valores) {
            foreach ($valores as $valor) {
                $response = $this->registrar([$campo => $valor, ...($campo === 'unidades_por_pacote' ? ['unidade' => 'pacote'] : [])]);

                $this->assertArrayHasKey("itens.0.{$campo}", $response->assertUnprocessable()->json('errors'));
            }
        }

        $this->assertSame([0, 0, 0], $this->contagens());
    }

    public function test_mensagens_de_validacao_saem_em_portugues_com_nomes_legiveis(): void
    {
        $this->assertSame('O campo produto é obrigatório.', $this->erro($this->registrar(['produto' => null]), 'itens.0.produto'));
        $this->assertSame('O campo preço pago deve ser maior que zero.', $this->erro($this->registrar(['preco_centavos' => 0]), 'itens.0.preco_centavos'));
        $this->assertSame('O campo quantidade deve ser maior que zero.', $this->erro($this->registrar(['quantidade' => 0]), 'itens.0.quantidade'));
        $this->assertSame('O campo mercado é obrigatório.', $this->erro($this->registrar(['mercado' => null]), 'mercado'));

        $this->registrar(['produto' => null, 'mercado' => null])
            ->assertJsonPath('message', 'O campo mercado é obrigatório. (e mais 1 erro)');
    }

    public function test_itens_vazio_ou_ausente_pede_ao_menos_um_item(): void
    {
        $this->assertSame('Adicione ao menos um item.', $this->erro($this->lote([]), 'itens'));

        $corpo = Arr::except($this->contractRequest('compras.store'), 'itens');
        $response = $this->postJson('/api/precos/compras', $corpo);

        $this->assertSame('Adicione ao menos um item.', $this->erro($response->assertUnprocessable(), 'itens'));
    }

    public function test_calcula_o_preco_por_unidade_base(): void
    {
        $casos = [
            // produto, quantidade, unidade, unidades_por_pacote, preço pago, unidade base, preço base
            ['Café', 500, 'g', null, 1890, 'kg', 3780],
            ['Café', 1, 'kg', null, 3490, 'kg', 3490],
            ['Leite', 500, 'ml', null, 300, 'L', 600],
            ['Ovos', 1, 'duzia', null, 1200, 'un', 100],
            ['Biscoito', 1, 'pacote', 6, 900, 'un', 150],
        ];

        foreach ($casos as [$produto, $quantidade, $unidade, $porPacote, $preco, $base, $precoBase]) {
            $id = $this->registrar([
                'produto' => $produto, 'quantidade' => $quantidade, 'unidade' => $unidade,
                'unidades_por_pacote' => $porPacote, 'preco_centavos' => $preco,
            ])->assertCreated()->json('compras.0.produto_id');

            $response = $this->getJson("/api/precos/produtos/{$id}");

            $this->assertContract($response, 'produtos.show');
            $this->assertSame($base, $response->json('produto.unidade_base'));
            $this->assertEquals($precoBase, $response->json('compras.0.preco_base_centavos'));
        }
    }

    public function test_rejeita_unidade_de_outra_familia_que_a_do_produto_e_nao_salva_nada(): void
    {
        $this->registrar(['produto' => 'Café', 'unidade' => 'g']);
        $antes = $this->contagens();

        $response = $this->lote([
            $this->item(['produto' => 'Arroz', 'unidade' => 'kg']),
            $this->item(['produto' => 'café', 'unidade' => 'un']),
            $this->item(['produto' => 'Leite', 'unidade' => 'L']),
        ], ['mercado' => 'Mercado novo'])->assertUnprocessable();

        $this->assertSame('A unidade deve ser compatível com a do produto, que é medido em kg.', $this->erro($response, 'itens.1.unidade'));
        $this->assertSame($antes, $this->contagens());
    }

    public function test_rejeita_unidades_de_familias_diferentes_para_o_mesmo_produto_no_mesmo_lote(): void
    {
        $response = $this->lote([
            $this->item(['produto' => 'Ovos', 'unidade' => 'un']),
            $this->item(['produto' => 'ovos', 'unidade' => 'g']),
        ])->assertUnprocessable();

        $this->assertSame('A unidade deve ser compatível com a do produto, que é medido em un.', $this->erro($response, 'itens.1.unidade'));
        $this->assertSame([0, 0, 0], $this->contagens());
    }

    public function test_unidades_por_pacote_so_e_obrigatorio_para_pacote(): void
    {
        $this->assertSame(
            'O campo unidades por pacote é obrigatório quando unidade é pacote.',
            $this->erro($this->registrar(['unidade' => 'pacote', 'unidades_por_pacote' => null]), 'itens.0.unidades_por_pacote'),
        );
        $this->assertSame(
            'O campo unidades por pacote deve ser no mínimo 1.',
            $this->erro($this->registrar(['unidade' => 'pacote', 'unidades_por_pacote' => 0]), 'itens.0.unidades_por_pacote'),
        );

        $this->registrar(['produto' => 'Biscoito', 'unidade' => 'pacote', 'unidades_por_pacote' => 6])->assertCreated();
        $this->registrar(['unidade' => 'kg', 'unidades_por_pacote' => null])->assertCreated();
    }

    public function test_registra_varios_itens_na_ordem_enviada_reaproveitando_produto_e_mercado(): void
    {
        $this->registrar();

        $response = $this->lote([
            $this->item(['produto' => 'café']),
            $this->item(['produto' => 'Arroz', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 600]),
            $this->item(['produto' => 'Biscoito', 'quantidade' => 1, 'unidade' => 'pacote', 'unidades_por_pacote' => 6, 'preco_centavos' => 900]),
        ], ['mercado' => 'ATACADÃO']);

        $this->assertContract($response, 'compras.store');
        $this->assertCount(3, $response->json('compras'));
        $this->assertSame([1, 2, 3], array_column($response->json('compras'), 'produto_id'));
        $this->assertSame([6, 600, 900], [$response->json('compras.2.unidades_por_pacote'), $response->json('compras.1.preco_centavos'), $response->json('compras.2.preco_centavos')]);
        $this->assertSame([3, 1, 4], $this->contagens());
    }

    public function test_mesmo_produto_duas_vezes_no_lote_vale_duas_compras(): void
    {
        $this->lote([$this->item(), $this->item(['preco_centavos' => 2000])])->assertCreated();

        $this->assertSame([1, 1, 2], $this->contagens());
    }

    public function test_aceita_lote_de_15_itens(): void
    {
        $itens = array_map(fn ($n) => $this->item(['produto' => "Produto {$n}"]), range(1, 15));

        $this->assertCount(15, $this->lote($itens)->assertCreated()->json('compras'));
        $this->assertSame([15, 1, 15], $this->contagens());
    }

    public function test_tudo_ou_nada_um_item_invalido_nao_salva_nenhum(): void
    {
        $response = $this->lote([
            $this->item(['produto' => 'Arroz']),
            $this->item(['produto' => 'Feijão', 'preco_centavos' => 0]),
            $this->item(['produto' => 'Leite']),
        ], ['mercado' => 'Mercado novo'])->assertUnprocessable();

        $this->assertSame('O campo preço pago deve ser maior que zero.', $this->erro($response, 'itens.1.preco_centavos'));
        $this->assertSame([0, 0, 0], $this->contagens());
    }

    public function test_index_traz_a_ultima_compra_de_cada_produto(): void
    {
        $this->registrar(['data' => '2026-09-10', 'quantidade' => 500, 'unidade' => 'g', 'preco_centavos' => 1890]);
        $this->registrar(['data' => '2026-09-20', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 3490]);
        $this->registrar(['data' => '2026-09-20', 'quantidade' => 2.5, 'unidade' => 'kg', 'preco_centavos' => 8000]);
        $this->registrar(['data' => '2026-09-01', 'produto' => 'Biscoito', 'quantidade' => 2, 'unidade' => 'pacote', 'unidades_por_pacote' => 6, 'preco_centavos' => 900]);

        $response = $this->getJson('/api/precos/produtos');
        $ultimas = array_column($response->json('produtos'), 'ultima_compra', 'nome');

        $this->assertContract($response, 'produtos.index');
        $this->assertSame(2.5, $ultimas['Café']['quantidade']);
        $this->assertSame(['kg', null, 8000], [$ultimas['Café']['unidade'], $ultimas['Café']['unidades_por_pacote'], $ultimas['Café']['preco_centavos']]);
        $this->assertSame(6, $ultimas['Biscoito']['unidades_por_pacote']);
    }

    public function test_resumo_do_preco_por_unidade_base(): void
    {
        foreach ([1000, 1000, 1000, 3000] as $preco) {
            $this->registrar(['quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => $preco]);
        }

        $response = $this->getJson('/api/precos/produtos/1');

        $this->assertContract($response, 'produtos.show');
        $resumo = $response->json('resumo');

        $this->assertEquals(1000, $resumo['mediana_centavos']);
        $this->assertEquals(1000, $resumo['minimo_centavos']);
        $this->assertEquals(3000, $resumo['maximo_centavos']);
        $this->assertSame(4, $resumo['contagem']);
    }

    public function test_mediana_com_quantidade_par_e_a_media_dos_dois_centrais(): void
    {
        $this->registrar(['quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 1000]);
        $this->registrar(['quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 2000]);

        $this->assertEquals(1500, $this->getJson('/api/precos/produtos/1')->json('resumo.mediana_centavos'));
    }

    public function test_compra_unica_da_resumo_com_contagem_1(): void
    {
        $this->registrar();

        $resumo = $this->getJson('/api/precos/produtos/1')->json('resumo');

        $this->assertSame(1, $resumo['contagem']);
        $this->assertEquals(3780, $resumo['mediana_centavos']);
        $this->assertEquals(3780, $resumo['minimo_centavos']);
        $this->assertEquals(3780, $resumo['maximo_centavos']);
    }

    public function test_resumo_usa_o_preco_por_unidade_base_de_embalagens_misturadas(): void
    {
        $this->registrar(['quantidade' => 500, 'unidade' => 'g', 'preco_centavos' => 1890]); // 3780/kg
        $this->registrar(['quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 3490]);
        $this->registrar(['quantidade' => 250, 'unidade' => 'g', 'preco_centavos' => 1000]); // 4000/kg

        $resumo = $this->getJson('/api/precos/produtos/1')->json('resumo');

        $this->assertEquals(3780, $resumo['mediana_centavos']);
        $this->assertEquals(3490, $resumo['minimo_centavos']);
        $this->assertEquals(4000, $resumo['maximo_centavos']);
    }

    public function test_resumo_traz_a_ultima_compra_e_o_periodo_coberto(): void
    {
        $this->registrar(['data' => '2026-09-10', 'mercado' => 'A', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 1000]);
        $this->registrar(['data' => '2026-09-20', 'mercado' => 'B', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 2000]);
        $this->registrar(['data' => '2026-09-20', 'mercado' => 'C', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 3000]);
        $this->registrar(['data' => '2026-08-01', 'mercado' => 'D', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 500]);

        $resumo = $this->getJson('/api/precos/produtos/1')->json('resumo');

        $this->assertSame('C', $resumo['ultima']['mercado']['nome']);
        $this->assertSame('2026-09-20', $resumo['ultima']['data']);
        $this->assertEquals(3000, $resumo['ultima']['preco_base_centavos']);
        $this->assertSame(['inicio' => '2026-08-01', 'fim' => '2026-09-20'], $resumo['periodo']);
    }

    public function test_compara_a_mediana_por_mercado_usando_so_as_compras_de_cada_um(): void
    {
        $this->registrar(['mercado' => 'Mercado do bairro', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 3910]);
        $this->registrar(['mercado' => 'Atacadão', 'quantidade' => 500, 'unidade' => 'g', 'preco_centavos' => 1860]); // 3720/kg
        $this->registrar(['mercado' => 'Atacadão', 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => 3720]);

        $response = $this->getJson('/api/precos/produtos/1');
        $mercados = $response->json('mercados');

        $this->assertContract($response, 'produtos.show');
        $this->assertSame(['Atacadão', 'Mercado do bairro'], array_column(array_column($mercados, 'mercado'), 'nome'));
        $this->assertEquals([3720, 3910], array_column($mercados, 'mediana_centavos'));
        $this->assertSame([2, 1], array_column($mercados, 'contagem'));
        $this->assertEquals([0, 5.1], array_column($mercados, 'diferenca_percentual'));
        $this->assertSame(3, $response->json('resumo.contagem'));
        $this->assertCount(3, $response->json('compras'));
    }

    public function test_produto_de_um_so_mercado_tem_uma_linha_sem_diferenca(): void
    {
        $this->registrar();
        $this->registrar();

        $mercados = $this->getJson('/api/precos/produtos/1')->json('mercados');

        $this->assertCount(1, $mercados);
        $this->assertSame(2, $mercados[0]['contagem']);
        $this->assertNull($mercados[0]['diferenca_percentual']);
    }

    public function test_mercados_ordenados_pela_mediana_e_empate_pelo_nome(): void
    {
        foreach ([['B', 1000], ['A', 1000], ['C', 500]] as [$mercado, $preco]) {
            $this->registrar(['mercado' => $mercado, 'quantidade' => 1, 'unidade' => 'kg', 'preco_centavos' => $preco]);
        }

        $mercados = $this->getJson('/api/precos/produtos/1')->json('mercados');

        $this->assertSame(['C', 'A', 'B'], array_column(array_column($mercados, 'mercado'), 'nome'));
        $this->assertEquals([0, 100, 100], array_column($mercados, 'diferenca_percentual'));
    }

    public function test_ultima_ida_devolve_os_itens_da_data_mais_recente_do_mercado(): void
    {
        $this->registrar(['mercado' => 'Atacadão', 'data' => '2026-09-10', 'produto' => 'Arroz']);
        $this->registrar(['mercado' => 'Atacadão', 'data' => '2026-09-20', 'produto' => 'Café']);
        $this->registrar(['mercado' => 'Bairro', 'data' => '2026-09-25', 'produto' => 'Leite', 'quantidade' => 1, 'unidade' => 'L']);
        $this->registrar(['mercado' => 'Atacadão', 'data' => '2026-09-20', 'produto' => 'Biscoito', 'quantidade' => 2, 'unidade' => 'pacote', 'unidades_por_pacote' => 6, 'preco_centavos' => 900]);

        $response = $this->getJson('/api/precos/compras/ultima-ida?mercado='.urlencode('ATACADÃO'));

        $this->assertContract($response, 'compras.ultima-ida');
        $this->assertSame('2026-09-20', $response->json('data'));
        $this->assertSame(['Café', 'Biscoito'], array_column($response->json('itens'), 'produto'));
        $this->assertSame(500.0, (float) $response->json('itens.0.quantidade'));
        $this->assertSame([null, 6], array_column($response->json('itens'), 'unidades_por_pacote'));
        $this->assertSame([1890, 900], array_column($response->json('itens'), 'preco_centavos'));
    }

    public function test_ultima_ida_de_mercado_desconhecido_vazio_ou_ausente_devolve_vazio_sem_criar_nada(): void
    {
        $this->registrar();
        $antes = $this->contagens();

        foreach (['?mercado=Desconhecido', '?mercado=', ''] as $consulta) {
            $response = $this->getJson('/api/precos/compras/ultima-ida'.$consulta);

            $this->assertContract($response, 'compras.ultima-ida');
            $this->assertNull($response->json('data'));
            $this->assertSame([], $response->json('itens'));
        }

        $this->assertSame($antes, $this->contagens());
    }

    public function test_marca_promocao_com_ou_sem_preco_original(): void
    {
        $sem = $this->registrar(['promocao' => true, 'preco_original_centavos' => null])->assertCreated();
        $this->assertSame([true, null], [$sem->json('compras.0.promocao'), $sem->json('compras.0.preco_original_centavos')]);

        $com = $this->registrar(['promocao' => true, 'preco_original_centavos' => 3990])->assertCreated();
        $this->assertSame([true, 3990], [$com->json('compras.0.promocao'), $com->json('compras.0.preco_original_centavos')]);

        $show = $this->getJson('/api/precos/produtos/1');
        $this->assertContract($show, 'produtos.show');
        $this->assertSame([true, 3990], [$show->json('compras.0.promocao'), $show->json('compras.0.preco_original_centavos')]);
        $this->assertSame(2, $show->json('resumo.em_promocao'));
    }

    public function test_sem_promocao_o_preco_original_e_guardado_como_nulo(): void
    {
        $response = $this->registrar(['promocao' => false, 'preco_original_centavos' => 3990])->assertCreated();

        $this->assertSame([false, null], [$response->json('compras.0.promocao'), $response->json('compras.0.preco_original_centavos')]);
        $this->assertDatabaseHas('compras', ['promocao' => false, 'preco_original_centavos' => null]);
    }

    public function test_preco_original_invalido_da_mensagem_em_portugues(): void
    {
        $this->assertSame(
            'O campo preço original deve ser maior que zero.',
            $this->erro($this->registrar(['preco_original_centavos' => 0]), 'itens.0.preco_original_centavos'),
        );
        $this->assertSame(
            'O campo preço original deve ser um número inteiro.',
            $this->erro($this->registrar(['preco_original_centavos' => 18.9]), 'itens.0.preco_original_centavos'),
        );
    }

    public function test_promocao_nao_altera_nenhum_calculo(): void
    {
        // Mesmas compras para dois produtos: um sem marcação, outro com promoção em algumas.
        $compras = [
            ['Atacadão', '2026-09-01', 1, 'kg', 3490, false],
            ['Bairro', '2026-09-10', 500, 'g', 1955, true],
            ['Atacadão', '2026-09-20', 1, 'kg', 1000, true],
            ['Bairro', '2026-09-20', 2, 'kg', 7000, false],
        ];
        foreach (['Sem' => false, 'Com' => true] as $produto => $usaPromocao) {
            foreach ($compras as [$mercado, $data, $quantidade, $unidade, $preco, $promocao]) {
                $this->registrar([
                    'produto' => $produto, 'mercado' => $mercado, 'data' => $data, 'quantidade' => $quantidade,
                    'unidade' => $unidade, 'preco_centavos' => $preco, 'promocao' => $usaPromocao && $promocao,
                ])->assertCreated();
            }
        }

        $sem = $this->getJson('/api/precos/produtos/1')->json();
        $com = $this->getJson('/api/precos/produtos/2')->json();

        $this->assertSame([0, 2], [$sem['resumo']['em_promocao'], $com['resumo']['em_promocao']]);
        $this->assertEquals(Arr::except($sem['resumo'], 'em_promocao'), Arr::except($com['resumo'], 'em_promocao'));
        $this->assertEquals($sem['mercados'], $com['mercados']);
        $this->assertEquals(
            array_map(fn ($c) => Arr::only($c, ['data', 'mercado', 'preco_base_centavos']), $sem['compras']),
            array_map(fn ($c) => Arr::only($c, ['data', 'mercado', 'preco_base_centavos']), $com['compras']),
        );
    }

    public function test_paginas_renderizam_o_componente_com_as_props_do_contrato(): void
    {
        foreach ($this->contract('paginas') as $rota => $pagina) {
            $this->get(str_replace('{id}', '1', $rota))
                ->assertInertia(fn (AssertableInertia $page) => $page
                    // Arquivos das páginas são do frontend: não exigir que existam aqui.
                    ->component($pagina['componente'], false)
                    ->whereAll($pagina['props']));
        }
    }
}
