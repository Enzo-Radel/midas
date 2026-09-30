<?php

namespace Tests\Support;

use Illuminate\Testing\TestResponse;

/**
 * Confere respostas da API contra as fixtures de contracts/precos, as mesmas
 * que o frontend usa nos seus testes. Compara o formato (campos e tipos), não os valores.
 * Regras: valor nulo na resposta é sempre aceito; nulo no contrato aceita qualquer tipo;
 * em listas, todo item segue o primeiro exemplo do contrato.
 */
trait AssertsContract
{
    protected function contract(string $name): array
    {
        $path = __DIR__.'/../../contracts/precos/'.$name.'.json';
        $this->assertFileExists($path, "Contrato {$name} não existe.");

        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function contractRequest(string $name): array
    {
        return $this->contract($name)['request'];
    }

    protected function assertContract(TestResponse $response, string $name): void
    {
        $contract = $this->contract($name);
        $response->assertStatus($contract['status']);

        $erros = $this->shapeErrors($response->json(), $contract['response']);
        $this->assertSame([], $erros, "Resposta fora do contrato {$name}:\n".implode("\n", $erros));
    }

    protected function shapeErrors(mixed $actual, mixed $expected, string $path = '$'): array
    {
        if ($expected === null || $actual === null) {
            return [];
        }

        if (! is_array($expected)) {
            $esperado = $this->kind($expected);
            $recebido = $this->kind($actual);

            return $esperado === $recebido ? [] : ["{$path}: esperado {$esperado}, recebido {$recebido}"];
        }

        if (! is_array($actual)) {
            return ["{$path}: esperado objeto ou lista, recebido ".$this->kind($actual)];
        }

        if (array_is_list($expected)) {
            if (! array_is_list($actual)) {
                return ["{$path}: esperado lista, recebido objeto"];
            }

            $erros = [];
            foreach ($actual as $i => $item) {
                $erros = [...$erros, ...$this->shapeErrors($item, $expected[0] ?? null, "{$path}[{$i}]")];
            }

            return $erros;
        }

        if ($actual !== [] && array_is_list($actual)) {
            return ["{$path}: esperado objeto, recebido lista"];
        }

        $erros = [];
        foreach (array_keys(array_diff_key($expected, $actual)) as $campo) {
            $erros[] = "{$path}.{$campo}: campo ausente";
        }
        foreach (array_keys(array_diff_key($actual, $expected)) as $campo) {
            $erros[] = "{$path}.{$campo}: campo fora do contrato";
        }
        foreach (array_intersect_key($expected, $actual) as $campo => $valor) {
            $erros = [...$erros, ...$this->shapeErrors($actual[$campo], $valor, "{$path}.{$campo}")];
        }

        return $erros;
    }

    private function kind(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_int($value), is_float($value) => 'number',
            is_string($value) => 'string',
            default => get_debug_type($value),
        };
    }
}
