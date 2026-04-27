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
        Schema::create('ayar_gecmisi', function (Blueprint $table) {
            $table->id();
            $table->date('tarih')->unique(); // Ayarların geçerli olduğu tarih

            // Genel Ayarlar
            $table->decimal('altin_fiyat', 10, 2)->default(0);
            $table->integer('ayar')->default(585);
            $table->decimal('iscilik', 10, 2)->default(0);
            $table->decimal('kargo', 10, 2)->default(0);
            $table->decimal('kutu', 10, 2)->default(0);
            $table->decimal('reklam', 10, 2)->default(0);
            $table->decimal('kdv', 8, 2)->default(20);

            // Döviz ve Yurtdışı Ayarları
            $table->decimal('altin_usd', 10, 2)->default(0);
            $table->decimal('dolar_kuru', 10, 4)->default(1.00);
            $table->decimal('kargo_yurtdisi', 10, 2)->default(0);
            
            // Etsy Özel Ayarları
            $table->decimal('etsy_ship_cost', 8, 2)->default(10.00);
            $table->decimal('etsy_usa_tax_rate', 8, 4)->default(0.10);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ayar_gecmisi');
    }
};
