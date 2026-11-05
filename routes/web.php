<?php

use App\Http\Controllers\ExampleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FavoriteController;
use App\Http\Middleware\UseDefaultUser;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExampleController::class, 'index']);

Route::middleware(UseDefaultUser::class)->group(function () {
    Route::get('/despesas', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/despesas/nova', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::get('/despesas/{expense}/repetir', [ExpenseController::class, 'repeat'])->name('expenses.repeat');
    Route::post('/despesas', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/despesas/{expense}/editar', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/despesas/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/despesas/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
    Route::get('/favoritos', [FavoriteController::class, 'index'])->name('favorites.index');
    Route::get('/favoritos/{favorite}/usar', [FavoriteController::class, 'use'])->name('favorites.use');
    Route::post('/despesas/{expense}/favorito', [ExpenseController::class, 'favorite'])->name('expenses.favorite');
    Route::delete('/despesas/{expense}/favorito', [ExpenseController::class, 'unfavorite'])->name('expenses.unfavorite');
});
