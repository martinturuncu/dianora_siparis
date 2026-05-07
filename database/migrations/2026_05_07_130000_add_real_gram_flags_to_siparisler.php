<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('mysql')->hasColumn('Siparisler', 'RealGramOnaylandi')) {
            Schema::connection('mysql')->table('Siparisler', function (Blueprint $table) {
                $table->boolean('RealGramOnaylandi')->default(false);
            });
        }
        if (!Schema::connection('mysql')->hasColumn('Siparisler', 'RealGramReddedildi')) {
            Schema::connection('mysql')->table('Siparisler', function (Blueprint $table) {
                $table->boolean('RealGramReddedildi')->default(false);
            });
        }
    }

    public function down(): void
    {
        Schema::connection('mysql')->table('Siparisler', function (Blueprint $table) {
            if (Schema::connection('mysql')->hasColumn('Siparisler', 'RealGramOnaylandi')) {
                $table->dropColumn('RealGramOnaylandi');
            }
            if (Schema::connection('mysql')->hasColumn('Siparisler', 'RealGramReddedildi')) {
                $table->dropColumn('RealGramReddedildi');
            }
        });
    }
};
