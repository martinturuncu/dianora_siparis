@extends('layouts.app')

@section('title', 'Fiyat Sihirbazı')

@section('content')
<div class="container-fluid pt-2 pb-4" style="min-height: 100vh;">
    <div class="px-3 px-xxl-5"> 

        {{-- ÜST BAŞLIK ALANI --}}
        <div class="wizard-header mb-4 p-4 rounded-4 position-relative overflow-hidden">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-md-5">
                    <div class="d-flex align-items-center gap-3 mb-2">
                        <div class="wizard-icon-box">
                            <i class="fa-solid fa-wand-magic-sparkles"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold text-white mb-0 ls-tight">Fiyat Sihirbazı</h3>
                            <p class="mb-0 small" style="color: rgba(255,255,255,0.7);">Kuyumcu matematiği ile hassas hesaplama</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-7 text-md-end mt-3 mt-md-0">
                    <div class="d-inline-flex gap-3 align-items-center">
                        <div class="kur-card position-relative">
                            <div class="kur-icon kur-icon-gold">
                                <i class="fa-solid fa-coins"></i>
                            </div>
                            <div class="text-start ps-2 pe-4">
                                <small class="d-block text-uppercase fw-bold" style="font-size: 0.6rem; color: rgba(255,255,255,0.6); letter-spacing: 1px;">Has Altın (TL)</small>
                                <input type="number" step="0.01" id="wizardAltin" class="kur-input" value="{{ $wizardAltin }}">
                            </div>
                            <div id="feedbackAltin" class="position-absolute end-0 me-2" style="display: none; transition: opacity 0.3s;">
                                <i class="fa-solid fa-circle-check" style="color: #6ee7b7;"></i>
                            </div>
                        </div>
                        <div class="kur-card position-relative">
                            <div class="kur-icon kur-icon-dollar">
                                <i class="fa-solid fa-dollar-sign"></i>
                            </div>
                            <div class="text-start ps-2 pe-4">
                                <small class="d-block text-uppercase fw-bold" style="font-size: 0.6rem; color: rgba(255,255,255,0.6); letter-spacing: 1px;">Dolar Kuru</small>
                                <input type="number" step="0.0001" id="wizardDolar" class="kur-input" value="{{ $wizardDolar }}">
                            </div>
                            <div id="feedbackDolar" class="position-absolute end-0 me-2" style="display: none; transition: opacity 0.3s;">
                                <i class="fa-solid fa-circle-check" style="color: #6ee7b7;"></i>
                            </div>
                        </div>
                        <button type="button" class="btn btn-kur-save" id="btnKurKaydet" onclick="saveKurlar()">
                            <i class="fa-solid fa-floppy-disk me-1"></i>Kaydet
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABS --}}
        <ul class="nav wizard-tabs mb-4" id="pills-tab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active fw-semibold" id="pills-single-tab" data-bs-toggle="pill" data-bs-target="#pills-single" type="button" role="tab">
                    <i class="fa-solid fa-calculator me-2"></i>Tekli Hesaplama
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link fw-semibold" id="pills-bulk-tab" data-bs-toggle="pill" data-bs-target="#pills-bulk" type="button" role="tab">
                    <i class="fa-solid fa-list-check me-2"></i>Toplu Hesapla
                </button>
            </li>
        </ul>

        <div class="tab-content" id="pills-tabContent">
            
            {{-- TEKLİ HESAPLAMA TABI --}}
            <div class="tab-pane fade show active" id="pills-single" role="tabpanel">
                <div class="row g-4"> 
                    {{-- SOL KOLON: PARAMETRELER --}}
                    <div class="col-lg-4 col-xl-3">
                        <div class="card border-0 shadow-lg rounded-4 overflow-hidden sticky-top" style="top: 20px; z-index: 100;">
                            <div class="card-header border-0 p-3" style="background: linear-gradient(135deg, #1e293b 0%, #334155 100%);">
                                <h6 class="fw-bold mb-0 d-flex align-items-center text-white">
                                    <i class="fa-solid fa-sliders me-2" style="color: #94a3b8;"></i>Parametreler
                                </h6>
                            </div>
                            <div class="card-body p-4 bg-card">
                                <form id="calcForm" onsubmit="return false;">
                                    @csrf
                                    
                                    {{-- Ürün Kodu --}}
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary x-small text-uppercase ls-1 mb-1">Ürün Kodu</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-card border-0 ps-3 text-primary"><i class="fa-solid fa-barcode"></i></span>
                                            <input type="text" id="urunKodu" class="form-control bg-card border-0 fw-bold text-dark shadow-none" placeholder="Örn: CH001" autocomplete="off">
                                            <button class="btn btn-primary px-3" type="button" id="btnGetir">
                                                <i class="fa-solid fa-search"></i>
                                            </button>
                                        </div>
                                        <div class="form-text mt-1 ps-1" id="urunMsg" style="min-height: 18px; font-size: 0.75rem;"></div>
                                    </div>

                                    {{-- Kategori --}}
                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary x-small text-uppercase ls-1 mb-1">Kategori</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-card border-0 ps-3 text-info"><i class="fa-solid fa-layer-group"></i></span>
                                            <select id="kategoriId" class="form-select bg-card border-0 fw-bold text-dark shadow-none" style="cursor: pointer;">
                                                <option value="" data-kar="0">Seçiniz...</option>
                                                @foreach($kategoriler as $kat)
                                                    <option value="{{ $kat->Id }}" data-kar="{{ $kat->KarOrani }}">
                                                        {{ $kat->KategoriAdi }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>

                                    {{-- Gram --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary x-small text-uppercase ls-1 mb-1">Has Gram</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-card border-0 ps-3 text-warning"><i class="fa-solid fa-scale-balanced"></i></span>
                                            <input type="number" step="0.01" id="gram" class="form-control bg-card border-0 fw-bold fs-5 text-dark shadow-none" placeholder="0.00">
                                            <span class="input-group-text bg-card border-0 text-muted small">gr</span>
                                        </div>
                                    </div>

                                    {{-- Ayar (Milyem) --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary x-small text-uppercase ls-1 mb-1">Ayar (Milyem)</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-card border-0 ps-3 text-warning"><i class="fa-solid fa-gem"></i></span>
                                            <input type="number" step="1" id="txtAyar" class="form-control bg-card border-0 fw-bold fs-5 text-dark shadow-none" value="585" placeholder="585">
                                        </div>
                                        <div class="form-text x-small text-end mt-1">Standart: 585</div>
                                    </div>

                                    {{-- Kâr Oranı --}}
                                    <div class="p-3 bg-body bg-opacity-50 rounded-3 border border-dashed mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <label class="form-label fw-bold text-dark mb-0 x-small text-uppercase">Net Kâr</label>
                                            <span class="badge bg-card text-primary border shadow-sm" id="lblKarOrani">%0</span>
                                        </div>
                                        <input type="range" class="form-range custom-range" min="0" max="200" step="5" id="rangeKar" value="0">
                                    </div>

                                    {{-- İndirim Oranı --}}
                                    <div class="mb-4">
                                        <label class="form-label fw-bold text-secondary x-small text-uppercase ls-1 mb-1">İndirim Oranı (%)</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-card border-0 ps-3 text-danger"><i class="fa-solid fa-percent"></i></span>
                                            <input type="number" step="1" min="0" max="100" id="indirim" class="form-control bg-card border-0 fw-bold fs-5 text-dark shadow-none" placeholder="0">
                                        </div>
                                    </div>

                                    <button type="button" class="btn w-100 py-3 rounded-3 fw-bold shadow-lg btn-hover-effect btn-wizard-calc" onclick="hesapla()">
                                        HESAPLA <i class="fa-solid fa-arrow-right ms-2"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    {{-- SAĞ KOLON: SONUÇLAR --}}
                    <div class="col-lg-8 col-xl-9">
                        
                        {{-- Yükleniyor --}}
                        <div id="loadingState" class="d-none h-100 d-flex flex-column justify-content-center align-items-center py-5" style="min-height: 300px;">
                            <div class="spinner-border text-primary mb-4" style="width: 3rem; height: 3rem;" role="status"></div>
                            <h5 class="text-muted fw-normal">Hesaplamalar yapılıyor...</h5>
                        </div>

                        {{-- Boş Durum --}}
                        <div id="emptyState" class="text-center py-5 bg-card rounded-4 shadow-sm border border-dashed h-100 d-flex flex-column justify-content-center align-items-center" style="min-height: 300px;">
                            <div class="bg-body p-4 rounded-circle mb-4">
                                <i class="fa-solid fa-calculator fa-3x text-secondary opacity-50"></i>
                            </div>
                            <h3 class="fw-bold mb-2" style="color: var(--text-main);">Hazır</h3>
                            <p class="text-muted small">Lütfen soldan parametreleri giriniz.</p>
                        </div>

                        {{-- Sonuç Grid --}}
                        <div id="resultState" class="d-none">
                            <div class="row g-4" id="resultsContainer">
                                <!-- JavaScript ile doldurulacak -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- TOPLU HESAPLAMA TABI --}}
            <div class="tab-pane fade" id="pills-bulk" role="tabpanel">
                <div class="row">
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                            <div class="card-header bg-dark text-white p-3 border-0">
                                <h6 class="fw-bold mb-0"><i class="fa-solid fa-barcode me-2 text-warning"></i>Ürün Kodları</h6>
                            </div>
                            <div class="card-body p-4 bg-card">
                                <form action="{{ route('fiyat.topluHesaplaExport') }}" method="POST">
                                    @csrf
                                    <p class="text-muted small mb-2">
                                        Her satıra bir ürün kodu gelecek şekilde yapıştırınız.
                                    </p>
                                    <textarea name="kodlar" id="bulkCodes" class="form-control bg-body border-0 shadow-inner mb-3" rows="12" placeholder="CH001&#10;YZK002&#10;KLY045..."></textarea>
                                    
                                    <input type="hidden" name="altin_fiyat" id="bulkAltinHdn">
                                    <input type="hidden" name="dolar_kuru" id="bulkDolarHdn">

                                    <div class="mb-3">
                                        <label class="form-label fw-bold text-secondary x-small text-uppercase ls-1 mb-1">İndirim Oranı (%)</label>
                                        <div class="input-group shadow-sm rounded-3 overflow-hidden">
                                            <span class="input-group-text bg-body border-0 ps-3 text-danger"><i class="fa-solid fa-percent"></i></span>
                                            <input type="number" step="1" min="0" max="100" id="bulkIndirim" name="indirim_orani" class="form-control bg-body border-0 fw-bold text-dark shadow-none" placeholder="0" value="0">
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-warning flex-grow-1 py-3 rounded-3 fw-bold shadow-lg btn-hover-effect text-dark" onclick="topluHesapla()">
                                            <i class="fa-solid fa-calculator me-2"></i>TOPLU HESAPLA
                                        </button>
                                        <button type="submit" class="btn btn-success py-3 rounded-3 fw-bold shadow-lg btn-hover-effect">
                                            <i class="fa-solid fa-file-excel me-2"></i>Excel
                                        </button>
                                    </div>
                                </form>

                            </div>
                        </div>
                    </div>
                    
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-lg rounded-4 overflow-hidden h-100">
                             <div class="card-header bg-card p-3 border-bottom d-flex justify-content-between align-items-center">
                                <h6 class="fw-bold mb-0 text-dark">Sonuçlar</h6>
                                <button class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('bulkResultsBody').innerHTML = ''">Temizle</button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-body">
                                        <tr>
                                            <th class="ps-4">Ürün Kodu</th>
                                            <th>Kategori</th>
                                            <th>Gram</th>
                                            <th class="text-primary">İndirimsiz Fiyat</th>
                                            <th class="text-primary">İndirimsiz Kâr</th>
                                            <th class="text-danger">İndirimli Fiyat</th>
                                            <th class="text-danger">İndirimli Kâr</th>
                                            <th>Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody id="bulkResultsBody" class="border-top-0">
                                        {{-- JS ile dolacak --}}
                                    </tbody>
                                </table>
                            </div>
                             {{-- Yükleniyor (Bulk) --}}
                             <div id="bulkLoading" class="d-none py-5 text-center">
                                <div class="spinner-border text-warning mb-3" role="status"></div>
                                <div class="text-muted">Toplu hesaplama yapılıyor...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>



{{-- CUSTOM CSS --}}
<style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
    
    body { font-family: 'Inter', sans-serif; }

    /* ========== HEADER BANNER ========== */
    .wizard-header {
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 50%, #0e4429 100%);
        box-shadow: 0 8px 32px rgba(15, 23, 42, 0.25);
    }
    .wizard-header::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .wizard-icon-box {
        width: 48px; height: 48px;
        background: rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255,255,255,0.15);
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.2rem;
        color: #fbbf24;
    }

    /* ========== KUR KARTLARI ========== */
    .kur-card {
        display: flex;
        align-items: center;
        background: rgba(255,255,255,0.08);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255,255,255,0.12);
        padding: 10px 16px;
        border-radius: 14px;
        transition: all 0.25s ease;
    }
    .kur-card:hover {
        background: rgba(255,255,255,0.12);
        border-color: rgba(255,255,255,0.2);
    }
    .kur-icon {
        width: 36px; height: 36px;
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 0.85rem;
    }
    .kur-icon-gold {
        background: rgba(251, 191, 36, 0.15);
        color: #fbbf24;
    }
    .kur-icon-dollar {
        background: rgba(52, 211, 153, 0.15);
        color: #34d399;
    }
    .kur-input {
        font-weight: 700;
        color: #fff;
        border: none;
        background: transparent;
        padding: 0;
        outline: none;
        width: 100px;
        font-size: 1.1rem;
        font-family: 'Inter', sans-serif;
    }
    .kur-input:focus { color: #fbbf24; }
    /* Chrome - number input okları kaldır */
    .kur-input::-webkit-outer-spin-button,
    .kur-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }
    .kur-input[type=number] { -moz-appearance: textfield; }

    /* ========== KAYDET BUTONU ========== */
    .btn-kur-save {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: #fff;
        border-radius: 12px;
        padding: 10px 18px;
        font-size: 0.82rem;
        font-weight: 600;
        transition: all 0.25s ease;
        white-space: nowrap;
    }
    .btn-kur-save:hover {
        background: rgba(99, 102, 241, 0.5);
        border-color: rgba(99, 102, 241, 0.6);
        color: #fff;
        transform: translateY(-1px);
    }
    .btn-kur-save.saved {
        background: rgba(52, 211, 153, 0.25);
        border-color: rgba(52, 211, 153, 0.4);
        color: #6ee7b7;
    }

    /* ========== TABS ========== */
    .wizard-tabs {
        display: flex;
        gap: 8px;
        padding: 4px;
        background: var(--bg-card);
        border-radius: 14px;
        border: 1px solid var(--border-color);
        display: inline-flex;
    }
    .wizard-tabs .nav-link {
        color: var(--text-muted) !important;
        border-radius: 10px;
        padding: 10px 20px;
        font-size: 0.88rem;
        transition: all 0.25s ease;
        border: none;
    }
    .wizard-tabs .nav-link:hover:not(.active) {
        background: rgba(99, 102, 241, 0.06);
        color: var(--text-main) !important;
    }
    .wizard-tabs .nav-link.active {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%) !important;
        color: #fff !important;
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }
    body.dark-mode .wizard-tabs {
        background: #1e1e1e;
        border-color: #333;
    }

    /* ========== HESAPLA BUTTON ========== */
    .btn-wizard-calc {
        background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        color: #fff;
        border: none;
        letter-spacing: 0.5px;
    }
    .btn-wizard-calc:hover {
        background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        color: #fff;
    }

    .ls-tight { letter-spacing: -0.5px; }
    .ls-1 { letter-spacing: 1px; }
    .x-small { font-size: 0.75rem; }

    /* Form Input İyileştirmeleri */
    .input-group:focus-within {
        box-shadow: 0 0 0 2px rgba(99, 102, 241, 0.25) !important;
    }
    
    /* Range Slider */
    .custom-range::-webkit-slider-thumb {
        background: #6366f1;
        width: 16px; height: 16px;
        margin-top: -6px;
        box-shadow: 0 2px 6px rgba(99, 102, 241, 0.4);
    }
    .custom-range::-webkit-slider-runnable-track {
        height: 4px; background: #e2e8f0; border-radius: 2px;
    }
    body.dark-mode .custom-range::-webkit-slider-runnable-track {
        background: #374151;
    }

    /* Buton Efekti */
    .btn-hover-effect { transition: all 0.2s ease; }
    .btn-hover-effect:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important; }

    /* --- SONUÇ KARTLARI --- */
    .result-card {
        background: var(--bg-card);
        border-radius: 1rem;
        position: relative;
        transition: all 0.3s ease;
        border: 1px solid var(--border-color);
        overflow: hidden;
    }
    .result-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 30px rgba(0,0,0,0.08);
        border-color: transparent;
    }

    /* Renkli Üst Çizgi */
    .card-top-line {
        height: 4px;
        width: 100%;
        position: absolute;
        top: 0; left: 0;
        filter: brightness(1.2);
    }
    
    body.dark-mode .card-top-line {
        filter: saturate(1.5) brightness(1.5);
    }

    /* Kart İçeriği */
    .card-content { padding: 1.5rem; }

    /* Fiyat Alanı */
    .price-area {
        margin: 0.5rem 0 1rem 0;
        text-align: left;
    }
    .price-label {
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #94a3b8;
        font-weight: 700;
        margin-bottom: 0.2rem;
    }
    .main-price {
        font-size: 2rem;
        font-weight: 800;
        line-height: 1;
        letter-spacing: -0.5px;
        color: var(--text-main);
    }
    
    body.dark-mode .main-price {
        color: #fff;
    }
    
    /* Alt Bilgi Alanı */
    .card-footer-info {
        margin-top: auto;
        padding-top: 1rem;
        border-top: 1px dashed #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
    }
    body.dark-mode .card-footer-info {
        border-top-color: #374151;
    }

    /* Detay Overlay (Hover) */
    .details-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        background: var(--bg-card);
        backdrop-filter: blur(5px);
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        opacity: 0;
        visibility: hidden;
        transition: all 0.25s ease;
        z-index: 10;
    }
    .result-card:hover .details-overlay {
        opacity: 1;
        visibility: visible;
    }

    .detail-item {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0.5rem;
        font-size: 0.85rem;
        color: #6c757d;
    }
    .detail-item span:last-child {
        font-weight: 600;
        color: var(--text-main);
    }

    body.dark-mode .detail-item span:first-child {
        color: var(--text-muted-light);
    }

    body.dark-mode .result-card {
        background: #1e1e1e;
        border-color: #2d2d2d;
    }

    body.dark-mode .details-overlay {
        background: rgba(30, 30, 30, 0.98);
    }
