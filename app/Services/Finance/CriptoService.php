<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class CriptoService
{
    private const COINGECKO_BASE_URL = 'https://api.coingecko.com/api/v3';

    private const BINANCE_BASE_URL = 'https://api.binance.com/api/v3';

    public function precos(array $moedas, string $vs = 'brl'): array
    {
        sort($moedas);
        $chave = implode(',', $moedas);

        return Cache::remember(
            "coingecko:precos:{$chave}:{$vs}",
            now()->addMinutes(5),
            function () use ($moedas, $vs) {
                $response = Http::withHeaders($this->headersCoinGecko())
                    ->get(self::COINGECKO_BASE_URL.'/simple/price', [
                        'ids' => implode(',', $moedas),
                        'vs_currencies' => $vs,
                    ]);

                if ($response->failed()) {
                    Log::warning('Falha ao consultar preços na CoinGecko', [
                        'moedas' => $moedas,
                        'status' => $response->status(),
                    ]);

                    throw new RuntimeException('Não foi possível consultar a CoinGecko.');
                }

                return $response->json();
            }
        );
    }

    public function historico(string $moeda, int $dias = 30, string $vs = 'brl'): array
    {
        $dias = max(1, min($dias, 365));

        return Cache::remember(
            "coingecko:historico:{$moeda}:{$dias}:{$vs}",
            now()->addHour(),
            function () use ($moeda, $dias, $vs) {
                $response = Http::withHeaders($this->headersCoinGecko())
                    ->get(self::COINGECKO_BASE_URL."/coins/{$moeda}/market_chart", [
                        'vs_currency' => $vs,
                        'days' => $dias,
                    ]);

                if ($response->failed()) {
                    Log::warning('Falha ao consultar histórico na CoinGecko', [
                        'moeda' => $moeda,
                        'status' => $response->status(),
                    ]);

                    throw new RuntimeException("Não foi possível obter o histórico de {$moeda}.");
                }

                return $response->json();
            }
        );
    }

    public function precoBinance(string $par): array
    {
        $par = strtoupper($par);

        return Cache::remember(
            "binance:preco:{$par}",
            now()->addMinute(),
            function () use ($par) {
                $response = Http::get(self::BINANCE_BASE_URL.'/ticker/price', [
                    'symbol' => $par,
                ]);

                if ($response->failed()) {
                    Log::warning('Falha ao consultar preço na Binance', [
                        'par' => $par,
                        'status' => $response->status(),
                    ]);

                    throw new RuntimeException("Não foi possível obter o preço de {$par} na Binance.");
                }

                return $response->json();
            }
        );
    }

    private function headersCoinGecko(): array
    {
        $apiKey = config('services.coingecko.api_key');

        return $apiKey ? ['x-cg-demo-api-key' => $apiKey] : [];
    }
}
