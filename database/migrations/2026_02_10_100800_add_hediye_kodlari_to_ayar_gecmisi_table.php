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
            $table->text('hediye_kodlari')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ayar_gecmisi', function (Blueprint $table) {
            $table->dropColumn('hediye_kodlari');
        });
    }
};
