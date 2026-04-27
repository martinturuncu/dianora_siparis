<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('ayarlar', function (Blueprint $table) {
        $table->id();
        $table->decimal('altin_fiyat', 10, 2)->default(5800);
        $table->integer('ayar')->default(585);
        $table->decimal('iscilik', 10, 2)->default(350);
        $table->decimal('kargo', 10, 2)->default(200);
        $table->decimal('kutu', 10, 2)->default(50);
        $table->decimal('reklam', 10, 2)->default(200);
        $table->decimal('komisyon', 5, 2)->default(8);
        $table->decimal('kdv', 5, 2)->default(20);
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ayarlar');
    }
};
