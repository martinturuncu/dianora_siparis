<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

use App\Http\Controllers\SiparisController;
use App\Http\Controllers\UrunController;
use App\Http\Controllers\AyarController;
use App\Http\Controllers\KategoriController;
use App\Http\Controllers\FiyatController;
use App\Http\Controllers\PazaryeriController;
use App\Http\Controllers\ManuelSiparisController;
use App\Http\Controllers\NotController;

// ...

// 🔹 AUTH ROUTES (Unprotected)
Route::get('/login', [\App\Http\Controllers\AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])->middleware('throttle:5,1');
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])->name('logout');

// 🔹 PROTECTED ROUTES
Route::middleware(['admin.auth'])->group(function () {

    // 🔹 Notlar (Notepad)
    Route::get('/notlar/get', [NotController::class, 'getNot']);
    Route::post('/notlar/save', [NotController::class, 'saveNot']);


    // Ana yönlendirme
    Route::get('/mobile', [App\Http\Controllers\MobileController::class, 'index'])->name('mobile');
    Route::redirect('/', '/siparisler');


    Route::get('/doviz', [App\Http\Controllers\CurrencyController::class, 'index'])->name('doviz.index');

    // 🔹 Siparişler
    Route::get('/siparisler', [SiparisController::class, 'index'])->name('siparisler.index');
    Route::get('/istatistikler', [SiparisController::class, 'istatistikler'])->name('istatistikler');
    Route::get('/istatistikler/lider-tablosu', [SiparisController::class, 'liderTablosu'])->name('istatistikler.lider'); // Yeni Lider Tablosu Rotası
    Route::get('/istatistikler/takvim', [SiparisController::class, 'satisTakvimi'])->name('istatistikler.takvim'); // Yeni Satış Takvimi Rotası

    Route::get('/istatistik/harita-veri', [SiparisController::class, 'haritaVerileri'])->name('istatistik.harita');

    Route::post('/siparis/ekstra-ekle', [SiparisController::class, 'ekstraEkle'])->name('siparis.ekstraEkle');
    Route::delete('/siparis/ekstra-sil/{id}', [SiparisController::class, 'ekstraSil'])->name('siparis.ekstraSil');

    // 🔹 Ürün Yönetimi (YENİ EKLENEN KISIM)
    Route::get('/urunler', [UrunController::class, 'index'])->name('urunler.index');
    Route::get('/api/urunler', [UrunController::class, 'getData'])->name('urunler.data'); // DataTables için API rotası
    Route::post('/urunler/bulk-store', [UrunController::class, 'bulkStore'])->name('urunler.bulkStore');
    Route::get('/urunler/{id}/edit', [UrunController::class, 'edit'])->name('urunler.edit');
    Route::put('/urunler/{id}', [UrunController::class, 'update'])->name('urunler.update');
    Route::get('/urunler/create', [App\Http\Controllers\UrunController::class, 'create'])->name('urunler.create');
    Route::post('/urunler', [App\Http\Controllers\UrunController::class, 'store'])->name('urunler.store');
    Route::delete('/urunler/{id}', [UrunController::class, 'destroy'])->name('urunler.destroy');

    // Cache sorununu test etmek için geçici rota
    Route::get('/test-urunler', [UrunController::class, 'indexTest']);

    // Ürün Detay
    Route::get('/urun-detay/{siparis}/{stok}', [UrunController::class, 'detay'])
         ->name('urun.detay');

    // Yeni Şipariş Detay Rotası (Genel)
    Route::get('/urun-detay/{id}', [SiparisController::class, 'show'])->name('siparis.show');

    Route::get('/siparis-guncelle-ve-kar', [UrunController::class, 'siparisGuncelleVeKar'])
         ->name('siparis.guncelleVeKar');

    Route::get('/siparis-senkronize-et', [SiparisController::class, 'sync'])->name('siparis.sync');

    // Manuel sipariş durumunu güncelleme rotası
    Route::post('/siparisler/durum-guncelle/{id}', [SiparisController::class, 'durumGuncelle'])->name('siparis.durumGuncelle');
    Route::post('/siparisler/toggle-usa/{id}', [SiparisController::class, 'toggleUSA'])->name('siparis.toggleUSA');
    Route::post('/siparisler/update-ayar/{id}', [SiparisController::class, 'updateAyarOrani'])->name('siparis.updateAyar');

    // Sipariş Notları (AJAX)
    Route::get('/siparisler/{id}/notlar', [SiparisController::class, 'notlariGetir'])->name('siparis.notlar.getir');
    Route::post('/siparisler/{id}/not-ekle', [SiparisController::class, 'notEkle'])->name('siparis.not.ekle');
    Route::delete('/siparisler/{id}/not-sil/{notId}', [SiparisController::class, 'notSil'])->name('siparis.not.sil');


    // --- Aylık Net Kazanç ---
    Route::get('/aylik-net', [App\Http\Controllers\AylikNetController::class, 'index'])->name('aylik_net.index');
    Route::post('/aylik-net/guncelle', [App\Http\Controllers\AylikNetController::class, 'update'])->name('aylik_net.update');

    // 🔹 Ayarlar

    // 🔹 Kategoriler
    Route::get('/kategoriler', [KategoriController::class, 'index'])->name('kategoriler.index');
    Route::post('/kategoriler/guncelle', [KategoriController::class, 'guncelle'])->name('kategoriler.guncelle');


    // 🔹 Fiyat Hesaplama
    Route::get('/fiyat', [FiyatController::class, 'index'])->name('fiyat.index'); // Sayfayı açar

    // AJAX İşlemleri (Yeni eklenenler)
    Route::get('/fiyat/urun-getir', [FiyatController::class, 'urunGetir'])->name('fiyat.urunGetir'); // Ürün bilgilerini çeker
    Route::post('/fiyat/hesapla', [FiyatController::class, 'hesapla'])->name('fiyat.hesapla');      // Hesaplama yapar
    Route::post('/fiyat/toplu-hesapla', [FiyatController::class, 'topluHesapla'])->name('fiyat.topluHesapla'); // Toplu Hesaplama yapar
    Route::post('/fiyat/toplu-hesapla/export', [FiyatController::class, 'topluHesaplaExport'])->name('fiyat.topluHesaplaExport');
    Route::post('/fiyat/kur-kaydet', [FiyatController::class, 'kurKaydet'])->name('fiyat.kurKaydet');



    // 🔹 2. PAZARYERİ ve AYAR İŞLEMLERİ (Ekle / Güncelle / Sil)
    Route::get('/ayarlar', [AyarController::class, 'index'])->name('ayarlar.index');
    Route::post('/ayarlar', [AyarController::class, 'guncelle'])->name('ayarlar.guncelle');
    Route::get('/ayarlar/gecmis', [AyarController::class, 'gecmis'])->name('ayarlar.gecmis');
    Route::get('/api/ayarlar/{tarih}', [AyarController::class, 'getAyarByDate'])->name('ayarlar.getByDate');

    Route::post('/ayarlar/pazaryerleri', [PazaryeriController::class, 'store'])->name('pazaryeri.store');
    Route::post('/ayarlar/pazaryerleri/{id}', [PazaryeriController::class, 'update'])->name('pazaryeri.update');
    Route::delete('/ayarlar/pazaryerleri/{id}', [PazaryeriController::class, 'destroy'])->name('pazaryeri.destroy');

    // 🔹 Manuel Sipariş Ekleme
    Route::get('/siparisler/manuel-ekle', [ManuelSiparisController::class, 'create'])->name('siparisler.manuel.create');
    Route::post('/siparisler/manuel-ekle', [ManuelSiparisController::class, 'store'])->name('siparisler.manuel.store');


    Route::delete('/siparis-sil/{id}', [SiparisController::class, 'destroy'])->name('manuel.sil');



    Route::get('/welcome', function () {
        return view('welcome');
    });


    Route::get('/admin/siparis-cek', [App\Http\Controllers\SiparisEntegrasyonController::class, 'senkronizeEt'])
        ->name('siparis.cek');

    Route::get('/admin/sync-progress', [App\Http\Controllers\SiparisEntegrasyonController::class, 'getProgress'])
        ->name('sync.progress');

});