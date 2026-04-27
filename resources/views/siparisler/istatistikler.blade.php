@extends('layouts.app')

@section('title', 'İstatistikler ve Raporlar')

@section('content')
<div class="container-fluid py-4 bg-light" style="min-height: 100vh;">
    <div class="container" style="max-width: 1600px;">

        {{-- BAŞLIK VE HARİTA BUTONU --}}
        <div class="mb-5 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
            
            {{-- Sol Taraf: Başlık --}}
            <div>
                <h3 class="fw-bold text-dark mb-1">
                    <i class="fa-solid fa-chart-line text-success me-2"></i>İstatistikler
                </h3>
                <p class="text-muted small mb-0">Satış performansınızı, cironuzu ve kârlılık durumunuzu analiz edin.</p>
            </div>

            {{-- Sağ Taraf: Butonlar --}}
            <div class="d-flex align-items-center gap-3">
                
                {{-- Lider Tablosu Butonu --}}
                <a href="{{ route('istatistikler.lider') }}" class="btn btn-warning shadow-sm rounded-5 px-3 py-2 d-flex align-items-center gap-2 hover-lift text-decoration-none border border-warning-subtle" style="max-width: fit-content;">
                    <div class="bg-white bg-opacity-25 rounded-circle d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-trophy text-white small"></i>
                    </div>
                    <div class="text-start lh-1">
                        <small class="d-block text-white-50 text-uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">En İyiler</small>
                        <span class="fw-bold text-white small">Lider Tablosu</span>
                    </div>
                </a>

                {{-- Coğrafi Harita Butonu --}}
                <a href="{{ route('istatistik.harita') }}" class="btn btn-dark shadow-sm rounded-5 px-3 py-2 d-flex align-items-center gap-2 hover-lift text-decoration-none border border-secondary border-opacity-25" style="max-width: fit-content;">
                    <div class="bg-white bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center position-relative" style="width: 38px; height: 38px;">
                        <i class="fa-solid fa-map-location-dot text-warning small"></i>
                        {{-- Ping Animasyonu --}}
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle" style="transform: scale(0.8);">
                            <span class="visually-hidden">New alerts</span>
                        </span>
                    </div>
                    <div class="text-start lh-1">
                        <small class="d-block text-white-50 text-uppercase fw-bold" style="font-size: 0.6rem; letter-spacing: 0.5px;">Coğrafi</small>
                        <span class="fw-bold text-white small">Yoğunluk Haritası</span>
                    </div>
                    <div class="border-start border-white border-opacity-10 ps-2 ms-1">
                        <i class="fa-solid fa-chevron-right text-white-50 small"></i>
                    </div>
                </a>
            </div>
        </div>

        <div class="row justify-content-between g-4">
            
            {{-- SOL KOLON: MEVCUT İSTATİSTİKLER --}}
            <div class="col-lg-9">
                {{-- BÖLÜM 1: GÜNLÜK ANALİZ --}}
                <div class="card border-0 shadow-sm rounded-5 mb-5 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-4 d-flex flex-column flex-md-row justify-content-between align-items-center gap-3">
                        
                        <h5 class="fw-bold text-dark mb-0 d-flex align-items-center">
                            <i class="fa-regular fa-calendar-check me-2 text-primary"></i> Günlük Performans
                        </h5>

                        {{-- TARİH SEÇİCİ --}}
                        <form method="GET" action="{{ route('istatistikler') }}" class="d-flex align-items-center bg-light rounded-pill p-1 border">
                            {{-- Önceki Gün --}}
                            <a href="{{ route('istatistikler', ['tarih' => \Carbon\Carbon::parse($tarih)->subDay()->toDateString()]) }}" 
                               class="btn btn-sm btn-white rounded-circle shadow-sm text-secondary" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                            
                            {{-- Tarih Input (Seçim yapınca otomatik gönderir) --}}
                            <input type="date" name="tarih" value="{{ $tarih }}" 
                                   class="form-control form-control-sm border-0 bg-transparent text-center fw-bold mx-2" 
                                   style="width: 130px; cursor: pointer;" onchange="this.form.submit()">

                            {{-- Sonraki Gün --}}
                            <a href="{{ route('istatistikler', ['tarih' => \Carbon\Carbon::parse($tarih)->addDay()->toDateString()]) }}" 
                               class="btn btn-sm btn-white rounded-circle shadow-sm text-secondary" style="width: 32px; height: 32px;">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </form>
                    </div>

                    <div class="card-body p-4 bg-light bg-opacity-25">
                        
                        {{-- GÜNLÜK KPI KARTLARI --}}
                        <div class="row g-3">
                            {{-- 1. GÜNLÜK CİRO --}}
                            <div class="col-md-6 col-lg-3">
                                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100 border-start border-4 border-primary">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="text-muted small text-uppercase fw-bold ls-1">Günlük Ciro</span>
                                            <h4 class="fw-bold text-dark mt-1 mb-0">{{ number_format($gunlukCiro ?? 0, 2, ',', '.') }} ₺</h4>
                                        </div>
                                        <div class="icon-box bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-wallet"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 2. GÜNLÜK KÂR --}}
                            @php
                                $gunlukK = $gunlukKar ?? 0;
                                $kRenk = $gunlukK > 0 ? 'success' : ($gunlukK < 0 ? 'danger' : 'secondary');
                            @endphp
                            <div class="col-md-6 col-lg-3">
                                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100 border-start border-4 border-{{ $kRenk }}">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="text-muted small text-uppercase fw-bold ls-1">Net Kâr</span>
                                            <h4 class="fw-bold text-{{ $kRenk }} mt-1 mb-0">{{ number_format($gunlukK, 2, ',', '.') }} ₺</h4>
                                        </div>
                                        <div class="icon-box bg-{{ $kRenk }} bg-opacity-10 text-{{ $kRenk }} rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-chart-line"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 3. ÜRÜN / SİPARİŞ SAYISI --}}
                            <div class="col-md-6 col-lg-3">
                                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100 border-start border-4 border-info">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="text-muted small text-uppercase fw-bold ls-1">Satılan Ürün</span>
                                            <div class="d-flex align-items-baseline gap-2 mt-1">
                                                {{-- Ürün Sayısı (Büyük) --}}
                                                <h4 class="fw-bold text-dark mb-0">{{ $gunlukUrunSayisi ?? 0 }} <span class="fs-6 fw-normal text-muted">Adet</span></h4>
                                                
                                                {{-- Sipariş Sayısı (Küçük) --}}
                                                <small class="text-muted" style="font-size: 0.8rem;">
                                                    / {{ $gunlukSiparisSayisi ?? 0 }} Sipariş
                                                </small>
                                            </div>
                                            @if(($gunlukHediyeSayisi ?? 0) > 0)
                                                <small class="text-success" style="font-size: 0.7rem;"><i class="fa-solid fa-gift me-1"></i>+{{ $gunlukHediyeSayisi }} hediye</small>
                                            @endif
                                        </div>
                                        <div class="icon-box bg-info bg-opacity-10 text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-bag-shopping"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- 4. İPTAL EDİLEN (Kırmızı Vurgu) --}}
                            <div class="col-md-6 col-lg-3">
                                <div class="stat-card bg-white p-3 rounded-3 shadow-sm h-100 border-start border-4 border-danger">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <span class="text-muted small text-uppercase fw-bold ls-1">İptal / İade</span>
                                            <h4 class="fw-bold text-danger mt-1 mb-0">{{ $gunlukIptalSayisi ?? 0 }}</h4>
                                        </div>
                                        <div class="icon-box bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                            <i class="fa-solid fa-ban"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>
                </div>

                {{-- BÖLÜM 2: TARİH ARALIĞI ANALİZİ --}}
                <div class="card border-0 shadow-sm rounded-5">
                    <div class="card-header bg-dark text-white p-4">
                        <h5 class="fw-bold mb-0"><i class="fa-solid fa-calendar-days me-2 text-warning"></i>Dönemsel Analiz</h5>
                        <small class="text-white-50">Belirli bir tarih aralığındaki verileri filtreleyin.</small>
                    </div>

                    <div class="card-body p-4">
                        
                        {{-- FİLTRE FORMU --}}
                        <form method="GET" action="{{ route('istatistikler') }}" class="mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Başlangıç Tarihi</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-muted border-end-0"><i class="fa-regular fa-calendar"></i></span>
                                        <input type="date" name="baslangic" value="{{ $baslangic }}" class="form-control border-start-0 ps-0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small fw-bold text-muted">Bitiş Tarihi</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-white text-muted border-end-0"><i class="fa-regular fa-calendar"></i></span>
                                        <input type="date" name="bitis" value="{{ $bitis }}" class="form-control border-start-0 ps-0">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <button class="btn btn-dark w-100 fw-bold shadow-sm">
                                        <i class="fa-solid fa-filter me-2"></i>ANALİZ ET
                                    </button>
                                </div>
                            </div>

                            {{-- HIZLI SEÇİM BUTONLARI --}}
                            <div class="d-flex flex-wrap gap-2 mt-3">
                                <a href="{{ route('istatistikler', ['baslangic' => now()->subDays(6)->toDateString(), 'bitis' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Son 7 Gün</a>
                                <a href="{{ route('istatistikler', ['baslangic' => now()->subDays(30)->toDateString(), 'bitis' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Son 30 Gün</a>
                                <a href="{{ route('istatistikler', ['baslangic' => now()->startOfMonth()->toDateString(), 'bitis' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Bu Ay</a>
                                <a href="{{ route('istatistikler', ['baslangic' => now()->startOfYear()->toDateString(), 'bitis' => now()->toDateString()]) }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3">Bu Yıl</a>
                            </div>
                        </form>

                        <hr class="text-muted opacity-25 my-4">

                        {{-- ARALIK SONUÇLARI --}}
                        @if($aralikVerisi)
                            @php
                                $akar = $aralikVerisi['kar'] ?? 0;
                                $akarRenk = $akar > 0 ? 'success' : ($akar < 0 ? 'danger' : 'dark');
                            @endphp

                            <div class="row g-4">
                                {{-- SOL TARAF: FİNANSAL ÖZET --}}
                                <div class="col-md-6">
                                    <div class="p-4 rounded-5 bg-light border h-100 position-relative overflow-hidden">
                                        <h6 class="text-secondary fw-bold mb-4 text-uppercase ls-1 small">Finansal Durum</h6>
                                        
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <span class="text-dark fw-medium">Toplam Ciro</span>
                                            <span class="fw-bold fs-5">{{ number_format($aralikCiro, 2, ',', '.') }} ₺</span>
                                        </div>
                                        
                                        <div class="d-flex justify-content-between align-items-center pt-3 border-top border-2">
                                            <span class="text-dark fw-bold">NET KÂR</span>
                                            <span class="fw-bold fs-3 text-{{ $akarRenk }}">
                                                {{ number_format($akar, 2, ',', '.') }} ₺
                                            </span>
                                        </div>
                                        
                                        {{-- Reklam Gideri Eklemesi --}}
                                        <div class="mt-3 pt-2 pb-2 border-top border-light">
                                             <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">Toplam Reklam Gideri</span>
                                                <span class="fw-bold text-danger text-opacity-75 small">
                                                    -{{ number_format($aralikReklamGideri ?? 0, 2, ',', '.') }} ₺
                                                </span>
                                            </div>
                                        </div>

                                        {{-- Vergi Kartı (Sadece Türkiye) --}}
                                        <div class="mt-1 pt-2 pb-4 border-top border-light">
                                             <div class="d-flex justify-content-between align-items-center">
                                                <span class="text-muted small">Ödenmesi Gereken Vergi <small class="opacity-75 ps-1">(Etsy Hariç)</small></span>
                                                <span class="fw-bold text-warning text-dark small">
                                                    {{ number_format($aralikVergi ?? 0, 2, ',', '.') }} ₺
                                                </span>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                                {{-- SAĞ TARAF: OPERASYONEL VERİLER --}}
                                <div class="col-md-6">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="p-3 bg-white border rounded-3 text-center shadow-sm h-100">
                                                <div class="text-muted x-small fw-bold text-uppercase mb-1">Toplam Sipariş</div>
                                                <div class="fs-3 fw-bold text-dark">{{ $aralikVerisi['toplam'] ?? 0 }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-white border rounded-3 text-center shadow-sm h-100">
                                                <div class="text-muted x-small fw-bold text-uppercase mb-1">Satılan Ürün</div>
                                                <div class="fs-3 fw-bold text-primary">{{ $aralikVerisi['urun'] ?? 0 }}</div>
                                                @if(($aralikVerisi['hediye'] ?? 0) > 0)
                                                    <small class="text-success" style="font-size: 0.65rem;"><i class="fa-solid fa-gift me-1"></i>+{{ $aralikVerisi['hediye'] }} hediye</small>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-white border rounded-3 text-center shadow-sm h-100">
                                                <div class="text-muted x-small fw-bold text-uppercase mb-1">Başarılı</div>
                                                <div class="fs-3 fw-bold text-success">{{ $aralikVerisi['aktif'] ?? 0 }}</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-3 bg-white border rounded-3 text-center shadow-sm h-100">
                                                <div class="text-muted x-small fw-bold text-uppercase mb-1">İptal</div>
                                                <div class="fs-3 fw-bold text-danger">{{ $aralikVerisi['iptal'] ?? 0 }}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <div class="opacity-25 mb-3"><i class="fa-solid fa-chart-pie fa-3x"></i></div>
                                <h6 class="text-muted">Veri görüntülemek için tarih aralığı seçiniz.</h6>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- SAĞ KOLON: SON 15 GÜN --}}
            <div class="col-lg-2">
                <div class="card border-0 shadow-sm rounded-5 h-100 sticky-top" style="top: 20px; z-index: 1;">
                    <div class="card-header bg-white border-bottom p-3 d-flex justify-content-between align-items-center">
                         <h6 class="fw-bold mb-0 text-dark small"><i class="fa-solid fa-clock-rotate-left me-2 text-primary"></i>Son 15 Gün</h6>
                         <a href="{{ route('istatistikler.takvim') }}" class="x-small fw-bold text-primary text-decoration-none">
                            Tümünü Gör <i class="fa-solid fa-chevron-right ms-1"></i>
                         </a>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle mb-0" style="font-size: 0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3 text-secondary x-small text-uppercase border-bottom-0">Tarih</th>
                                        <th class="text-secondary x-small text-uppercase border-bottom-0 text-center">Adet</th>
                                        <th class="text-end pe-3 text-secondary x-small text-uppercase border-bottom-0">Kâr</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $maxUrun = $son15Gun->max('ToplamUrun');
                                    @endphp
                                    @foreach($son15Gun as $gun)
                                    @php
                                        $kar = $gun->ToplamKar ?? 0;
                                        $karRenk = $kar > 0 ? 'success' : ($kar < 0 ? 'danger' : 'secondary');
                                        
                                        // Opacity hesabı (En az 0.1, en çok 1.0)
                                        $ratio = $maxUrun > 0 ? ($gun->ToplamUrun / $maxUrun) : 0;
                                        $opacity = round(0.1 + ($ratio * 0.9), 2); // Tabansız 0.1, full 1.0
                                    @endphp
                                    <tr>
                                        <td class="ps-3 fw-medium text-dark py-2">{{ $gun->TarihOzel }}</td>
                                        <td class="text-center fw-bold py-2" style="background-color: rgba(13, 110, 253, {{ $opacity * 0.25 }}); color: #0d6efd;">
                                            {{ $gun->ToplamUrun }}
                                        </td>
                                        <td class="pe-3 text-end fw-bold text-{{ $karRenk }} py-2" style="white-space: nowrap;">{{ number_format($kar, 2, ',', '.') }} ₺</td>
                                    </tr>
                                    @endforeach
                                    {{-- Eğer veri yoksa --}}
                                    @if($son15Gun->isEmpty())
                                    <tr>
                                        <td colspan="2" class="text-center text-muted py-3 x-small">Veri bulunamadı</td>
                                    </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<style>
    .ls-1 { letter-spacing: 1px; }
    .x-small { font-size: 0.7rem; }
    .form-control:focus { box-shadow: none; border-color: #212529; }
    .stat-card { transition: transform 0.2s; }
    .stat-card:hover { transform: translateY(-3px); }
    /* Buton Hover Efekti */
    .hover-lift { transition: transform 0.2s, box-shadow 0.2s; }
    .hover-lift:hover { transform: translateY(-2px); box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important; }
</style>
@endsection