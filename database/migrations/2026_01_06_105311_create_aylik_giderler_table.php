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
        Schema::connection('sqlsrv')->create('aylik_giderler', function (Blueprint $table) {
            $table->id();
            $table->integer('yil');
            $table->integer('ay');
            $table->decimal('reklam_gideri', 10, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['yil', 'ay']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('aylik_giderler');
    }
};
