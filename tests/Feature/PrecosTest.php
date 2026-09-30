<?php

namespace Tests\Feature;

use App\Models\Mercado;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia;
use Tests\Support\AssertsContract;
use Tests\TestCase;

class PrecosTest extends TestCase
{
    use AssertsContract;
    use RefreshDatabase;

    private function registrar(array $campos = []): TestResponse
    {
        return $this->postJson('/api/precos/compras', [...$this->contractRequest('compras.store'), ...$campos]);
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

    public function test_lista_produtos_em_ordem_alfabetica(): void
    {
        $this->registrar(['produto' => 'feijão']);
        $this->registrar(['produto' => 'Arroz']);
        $this->registrar(['produto' => 'Café']);

        $response = $this->getJson('/api/precos/produtos');

        $this->assertContract($response, 'produtos.index');
        $this->assertSame(['Arroz', 'Café', 'feijão'], array_column($response->json('produtos'), 'nome'));
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
        $invalidos = [
            'produto' => [null, '   ', str_repeat('a', 256)],
            'mercado' => [null, '   ', str_repeat('a', 256)],
            'data' => [null, '14/09/2026', '2026-13-45'],
            'quantidade' => [null, 'abc', 0, -1],
            'unidade' => [null, 'xícara'],
            'unidades_por_pacote' => [null, 0, 'abc'],
            'preco_centavos' => [null, 'abc', 0, -5, 18.9],
        ];

        foreach ($invalidos as $campo => $valores) {
            foreach ($valores as $valor) {
                $this->registrar([$campo => $valor, ...($campo === 'unidades_por_pacote' ? ['unidade' => 'pacote'] : [])])
                    ->assertUnprocessable()
                    ->assertJsonValidationErrors($campo);
            }
        }

        $this->assertDatabaseCount('compras', 0);
    }

    public function test_mensagens_de_validacao_saem_em_portugues_com_nomes_legiveis(): void
    {
        $this->registrar(['produto' => null])
            ->assertUnprocessable()
            ->assertJsonPath('errors.produto.0', 'O campo produto é obrigatório.');

        $this->registrar(['preco_centavos' => 0])
            ->assertJsonPath('errors.preco_centavos.0', 'O campo preço pago deve ser maior que zero.');

        $this->registrar(['quantidade' => 0])
            ->assertJsonPath('errors.quantidade.0', 'O campo quantidade deve ser maior que zero.');

        $this->registrar(['produto' => null, 'mercado' => null])
            ->assertJsonPath('message', 'O campo produto é obrigatório. (e mais 1 erro)');
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
            ])->assertCreated()->json('compra.produto_id');

            $response = $this->getJson("/api/precos/produtos/{$id}");

            $this->assertContract($response, 'produtos.show');
            $this->assertSame($base, $response->json('produto.unidade_base'));
            $this->assertEquals($precoBase, $response->json('compras.0.preco_base_centavos'));
        }
    }

    public function test_rejeita_unidade_de_outra_familia_que_a_do_produto(): void
    {
        $this->registrar(['produto' => 'Ovos', 'unidade' => 'un']);
        $this->registrar(['produto' => 'Café', 'unidade' => 'g']);
        $mercados = Mercado::count();

        $this->registrar(['produto' => 'ovos', 'mercado' => 'Novo', 'unidade' => 'g'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.unidade.0', 'A unidade deve ser compatível com a do produto, que é medido em un.');
        $this->registrar(['produto' => 'Café', 'unidade' => 'duzia'])
            ->assertJsonPath('errors.unidade.0', 'A unidade deve ser compatível com a do produto, que é medido em kg.');

        $this->assertDatabaseCount('compras', 2);
        $this->assertSame($mercados, Mercado::count());
    }

    public function test_unidades_por_pacote_so_e_obrigatorio_para_pacote(): void
    {
        $this->registrar(['unidade' => 'pacote', 'unidades_por_pacote' => null])
            ->assertUnprocessable()
            ->assertJsonPath('errors.unidades_por_pacote.0', 'O campo unidades por pacote é obrigatório quando unidade é pacote.');
        $this->registrar(['unidade' => 'pacote', 'unidades_por_pacote' => 0])
            ->assertJsonPath('errors.unidades_por_pacote.0', 'O campo unidades por pacote deve ser no mínimo 1.');

        $this->registrar(['produto' => 'Biscoito', 'unidade' => 'pacote', 'unidades_por_pacote' => 6])->assertCreated();
        $this->registrar(['unidade' => 'kg', 'unidades_por_pacote' => null])->assertCreated();
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
