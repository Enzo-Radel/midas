<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // O favorito é uma cópia dos dados do gasto; o gasto de origem só serve para saber
        // que ele já foi favoritado e some do vínculo (sem apagar o favorito) quando é excluído.
        Schema::create('favorites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            // Único: cada gasto gera no máximo um favorito (valores nulos, de origem excluída, podem repetir).
            $table->foreignId('source_expense_id')->nullable()->unique()->constrained('expenses')->nullOnDelete();
            $table->string('name')->nullable();
            $table->string('description')->nullable();
            $table->unsignedBigInteger('amount'); // em centavos
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('favorite_tag', function (Blueprint $table) {
            $table->foreignId('favorite_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
            $table->primary(['favorite_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('favorite_tag');
        Schema::dropIfExists('favorites');
    }
};
