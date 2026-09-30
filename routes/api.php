<?php

use App\Http\Controllers\Api\AcoesFiisController;
use App\Http\Controllers\Api\CriptoController;
use App\Http\Controllers\Api\PrecosController;
use App\Http\Controllers\Api\RendaFixaController;
use Illuminate\Support\Facades\Route;

Route::prefix('renda-fixa')->group(function () {
    Route::get('/', [RendaFixaController::class, 'indices']);
    Route::get('/{indice}', [RendaFixaController::class, 'show']);
});

Route::prefix('acoes')->group(function () {
    Route::get('/{ticker}', [AcoesFiisController::class, 'show']);
    Route::get('/{ticker}/historico', [AcoesFiisController::class, 'historico']);
});

Route::prefix('fiis')->group(function () {
    Route::get('/{ticker}', [AcoesFiisController::class, 'show']);
    Route::get('/{ticker}/historico', [AcoesFiisController::class, 'historico']);
});

Route::prefix('cripto')->group(function () {
    Route::get('/precos', [CriptoController::class, 'precos']);
    Route::get('/binance/{par}', [CriptoController::class, 'binance']);
    Route::get('/{moeda}/historico', [CriptoController::class, 'historico']);
});

Route::prefix('precos')->group(function () {
    Route::get('/produtos', [PrecosController::class, 'produtos']);
    Route::get('/produtos/{produto}', [PrecosController::class, 'produto']);
    Route::post('/compras', [PrecosController::class, 'registrar']);
});
