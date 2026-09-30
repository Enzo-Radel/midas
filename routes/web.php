<?php

use App\Http\Controllers\ExampleController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', [ExampleController::class, 'index']);

Route::inertia('/precos', 'Precos/Index');
Route::inertia('/precos/compras/nova', 'Precos/NovaCompra');
Route::get('/precos/produtos/{id}', fn (int $id) => Inertia::render('Precos/Produto', ['produtoId' => $id]))
    ->whereNumber('id');
