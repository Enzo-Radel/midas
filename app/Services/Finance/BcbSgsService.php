<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use RuntimeException;

class BcbSgsService
{
    private const BASE_URL = 'https://api.bcb.gov.br/dados/serie/bcdata.sgs';

    private const CODIGOS = [
        'selic-meta' => 432,
        'selic-diaria' => 11,
        'cdi' => 12,
        'ipca' => 433,
        'ipca-12-meses' => 13522,
        'igpm' => 189,
        'inpc' => 188,
        'tr' => 226,
        'usd-brl' => 1,
        'ibovespa' => 7,
    ];

    public function indicesDisponiveis(): array
    {
        return array_keys(self::CODIGOS);
    }

    public function ultimos(string $indice, int $quantidade = 1): array
    {
        $codigo = $this->codigoPara($indice);
        $quantidade = max(1, min($quantidade, 20));

        return Cache::remember(
            "bcb-sgs:{$indice}:ultimos:{$quantidade}",
            now()->addDay(),
            fn () => $this->buscar($codigo, "dados/ultimos/{$quantidade}")
        );
    }

    public function porPeriodo(string $indice, string $dataInicial, string $dataFinal): array
    {
        $codigo = $this->codigoPara($indice);

        return Cache::remember(
            "bcb-sgs:{$indice}:periodo:{$dataInicial}:{$dataFinal}",
            now()->addDay(),
            fn () => $this->buscar($codigo, 'dados', [
                'dataInicial' => $dataInicial,
                'dataFinal' => $dataFinal,
            ])
        );
    }

    private function codigoPara(string $indice): int
    {
        return self::CODIGOS[$indice]
            ?? throw new InvalidArgumentException("Índice de renda fixa desconhecido: {$indice}");
    }

    private function buscar(int $codigo, string $caminho, array $query = []): array
    {
        $response = Http::get(self::BASE_URL.".{$codigo}/{$caminho}", [
            ...$query,
            'formato' => 'json',
        ]);

        if ($response->failed()) {
            Log::warning('Falha ao consultar BCB SGS', [
                'codigo' => $codigo,
                'caminho' => $caminho,
                'status' => $response->status(),
            ]);

            throw new RuntimeException('Não foi possível consultar a API do Banco Central (SGS).');
        }

        return $response->json();
    }
}
