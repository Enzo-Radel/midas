<?php

namespace App\Services\Finance;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class BrapiService
{
    private const BASE_URL = 'https://brapi.dev/api';

    private const YAHOO_BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart';

    public function cotacao(string $ticker): array
    {
        $ticker = strtoupper($ticker);

        return Cache::remember(
            "brapi:cotacao:{$ticker}",
            now()->addMinutes(15),
            fn () => $this->cotacaoViaBrapi($ticker) ?? $this->cotacaoViaYahoo($ticker)
        );
    }

    public function historico(string $ticker, string $range = '3mo', string $interval = '1d'): array
    {
        $ticker = strtoupper($ticker);

        return Cache::remember(
            "brapi:historico:{$ticker}:{$range}:{$interval}",
            now()->addHours(6),
            function () use ($ticker, $range, $interval) {
                $response = Http::get(self::BASE_URL."/quote/{$ticker}", array_filter([
                    'range' => $range,
                    'interval' => $interval,
                    'token' => config('services.brapi.token'),
                ]));

                if ($response->failed()) {
                    Log::warning('Falha ao consultar histórico na brapi', [
                        'ticker' => $ticker,
                        'status' => $response->status(),
                    ]);

                    throw new RuntimeException("Não foi possível obter o histórico de {$ticker}.");
                }

                return $response->json();
            }
        );
    }

    private function cotacaoViaBrapi(string $ticker): ?array
    {
        $response = Http::get(self::BASE_URL."/quote/{$ticker}", array_filter([
            'token' => config('services.brapi.token'),
        ]));

        if ($response->successful()) {
            return $response->json();
        }

        Log::warning('Falha ao consultar cotação na brapi, tentando fallback Yahoo Finance', [
            'ticker' => $ticker,
            'status' => $response->status(),
        ]);

        return null;
    }

    private function cotacaoViaYahoo(string $ticker): array
    {
        $response = Http::get(self::YAHOO_BASE_URL."/{$ticker}.SA");

        if ($response->failed()) {
            throw new RuntimeException("Não foi possível obter a cotação de {$ticker} (brapi e Yahoo Finance falharam).");
        }

        return $response->json();
    }
}
