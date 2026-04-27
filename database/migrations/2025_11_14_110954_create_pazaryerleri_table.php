<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('Pazaryerleri', function (Blueprint $table) {
            $table->id(); // id (bigint)
            $table->string('Ad'); // Site, Trendyol, Etsy...
            $table->decimal('KomisyonOrani', 5, 4)->default(0); // 0.2300 = %23
            $table->timestamps();
        });

        // Varsayılan kayıtlar
        DB::table('Pazaryerleri')->insert([
            [
                'Ad' => 'Site',
                'KomisyonOrani' => 0.1800, // eski Ayarlar’daki oranını buraya yazarsın
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'Ad' => 'Trendyol',
                'KomisyonOrani' => 0.2300,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'Ad' => 'Etsy',
                'KomisyonOrani' => 0.1500, // şimdilik örnek
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('Pazaryerleri');
    }
};
