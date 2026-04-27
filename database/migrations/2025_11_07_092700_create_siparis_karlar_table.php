<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::create('SiparisKarlar', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('SiparisID');
        $table->string('UrunKodu', 100);
        $table->decimal('GercekKar', 18, 2);
        $table->timestamp('HesapTarihi')->useCurrent();

        $table->unique(['SiparisID', 'UrunKodu']); // ✅ Tekil kombinasyon
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('siparis_karlar');
    }
};
