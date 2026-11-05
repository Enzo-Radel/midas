<?php

use App\Http\Controllers\ExampleController;
use App\Http\Controllers\ExpenseController;
use App\Http\Middleware\UseDefaultUser;
use Illuminate\Support\Facades\Route;

Route::get('/', [ExampleController::class, 'index']);

Route::middleware(UseDefaultUser::class)->group(function () {
    Route::get('/despesas', [ExpenseController::class, 'index'])->name('expenses.index');
    Route::get('/despesas/nova', [ExpenseController::class, 'create'])->name('expenses.create');
    Route::post('/despesas', [ExpenseController::class, 'store'])->name('expenses.store');
    Route::get('/despesas/{expense}/editar', [ExpenseController::class, 'edit'])->name('expenses.edit');
    Route::put('/despesas/{expense}', [ExpenseController::class, 'update'])->name('expenses.update');
    Route::delete('/despesas/{expense}', [ExpenseController::class, 'destroy'])->name('expenses.destroy');
});
