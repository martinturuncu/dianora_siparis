<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::connection('sqlsrv')->table('Siparisler', function (Blueprint $table) {
            $table->unsignedBigInteger('PazaryeriID')->nullable()->after('SiparisDurumu');
        });
    }

    public function down(): void
    {
        Schema::connection('sqlsrv')->table('Siparisler', function (Blueprint $table) {
            $table->dropColumn('PazaryeriID');
        });
    }
};
