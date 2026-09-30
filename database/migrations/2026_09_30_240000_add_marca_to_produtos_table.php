<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Produtos já existentes ficam com marca nula, ou seja, sem marca.
        Schema::table('produtos', function (Blueprint $table) {
            $table->string('marca')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('produtos', function (Blueprint $table) {
            $table->dropColumn('marca');
        });
    }
};
