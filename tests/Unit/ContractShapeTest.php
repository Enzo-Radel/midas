<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tests\Support\AssertsContract;

class ContractShapeTest extends TestCase
{
    use AssertsContract;

    public function test_aceita_resposta_com_o_mesmo_formato(): void
    {
        $esperado = ['id' => 1, 'nome' => 'Café', 'preco' => 3780.5, 'ativo' => true, 'itens' => [['a' => 1]]];
        $recebido = ['id' => 9, 'nome' => 'Leite', 'preco' => 600, 'ativo' => false, 'itens' => [['a' => 2], ['a' => 3]]];

        $this->assertSame([], $this->shapeErrors($recebido, $esperado));
    }

    public function test_aceita_lista_vazia_e_nulo(): void
    {
        $esperado = ['itens' => [['a' => 1]], 'ultima' => ['preco' => 10]];

        $this->assertSame([], $this->shapeErrors(['itens' => [], 'ultima' => null], $esperado));
    }

    public function test_aponta_campo_ausente_extra_e_tipo_errado(): void
    {
        $esperado = ['id' => 1, 'nome' => 'Café'];

        $this->assertSame(['$.nome: campo ausente'], $this->shapeErrors(['id' => 1], $esperado));
        $this->assertSame(['$.x: campo fora do contrato'], $this->shapeErrors(['id' => 1, 'nome' => 'a', 'x' => 1], $esperado));
        $this->assertSame(['$.id: esperado number, recebido string'], $this->shapeErrors(['id' => '1', 'nome' => 'a'], $esperado));
    }

    public function test_confere_cada_item_da_lista_contra_o_primeiro_exemplo(): void
    {
        $esperado = ['itens' => [['a' => 1]]];

        $this->assertSame(['$.itens[1].a: campo ausente'], $this->shapeErrors(['itens' => [['a' => 1], []]], $esperado));
    }
}
