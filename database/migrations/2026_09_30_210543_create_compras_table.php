<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('produto_id')->constrained();
            $table->foreignId('mercado_id')->constrained();
            $table->date('data');
            $table->double('quantidade');
            $table->string('unidade');
            $table->unsignedInteger('preco_centavos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('compras');
    }
};
