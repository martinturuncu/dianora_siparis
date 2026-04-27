<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::connection('sqlsrv')->hasColumn('Siparisler', 'is_manuel')) {
            Schema::connection('sqlsrv')->table('Siparisler', function (Blueprint $table) {
                // is_manuel: 1 (Manuel), 0 (API/Oto)
                $table->tinyInteger('is_manuel')->default(0)->nullable();
            });
        }

        // Backfill Logic
        // SiparisID 'M' ile başlıyorsa is_manuel = 1 yap.
        DB::connection('sqlsrv')
            ->table('Siparisler')
            ->where('SiparisID', 'LIKE', 'M%')
            ->update(['is_manuel' => 1]);
            
        // Geri kalanlar (veya Etsy gibi sayısal IDler) 0 kalsın (Default 0 zaten ama update yapılabilir)
        DB::connection('sqlsrv')
            ->table('Siparisler')
            ->where('SiparisID', 'NOT LIKE', 'M%')
            ->update(['is_manuel' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('sqlsrv')->hasColumn('Siparisler', 'is_manuel')) {
            Schema::connection('sqlsrv')->table('Siparisler', function (Blueprint $table) {
                $table->dropColumn('is_manuel');
            });
        }
    }
};
