@extends('layouts.app')

@section('title', 'Lider Tabloları')

@section('content')
<div class="container-fluid py-4 bg-light" style="min-height: 100vh;">
    <div class="container-xl">
        
        {{-- HEADER --}}
        <div class="d-flex align-items-center justify-content-between mb-5">
            <div class="d-flex align-items-center">
                <div>
                    <h3 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-trophy text-warning me-2"></i>Lider Tabloları
                    </h3>
                    <p class="text-muted small mb-0">En iyi müşteriler ve en çok satan ürünler.</p>
                </div>
                
                <button type="button" class="btn btn-warning rounded-pill px-4 shadow-sm fw-medium ms-4" data-bs-toggle="modal" data-bs-target="#retentionModal">
                    <i class="fa-solid fa-chart-pie me-2"></i>Müşteri Analizi
                </button>
            </div>
            
            <a href="{{ route('istatistikler') }}" class="btn btn-outline-secondary rounded-pill px-4 shadow-sm fw-medium">
                <i class="fa-solid fa-arrow-left me-2"></i>İstatistiklere Dön
            </a>
        </div>

        <div class="row g-4">
            
            {{-- 1. EN ÇOK SİPARİŞ VERENLER --}}
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-primary bg-opacity-10 text-primary rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="fa-solid fa-user-check fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-0">Sadık Müşteriler</h5>
                        <small class="text-muted">En çok sipariş verenler</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($topMusterilerSiparis as $index => $m)
                                <div class="list-group-item px-4 py-3 border-light d-flex align-items-center gap-3 {{ $index < 3 ? 'bg-light bg-opacity-50' : '' }}">
                                    <div class="fw-bold fs-5 text-secondary" style="width: 25px;">#{{ $index + 1 }}</div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="{{ $m->AdiSoyadi }}">{{ $m->AdiSoyadi }}</div>
                                        <div class="text-muted x-small phone-mask">{{ Str::mask($m->Telefon, '*', 3, 4) }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary rounded-pill px-3">{{ $m->SiparisSayisi }} Sipariş</span>
                                    </div>
                                    @if($index == 0) <i class="fa-solid fa-crown text-warning fa-lg ms-2"></i> @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- 2. EN ÇOK ÜRÜN ALANLAR --}}
            <div class="col-lg-4">
                 <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-success bg-opacity-10 text-success rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="fa-solid fa-boxes-stacked fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-0">Toptancı Müşteriler</h5>
                        <small class="text-muted">En çok ürün adedi alanlar</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($topMusterilerUrun as $index => $m)
                                <div class="list-group-item px-4 py-3 border-light d-flex align-items-center gap-3 {{ $index < 3 ? 'bg-light bg-opacity-50' : '' }}">
                                    <div class="fw-bold fs-5 text-secondary" style="width: 25px;">#{{ $index + 1 }}</div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold text-dark text-truncate" style="max-width: 200px;" title="{{ $m->AdiSoyadi }}">{{ $m->AdiSoyadi }}</div>
                                        <div class="text-muted x-small phone-mask">{{ Str::mask($m->Telefon, '*', 3, 4) }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-success rounded-pill px-3">{{ $m->ToplamUrun }} Adet</span>
                                    </div>
                                    @if($index == 0) <i class="fa-solid fa-crown text-warning fa-lg ms-2"></i> @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- 3. BEST SELLER ÜRÜNLER --}}
            <div class="col-lg-4">
                 <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                    <div class="card-header bg-white border-bottom p-4 text-center">
                        <div class="d-inline-flex align-items-center justify-content-center bg-danger bg-opacity-10 text-danger rounded-circle mb-3" style="width: 60px; height: 60px;">
                            <i class="fa-solid fa-fire fs-4"></i>
                        </div>
                        <h5 class="fw-bold text-dark mb-0">Çok Satanlar</h5>
                        <small class="text-muted">En popüler ürünler</small>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            @foreach($topUrunler as $index => $u)
                                <div class="list-group-item px-4 py-3 border-light d-flex align-items-center gap-3 {{ $index < 3 ? 'bg-light bg-opacity-50' : '' }}">
                                    <div class="fw-bold fs-5 text-secondary" style="width: 25px;">#{{ $index + 1 }}</div>
                                    
                                    {{-- Ürün Resim (Opsiyonel Placehoder) --}}
                                    <div class="rounded-3 bg-white border d-flex align-items-center justify-content-center flex-shrink-0" style="width: 40px; height: 40px;">
                                        <i class="fa-regular fa-gem text-muted opacity-50"></i>
                                    </div>

                                    <div class="flex-grow-1 overflow-hidden">
                                        <div class="fw-bold text-dark text-truncate" title="{{ $u->UrunAdi }}">{{ $u->UrunAdi }}</div>
                                        <div class="text-muted x-small font-monospace">{{ $u->StokKodu }}</div>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-danger rounded-pill px-3">{{ $u->SatilanMiktar }} Adet</span>
                                    </div>
                                    @if($index == 0) <i class="fa-solid fa-medal text-warning fa-lg ms-2"></i> @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .x-small { font-size: 0.75rem; }
    .card { transition: transform 0.2s; }
    /* .card:hover { transform: translateY(-5px); } */
</style>
@endsection

{{-- RETENTION MODAL --}}
<div class="modal fade" id="retentionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered shadow-lg">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-bottom-0 p-4 pb-0">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-chart-pie text-warning me-2"></i>Müşteri Analizi
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-5 bg-light py-3 rounded-4">
                    <div class="h1 fw-bold text-dark mb-0">{{ number_format($retentionStats['total'], 0, ',', '.') }}</div>
                    <div class="text-muted small text-uppercase letter-spacing-1">Toplam Tekil Müşteri</div>
                </div>

                <div class="mb-4">
                    <div class="d-flex justify-content-between mb-2 small fw-bold">
                        <span class="text-secondary"><i class="fa-solid fa-user-plus me-2 text-primary"></i>İlk Defa Alışveriş Yapanlar</span>
                        <span class="text-primary fs-5">%{{ $retentionStats['first_time_percent'] }}</span>
                    </div>
                    <div class="progress rounded-pill bg-primary bg-opacity-10" style="height: 12px;">
                        <div class="progress-bar bg-primary rounded-pill progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $retentionStats['first_time_percent'] }}%"></div>
                    </div>
                    <div class="text-muted x-small mt-2 ps-1">{{ number_format($retentionStats['first_time_count'], 0, ',', '.') }} Müşteri</div>
                </div>

                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-2 small fw-bold">
                        <span class="text-secondary"><i class="fa-solid fa-rotate me-2 text-success"></i>Tekrarlı Müşteriler (Repeat Buyer)</span>
                        <span class="text-success fs-5">%{{ $retentionStats['repeat_percent'] }}</span>
                    </div>
                    <div class="progress rounded-pill bg-success bg-opacity-10" style="height: 12px;">
                        <div class="progress-bar bg-success rounded-pill progress-bar-striped progress-bar-animated" role="progressbar" style="width: {{ $retentionStats['repeat_percent'] }}%"></div>
                    </div>
                    <div class="text-muted x-small mt-2 ps-1">{{ number_format($retentionStats['repeat_count'], 0, ',', '.') }} Müşteri</div>
                </div>
            </div>
            <div class="modal-footer border-top-0 p-4 pt-0">
                <button type="button" class="btn btn-dark w-100 rounded-pill py-2 fw-medium shadow-sm" data-bs-dismiss="modal">Anlaşıldı, Kapat</button>
            </div>
        </div>
    </div>
</div>
