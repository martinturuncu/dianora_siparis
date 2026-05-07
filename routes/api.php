<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SyncController;

// Basit Token Koruması (Middleware ile de yapılabilir ama hızlı çözüm için burada)
// Production'da bu token .env dosyasında saklanmalıdır.
// Middleware group içine alarak her istekte kontrol edebiliriz.

Route::prefix('sync')->group(function () {
    Route::post('/upload', [SyncController::class, 'upload']);
    Route::get('/download', [SyncController::class, 'download']);
    Route::get('/real-grams', [SyncController::class, 'realGramsDownload']);
});