</style>

{{-- JAVASCRIPT --}}
<script>
    // Değişkenler
    const inpUrunKodu = document.getElementById('urunKodu');
    const inpGram = document.getElementById('gram');
    const inpAyar = document.getElementById('txtAyar');
    const selKategori = document.getElementById('kategoriId');
    const rangeKar = document.getElementById('rangeKar');
    const inpIndirim = document.getElementById('indirim');
    const lblKar = document.getElementById('lblKarOrani');

    const wizardAltin = document.getElementById('wizardAltin');
    const wizardDolar = document.getElementById('wizardDolar');
    const bulkAltinHdn = document.getElementById('bulkAltinHdn');
    const bulkDolarHdn = document.getElementById('bulkDolarHdn');
    
    // Sayfa açılışında hidden inputları doldur
    if(bulkAltinHdn) bulkAltinHdn.value = wizardAltin.value;
    if(bulkDolarHdn) bulkDolarHdn.value = wizardDolar.value;

    const loadingState = document.getElementById('loadingState');
    const emptyState = document.getElementById('emptyState');
    const resultState = document.getElementById('resultState');
    const resultsContainer = document.getElementById('resultsContainer');
    const csrfToken = document.querySelector('input[name="_token"]').value;
    const badgeConfig = @json($badges ?? []);

    // Event Listeners
    inpUrunKodu.addEventListener('keypress', function (e) { if (e.key === 'Enter') fetchUrun(); });
    document.getElementById('btnGetir').addEventListener('click', fetchUrun);
    
    wizardAltin.addEventListener('input', function() { if(bulkAltinHdn) bulkAltinHdn.value = this.value; });
    wizardDolar.addEventListener('input', function() { if(bulkDolarHdn) bulkDolarHdn.value = this.value; });

    selKategori.addEventListener('change', function() {
        const opt = this.options[this.selectedIndex];
        const kar = opt.getAttribute('data-kar') || 0;
        
        // Sadece kategori değiştiğinde varsayılan kar oranını yükle
        // Kullanıcı manuel değiştirmişse bile kategori değişince resetlenir.
        rangeKar.value = kar;
        lblKar.innerText = '%' + kar;
        
        if(inpGram.value > 0) hesapla();
    });

    // Kurs Değişimi Feedback
    function showFeedback() {
        const fA = document.getElementById('feedbackAltin');
        const fD = document.getElementById('feedbackDolar');
        if(!fA || !fD) return;
        fA.style.display = 'block';
        fD.style.display = 'block';
        setTimeout(() => {
            fA.style.display = 'none';
            fD.style.display = 'none';
        }, 1500);
    }

    // Kurları Kaydet (Bağımsız)
    async function saveKurlar() {
        const btn = document.getElementById('btnKurKaydet');
        const origHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Kaydediliyor...';
        btn.disabled = true;

        try {
            const fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('altin_fiyat', wizardAltin.value);
            fd.append('dolar_kuru', wizardDolar.value);

            const res = await fetch("{{ route('fiyat.kurKaydet') }}", { method: 'POST', body: fd });
            const data = await res.json();

            if(data.status) {
                btn.innerHTML = '<i class="fa-solid fa-check me-1"></i>Kaydedildi!';
                btn.classList.add('saved');
                showFeedback();
                setTimeout(() => {
                    btn.innerHTML = origHtml;
                    btn.classList.remove('saved');
                    btn.disabled = false;
                }, 2000);
            } else {
                btn.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>Hata';
                setTimeout(() => {
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                }, 2000);
            }
        } catch(err) {
            console.error(err);
            btn.innerHTML = '<i class="fa-solid fa-xmark me-1"></i>Hata';
            setTimeout(() => {
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }, 2000);
        }
    }

    rangeKar.addEventListener('input', function() {
        lblKar.innerText = '%' + this.value;
    });

    rangeKar.addEventListener('change', function() {
        if(inpGram.value > 0) hesapla();
    });

    // Ayar değişirse de hesapla
    inpAyar.addEventListener('change', function() {
        if(inpGram.value > 0) hesapla();
    });

    // 1. ÜRÜN GETİR
    async function fetchUrun() {
        const kod = inpUrunKodu.value.trim();
        const msgDiv = document.getElementById('urunMsg');
        if(!kod) return;
        
        msgDiv.innerHTML = '<span class="text-primary"><i class="fa-solid fa-spinner fa-spin"></i> Aranıyor...</span>';
        
        try {
            const res = await fetch("{{ route('fiyat.urunGetir') }}?urun_kodu=" + kod);
            const data = await res.json();
            
            if(data.status) {
                inpGram.value = data.urun.Gram;
                selKategori.value = data.urun.KategoriId;
                selKategori.dispatchEvent(new Event('change'));
                msgDiv.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check"></i> Bulundu.</span>';
                hesapla();
            } else {
                msgDiv.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-times"></i> Yok.</span>';
                inpGram.value = '';
            }
        } catch (err) { 
            console.error(err);
            msgDiv.innerHTML = '<span class="text-danger">Hata.</span>'; 
        }
    }

    // 2. HESAPLA
    async function hesapla() {
        const gram = parseFloat(inpGram.value);
        const katId = selKategori.value;
        
        if(!gram || gram <= 0) {
            alert("Lütfen geçerli bir gram değeri giriniz.");
            return;
        }

        emptyState.classList.add('d-none');
        resultState.classList.add('d-none');
        loadingState.classList.remove('d-none');

        try {
            const fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('gram', gram);
            fd.append('kategori_id', katId);
            fd.append('manuel_kar_orani', rangeKar.value);
            fd.append('indirim_orani', inpIndirim.value || 0);
            fd.append('ayar', inpAyar.value || 585); // Yeni parametre
            fd.append('altin_fiyat', wizardAltin.value);
            fd.append('dolar_kuru', wizardDolar.value);

            const res = await fetch("{{ route('fiyat.hesapla') }}", { method: 'POST', body: fd });
            const data = await res.json();

            if(data.status) {
                // Gelen veriyi JS tarafında sıralıyoruz (TL -> USD -> ELDEN)
                // Bu sayede Etsy kartları her zaman yan yana gelir.
                let sortedResults = data.sonuclar.sort((a, b) => {
                    // Sıralama önceliği: TL (tur!='USD' ve id!=99) -> USD (tur=='USD') -> Elden (id==99)
                    const getRank = (item) => {
                        if (item.id == 99) return 3; // Elden en son
                        if (item.tur === 'USD') return 2; // Dolar ortada
                        return 1; // TL en başa
                    };
                    return getRank(a) - getRank(b);
                });

                renderResults(sortedResults);
                
                if(data.meta) {
                    showFeedback();
                }
            } else {
                alert(data.message);
                emptyState.classList.remove('d-none');
            }
        } catch (err) {
            console.error(err);
            alert('Bir hata oluştu.');
            emptyState.classList.remove('d-none');
        } finally {
            if(loadingState.classList.contains('d-none')) return;
            loadingState.classList.add('d-none');
            resultState.classList.remove('d-none');
        }
    }

    // 3. SONUÇLARI ÇİZ
    function renderResults(sonuclar) {
        let html = '';
        sonuclar.forEach(item => {
            const cfg = badgeConfig[item.id];
            if(!cfg) return;

            const sembol = item.tur === 'USD' ? '$' : '₺';
            
            // İKON MANTIĞI (Görsel yoksa FontAwesome)
            let logoHtml = '';
            if(cfg.image) {
                const darkClass = cfg.image_dark ? 'd-dark-none' : '';
                logoHtml = `<img src="${cfg.image}" class="${darkClass}" style="height: 24px; width: auto; object-fit: contain;">`;
                if(cfg.image_dark) {
                    logoHtml += `<img src="${cfg.image_dark}" class="d-dark-block" style="height: 24px; width: auto; object-fit: contain;">`;
                }
            } else {
                let iconPrefix = 'fa-solid';
                if(cfg.icon.includes('etsy') || cfg.icon.includes('n11') || cfg.icon.includes('instagram')) {
                    iconPrefix = 'fa-brands';
                }
                logoHtml = `<i class="${iconPrefix} ${cfg.icon} fs-4" style="color:${cfg.bg}"></i>`;
            }

            // Etsy Başlık Düzenlemesi
            let displayTitle = item.ad || cfg.text;
            if(displayTitle === 'Etsy (USA)') displayTitle = 'Etsy (Amerika/USA)';
            if(displayTitle === 'Etsy (Diğer)') displayTitle = 'Etsy (EU/Diğer)';

            // Liste Fiyatı (%10) Gösterimi
            let oldPriceHtml = '';
            
            // İndirim Varsa Eskiyi Çiz, Yeniyi Göster
            // Logic: item.old_price varsa ve satis_fiyati'ndan farklıysa indirim var demektir.
            if(item.old_price && item.old_price !== item.satis_fiyati) {
                 // İndirim Oranı Hesaplama (Kabaca)
                 const oldP = parseFloat(item.old_price.replace(',','.'));
                 const newP = parseFloat(item.satis_fiyati.replace(',','.'));
                 let discountRate = 0;
                 if(oldP > 0) discountRate = Math.round(((oldP - newP) / oldP) * 100);

                 oldPriceHtml = `
                    <div class="mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-muted text-decoration-line-through small fw-bold">${item.old_price} ${sembol}</span>
                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25">
                                <i class="fa-solid fa-arrow-down me-1"></i>%${discountRate}
                            </span>
                        </div>
                    </div>`;
            } 
            else if(item.buffer_fiyati) {
                // İndirim yoksa standart Buffer Fiyatını göster (Eski logic)
                oldPriceHtml = `
                <div class="mb-2">
                    <div class="d-inline-flex align-items-center border rounded px-2 py-1 bg-light bg-opacity-75" title="Pazaryerine Girilecek Liste Fiyatı">
                        <span class="badge bg-dark bg-opacity-10 text-dark me-2 px-1" style="font-size: 0.65rem;">%10</span>
                        <span class="fw-bold text-dark" style="font-size: 0.9rem;">${item.buffer_fiyati} ${sembol}</span>
                    </div>
                </div>`;
            }
            
            // Ekstra Notlar ve TL Karşılıkları (FONT BÜYÜTÜLDÜ)
            let extraNote = '';
            let costNote = '';
            let netNote = '';

            if(item.id == 99 && item.ekstra_fiyat) {
                extraNote = `<div class="mt-1"><span class="badge bg-success bg-opacity-10 text-success px-2 py-1">Nakit: ${item.ekstra_fiyat}</span></div>`;
            } else if (item.tur === 'USD') {
                // Kur Bilgisi
                extraNote = `<div class="mt-1 text-muted x-small">(${item.kur_karsiligi})</div>`;
                
                // TL Karşılıklarını Ekle (Varsa) - Font büyütüldü (0.75rem)
                if(item.maliyet_tl) {
                    costNote = `<div class="text-muted fw-light x-small mt-1" style="font-size: 0.75rem;">≈ ${item.maliyet_tl} ₺</div>`;
                }
                if(item.net_kar_tl) {
                    netNote = `<div class="text-success fw-light x-small mt-1" style="font-size: 0.75rem; opacity: 0.85;">≈ ${item.net_kar_tl} ₺</div>`;
                }
            }

            // Kargo Satırı
            let kargoHtml = '';
            if(item.detay_kargo && item.detay_kargo !== '0.00') {
                kargoHtml = `
                <div class="detail-item">
                    <span><i class="fa-solid fa-truck-fast me-2 text-info"></i>Kargo</span>
                    <span class="text-danger">${item.detay_kargo} ${sembol}</span>
                </div>`;
            }

            html += `
            <div class="col-sm-6 col-md-6 col-xl-4">
                <div class="result-card h-100 shadow-sm">
                    <div class="card-top-line" style="background-color: ${cfg.bg}"></div>
                    
                    <div class="card-content d-flex flex-column h-100">
                        <!-- HEADER -->
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded-circle bg-light d-flex align-items-center justify-content-center border" style="width:40px; height:40px;">
                                    ${logoHtml}
                                </div>
                                <div>
                                    <h6 class="fw-bold text-dark mb-0" style="font-size: 0.95rem;">${displayTitle}</h6>
                                    <span class="text-muted x-small text-uppercase fw-bold" style="font-size: 0.65rem;">${item.tur} Bazlı</span>
                                </div>
                            </div>
                        </div>

                        <!-- PRICE BLOCK -->
                        <div class="price-area">
                            ${oldPriceHtml}
                            <div class="price-label mt-2">Satış Fiyatı</div>
                            <div class="main-price">${item.satis_fiyati} <span class="fs-5 fw-light text-muted">${sembol}</span></div>
                            ${extraNote}
                        </div>

                        <!-- FOOTER INFO -->
                        <div class="card-footer-info">
                            <div>
                                <div class="text-muted x-small fw-bold text-uppercase" style="font-size: 0.65rem;">Maliyet</div>
                                <div class="fw-bold text-secondary small">${item.detay_maliyet} ${sembol}</div>
                                ${costNote}
                            </div>
                            <div class="text-end">
                                <div class="text-muted x-small fw-bold text-uppercase" style="font-size: 0.65rem;">Net Kazanç</div>
                                ${item.old_net_kar && item.old_net_kar != item.net_kar ? 
                                    `<div class="text-decoration-line-through text-muted x-small" style="font-size: 0.7rem;">${item.old_net_kar}</div>` : ''}
                                <div class="fw-bold fs-6 text-success">${item.net_kar}</div>
                                ${netNote}
                            </div>
                        </div>
                    </div>

                    <!-- HOVER DETAILS -->
                    <div class="details-overlay">
                        <h6 class="fw-bold text-dark mb-3 pb-2 border-bottom small">
                            <i class="fa-solid fa-chart-pie me-2 text-primary"></i>Satış Fiyatı Dağılımı
                        </h6>
                        
                        <div class="detail-item">
                            <span><i class="fa-regular fa-gem me-2 text-warning"></i>Has Altın</span>
                            <span>${item.detay_altin} ${sembol}</span>
                        </div>
                        <div class="detail-item">
                            <span><i class="fa-solid fa-wrench me-2 text-secondary"></i>İşçilik & Gider</span>
                            <span>${item.detay_gider} ${sembol}</span>
                        </div>
                        <div class="detail-item">
                            <span><i class="fa-solid fa-percent me-2 text-danger"></i>Komisyon</span>
                            <span class="text-danger">${item.detay_komisyon} ${sembol}</span>
                        </div>
                        <div class="detail-item">
                            <span><i class="fa-solid fa-building-columns me-2 text-danger"></i>KDV / Vergi</span>
                            <span class="text-danger">${item.detay_vergi} ${sembol}</span>
                        </div>
                        ${kargoHtml}
                        
                        <div class="mt-auto pt-2 border-top d-flex justify-content-between align-items-center">
                            <span class="fw-bold text-dark small">NET KÂR</span>
                            <span class="fw-bold fs-5 text-success bg-success bg-opacity-10 px-2 rounded">${item.net_kar}</span>
                        </div>
                        <div class="text-center mt-2 pt-1 border-top border-light">
                           <small class="text-muted x-small" style="font-size:0.65rem;">Toplam = Satış Fiyatı</small>
                        </div>
                    </div>

                </div>
            </div>`;
        });
        resultsContainer.innerHTML = html;
    }

    // 4. TOPLU HESAPLA ve TABLO ÇİZİMİ
    async function topluHesapla() {
        const codes = document.getElementById('bulkCodes').value;
        const resultBody = document.getElementById('bulkResultsBody');
        const bulkLoading = document.getElementById('bulkLoading');
        
        if(!codes.trim()) {
            alert('Lütfen en az bir ürün kodu giriniz.');
            return;
        }

        resultBody.innerHTML = '';
        bulkLoading.classList.remove('d-none');

        try {
            const fd = new FormData();
            fd.append('_token', csrfToken);
            fd.append('kodlar', codes);
            fd.append('altin_fiyat', wizardAltin.value);
            fd.append('dolar_kuru', wizardDolar.value);
            fd.append('indirim_orani', document.getElementById('bulkIndirim').value || 0);

            const res = await fetch("{{ route('fiyat.topluHesapla') }}", { method: 'POST', body: fd });
            const data = await res.json();

            if(data.status) {
                let rows = '';
                data.sonuclar.forEach(item => {
                    let durumBadge = '';
                    let fiyatHtml = '';
                    let karHtml = '';
                    let indFiyatHtml = '';
                    let indKarHtml = '';
                    
                    if(item.durum === 'ok') {
                        durumBadge = '<span class="badge bg-success bg-opacity-10 text-success">Başarılı</span>';
                        fiyatHtml = `<span class="fw-bold text-dark">${item.fiyat}</span>`;
                        karHtml = `<span class="fw-bold text-success">${item.net_kar}</span>`;
                        
                        if(item.indirim_orani > 0) {
                            indFiyatHtml = `<span class="fw-bold text-danger">${item.indirimli_fiyat}</span>`;
                            const karValue = parseFloat(item.indirimli_net_kar.replace(/\./g, '').replace(',', '.'));
                            indKarHtml = `<span class="fw-bold ${karValue >= 0 ? 'text-success' : 'text-danger'}">${item.indirimli_net_kar}</span>`;
                        } else {
                            indFiyatHtml = '<span class="text-muted">—</span>';
                            indKarHtml = '<span class="text-muted">—</span>';
                        }
                    } else {
                        durumBadge = `<span class="badge bg-danger bg-opacity-10 text-danger">${item.mesaj}</span>`;
                        fiyatHtml = '<span class="text-muted">-</span>';
                        karHtml = '<span class="text-muted">-</span>';
                        indFiyatHtml = '<span class="text-muted">-</span>';
                        indKarHtml = '<span class="text-muted">-</span>';
                    }

                    rows += `
                    <tr>
                        <td class="ps-4 fw-bold text-secondary">${item.kod}</td>
                        <td class="text-muted small">${item.kategori_adi || '-'}</td>
                        <td>${item.gram ? item.gram + ' gr' : '-'}</td>
                        <td>${fiyatHtml}</td>
                        <td>${karHtml}</td>
                        <td>${indFiyatHtml}</td>
                        <td>${indKarHtml}</td>
                        <td>${durumBadge}</td>
                    </tr>`;
                });
                resultBody.innerHTML = rows;

                if(data.meta) {
                    showFeedback();
                }
            } else {
                alert(data.message || 'Hata oluştu.');
            }

        } catch (err) {
            console.error(err);
            alert('Sunucu hatası oluştu.');
        } finally {
            bulkLoading.classList.add('d-none');
        }
    }
</script>
@endsection