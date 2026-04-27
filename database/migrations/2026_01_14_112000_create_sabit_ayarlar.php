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
        // SQL Server veya MySQL uyumlu olması için text/string ayrımına dikkat edelim.
        // Laravel'de 'text' genelde nvarchar(max) or text'e map olur.
        if (!Schema::connection('sqlsrv')->hasTable('sabit_ayarlar')) {
            Schema::connection('sqlsrv')->create('sabit_ayarlar', function (Blueprint $table) {
                $table->id('Id');
                $table->string('Anahtar', 255)->unique();
                $table->text('Deger')->nullable(); // Uzun tokenlar için
                $table->string('Aciklama', 255)->nullable();
                $table->timestamps();
            });
            
            // Varsayılan boş kayıtları ekleyelim ki kullanıcı ne gireceğini bilsin
            DB::connection('sqlsrv')->table('sabit_ayarlar')->insert([
                ['Anahtar' => 'etsy_client_id', 'Deger' => '', 'Aciklama' => 'Etsy App API Key String (Keystring)'],
                ['Anahtar' => 'etsy_refresh_token', 'Deger' => '', 'Aciklama' => 'Etsy OAuth Refresh Token'],
                ['Anahtar' => 'etsy_shop_id', 'Deger' => '', 'Aciklama' => 'Etsy Shop ID (fetchOrders için)']
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('sqlsrv')->dropIfExists('sabit_ayarlar');
    }
};
