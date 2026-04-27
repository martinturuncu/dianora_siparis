<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$idsToDelete = [
    '3943370471',
    '3935573659',
    '3935566135',
    '3926511198',
    '3923924408',
    '3928052801',
    '3925274795'
];

try {
    DB::connection('sqlsrv')->transaction(function () use ($idsToDelete) {
        // 1. SiparisUrunleri'nden sil
        $deletedUrunler = DB::connection('sqlsrv')
            ->table('SiparisUrunleri')
            ->whereIn('SiparisID', $idsToDelete)
            ->delete();

        // 2. Siparisler'den sil
        $deletedSiparisler = DB::connection('sqlsrv')
            ->table('Siparisler')
            ->whereIn('SiparisID', $idsToDelete)
            ->delete();
            
        // 3. SiparisKarlar'dan da silelim (Varsa) - Temiz sayfa
        $deletedKarlar = DB::connection('sqlsrv')
            ->table('SiparisKarlar')
            ->whereIn('SiparisID', $idsToDelete)
            ->delete();

        echo "Başarıyla Silindi:\n";
        echo "Siparisler Tablosundan: $deletedSiparisler kayıt\n";
        echo "SiparisUrunleri Tablosundan: $deletedUrunler kayıt\n";
        echo "SiparisKarlar Tablosundan: $deletedKarlar kayıt\n";
    });
} catch (\Exception $e) {
    echo "HATA OLUŞTU: " . $e->getMessage();
}
