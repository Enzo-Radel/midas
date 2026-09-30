<?php

namespace Tests\Feature;

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
            'unidade' => [null, 'xícara', 'duzia'],
            'preco_centavos' => [null, 'abc', 0, -5, 18.9],
        ];

        foreach ($invalidos as $campo => $valores) {
            foreach ($valores as $valor) {
                $this->registrar([$campo => $valor])
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
