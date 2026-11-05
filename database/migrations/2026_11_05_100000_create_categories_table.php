<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('category_id')->nullable()->after('description')->constrained()->nullOnDelete();
        });

        // Usuários que já existiam antes das categorias também recebem a lista inicial.
        // Usa a tabela direto, sem o model, para esta migration não depender de código que vai mudar.
        $now = now();

        DB::table('users')->pluck('id')->each(function ($userId) use ($now) {
            DB::table('categories')->insert(
                collect(config('midas.default_categories'))->map(fn (string $name) => [
                    'user_id' => $userId,
                    'name' => $name,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all()
            );
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
        });

        Schema::dropIfExists('categories');
    }
};
