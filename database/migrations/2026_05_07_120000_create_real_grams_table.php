<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('real_grams', function (Blueprint $table) {
            $table->string('siparis_id')->primary();
            $table->decimal('real_gram', 10, 2)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('real_grams');
    }
};
