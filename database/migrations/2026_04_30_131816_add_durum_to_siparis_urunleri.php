<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ürün bazlı iptal desteği için SiparisUrunleri tablosuna Durum kolonu ekler.
     * 0 = Aktif, 1 = İptal
     */
    public function up(): void
    {
        Schema::table('SiparisUrunleri', function (Blueprint $table) {
            $table->tinyInteger('Durum')->default(0)->after('KdvTutari');
        });
    }

    public function down(): void
    {
        Schema::table('SiparisUrunleri', function (Blueprint $table) {
            $table->dropColumn('Durum');
        });
    }
};
