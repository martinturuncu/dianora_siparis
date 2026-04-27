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
        Schema::table('aylik_giderler', function (Blueprint $table) {
            $table->dropColumn('reklam_gideri');
            $table->decimal('reklam_google', 10, 2)->default(0)->after('ay');
            $table->decimal('reklam_meta', 10, 2)->default(0)->after('reklam_google');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('aylik_giderler', function (Blueprint $table) {
            $table->decimal('reklam_gideri', 10, 2)->default(0);
            $table->dropColumn(['reklam_google', 'reklam_meta']);
        });
    }
};
