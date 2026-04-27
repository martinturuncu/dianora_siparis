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
        Schema::table('ayar_gecmisi', function (Blueprint $table) {
            $table->decimal('komisyon_site', 8, 4)->default(0.0500);
            $table->decimal('komisyon_trendyol', 8, 4)->default(0.2250);
            $table->decimal('komisyon_etsy', 8, 4)->default(0.1600);
            $table->decimal('komisyon_hipicon', 8, 4)->default(0.3000);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ayar_gecmisi', function (Blueprint $table) {
            $table->dropColumn(['komisyon_site', 'komisyon_trendyol', 'komisyon_etsy', 'komisyon_hipicon']);
        });
    }
};
