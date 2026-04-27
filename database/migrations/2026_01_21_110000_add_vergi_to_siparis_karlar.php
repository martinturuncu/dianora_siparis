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
        Schema::table('SiparisKarlar', function (Blueprint $table) {
            $table->decimal('Vergi', 18, 2)->default(0)->after('GercekKar');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('SiparisKarlar', function (Blueprint $table) {
            $table->dropColumn('Vergi');
        });
    }
};
