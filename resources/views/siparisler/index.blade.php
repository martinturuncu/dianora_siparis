@extends('layouts.app')

@section('title', 'Sipariş Yönetimi')

@section('content')

<style>
    /* Dropdown açıldığında kartın z-indexini yükselt */
    .group-card { 
        position: relative; 
        z-index: 1; 
        transition: all 0.2s ease-in-out; 
    }
    .group-card:hover { 
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.08), 0 4px 6px -2px rgba(0, 0, 0, 0.04) !important;
        border-color: rgba(0,0,0,0.1) !important;
    }
    .group-card:focus-within,
    .group-card:has(.show) { 
        z-index: 1001 !important; 
    }

    .badge-premium {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1.25rem;
        font-weight: 700;
        letter-spacing: 0.025em;
        text-transform: uppercase;
        font-size: 0.75rem;
        border-radius: 9999px;
        backdrop-filter: blur(8px);
        -webkit-backdrop-filter: blur(8px);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(255, 255, 255, 0.2) !important;
    }
    .badge-premium:hover {
        transform: scale(1.05) translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    .glass-effect {
        background: rgba(255, 255, 255, 0.7) !important;
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
    }
    body.dark-mode .glass-effect {
        background: rgba(45, 45, 45, 0.7) !important;
        border-color: rgba(255, 255, 255, 0.1) !important;
    }
</style>

@php
    $platformBadges = [
        1 => ['bg' => '#212529', 'text' => 'dianorapiercing.com', 'icon' => 'fa-solid fa-globe', 'image' => asset('images/dianora_icon.png'), 'image_dark' => asset('images/dianora_icon_beyaz.png')],
        2 => ['bg' => '#f27a1a', 'text' => 'Trendyol', 'icon' => '', 'image' => asset('images/trendyol_icon.png')],
        3 => ['bg' => '#F1641E', 'text' => 'Etsy', 'icon' => 'fa-brands fa-etsy', 'image' => ''],
        4 => ['bg' => '#146eb4', 'text' => 'Hipicon', 'icon' => '', 'image' => asset('images/hipicon_icon.png')],
        5 => ['bg' => '#FF6000', 'text' => 'Hepsiburada', 'icon' => 'fa-solid fa-bag-shopping', 'image' => ''],
        6 => ['bg' => '#5f259f', 'text' => 'N11', 'icon' => 'fa-solid fa-n', 'image' => ''],
    ];
    $currentRoute = route('siparisler.index');
@endphp

<div class="pt-2 pb-4 min-vh-100">
    {{-- container-xl ile genişliği kısıtlıyoruz (User Request: "yatayda uzun") --}}
    <div class="container-xl">

        {{-- HEADER ALANI --}}
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h4 class="fw-bolder text-dark mb-1 d-flex align-items-center gap-2">
                    <i class="fa-solid fa-boxes-stacked text-primary"></i> Siparişler
                </h4>
                <p class="text-secondary small mb-0 fw-medium">Siparişlerini buradan takip et ve yönet.</p>
            </div>
            
            {{-- ARAMA KUTUSU VE FİLTRELER (ORTA ALAN) --}}
            <div class="flex-grow-1 w-100 mx-lg-5 d-flex flex-column gap-2" style="max-width: 500px;">
                <form action="{{ $currentRoute }}" method="GET">
                    {{-- Mevcut filtreleri korumak için hidden inputlar --}}
                    @if(request('platform')) <input type="hidden" name="platform" value="{{ request('platform') }}"> @endif
                    @if(request('durum') !== null) <input type="hidden" name="durum" value="{{ request('durum') }}"> @endif
                    @if(request('not_durumu')) <input type="hidden" name="not_durumu" value="{{ request('not_durumu') }}"> @endif

                    <div class="position-relative">
                        <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-3 text-muted"></i>
                        <input type="text" name="search" class="form-control form-control-lg border-0 shadow-sm ps-5 rounded-5 fs-6 glass-effect" 
                               placeholder="Sipariş ara..." 
                               value="{{ request('search') }}">
                        
                        @if(request()->anyFilled(['search', 'platform', 'durum', 'not_durumu']))
                            <a href="{{ $currentRoute }}" class="position-absolute top-50 end-0 translate-middle-y me-2 btn btn-sm btn-light rounded-circle text-danger w-30px h-30px d-flex align-items-center justify-content-center" title="Temizle">
                                <i class="fa-solid fa-times"></i>
                            </a>
                        @endif
                    </div>
                </form>

                {{-- FİLTRE GRUBU (Aramanın Altında) --}}
                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-between rounded-5 shadow-sm px-2 px-lg-3 py-1 border glass-effect" style="position: relative; z-index: 1050; gap: 5px;">
                    
                    {{-- Platform --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link text-decoration-none text-dark fw-bold d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown">
                            @if(request('platform')) <i class="fa-solid fa-filter text-primary"></i> <span class="text-primary fw-bold">Platform</span>
                            @else <span>Platform</span> <i class="fa-solid fa-chevron-down x-small opacity-50 ms-1"></i> @endif
                        </button>
                        <ul class="dropdown-menu border-0 shadow-lg rounded-4 mt-2">
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', request()->except('platform', 'page')) }}">Tümü</a></li>
                            <li><hr class="dropdown-divider"></li>
                            @foreach($pazaryerleri as $p)
                                <li>
                                    @php $badge = $platformBadges[$p->id] ?? null; @endphp
                                    <a class="dropdown-item small py-2 d-flex align-items-center gap-2 {{ request('platform') == $p->id ? 'bg-light fw-bold text-primary' : '' }}" 
                                       href="{{ route('siparisler.index', array_merge(request()->except('platform', 'page'), ['platform' => $p->id])) }}">
                                       @if($badge && !empty($badge['image']))
                                            <img src="{{ $badge['image'] }}" style="width: 20px; height: 20px; object-fit: contain;">
                                       @elseif($badge && !empty($badge['icon']))
                                            <i class="{{ $badge['icon'] }} w-20" style="color: {{ $badge['bg'] }};"></i>
                                       @else
                                            <i class="fa-solid fa-store w-20 text-muted"></i>
                                       @endif
                                       {{ $p->Ad }}
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                    
                    <div class="vr h-50 mx-2 text-muted opacity-25 d-none d-lg-block"></div>

                    {{-- Durum --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link text-decoration-none text-dark fw-bold d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown">
                            @if(request('durum') !== null) <i class="fa-solid fa-list-ul text-primary"></i> <span class="text-primary fw-bold">Durum</span>
                            @else <span>Durum</span> <i class="fa-solid fa-chevron-down x-small opacity-50 ms-1"></i> @endif
                        </button>
                        <ul class="dropdown-menu border-0 shadow-lg rounded-4 mt-2">
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', request()->except('durum', 'page')) }}">Tümü</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', array_merge(request()->except('durum', 'page'), ['durum' => '0'])) }}"><i class="fa-solid fa-hourglass-half text-warning w-20"></i> Hazırlanıyor</a></li>
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', array_merge(request()->except('durum', 'page'), ['durum' => '5'])) }}"><i class="fa-solid fa-hammer text-primary w-20"></i> Üretiliyor</a></li>
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', array_merge(request()->except('durum', 'page'), ['durum' => '6'])) }}"><i class="fa-solid fa-truck text-success w-20"></i> Kargolandı</a></li>
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', array_merge(request()->except('durum', 'page'), ['durum' => '8'])) }}"><i class="fa-solid fa-ban text-danger w-20"></i> İptal</a></li>
                        </ul>
                    </div>

                    <div class="vr h-50 mx-2 text-muted opacity-25 d-none d-lg-block"></div>

                    {{-- Not Filtresi --}}
                    <div class="dropdown">
                        <button class="btn btn-sm btn-link text-decoration-none text-dark fw-bold d-flex align-items-center gap-2 border-0" data-bs-toggle="dropdown">
                             @if(request('not_durumu')) <i class="fa-solid fa-sticky-note text-primary"></i> <span class="text-primary fw-bold">Not</span>
                             @else <span>Not</span> <i class="fa-solid fa-chevron-down x-small opacity-50 ms-1"></i> @endif
                        </button>
                        <ul class="dropdown-menu border-0 shadow-lg rounded-4 mt-2">
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', array_merge(request()->except('page', 'not_durumu'), ['not_durumu' => null])) }}">Tümü</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item small py-2" href="{{ route('siparisler.index', array_merge(request()->except('page', 'not_durumu'), ['not_durumu' => 'olan'])) }}"><i class="fa-solid fa-note-sticky text-warning w-20"></i> Notu Olanlar</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- ... existing code ... --}}
            {{-- AKSİYON BUTONLARI --}}
            <div class="d-flex align-items-center justify-content-center justify-content-lg-end gap-2 w-100 w-lg-auto">
                <div class="btn-group shadow-sm rounded-pill">
                    <a href="{{ route('siparis.guncelleVeKar') }}" id="updateOrdersBtn" class="btn btn-white btn-sync-hover border border-end-0 text-secondary fw-medium d-flex align-items-center gap-2 text-nowrap" style="height: 40px; border-top-left-radius: 50rem; border-bottom-left-radius: 50rem; position: relative" onclick="handleSyncAndUpdate(event, this)">
                        <i class="fa-solid fa-sync text-success sync-icon"></i> <span>Güncelle</span>
                    </a>
                    <button type="button" class="btn btn-white border border-start-0 text-secondary dropdown-toggle dropdown-toggle-split px-3" data-bs-toggle="dropdown" aria-expanded="false" style="height: 40px; border-top-right-radius: 50rem; border-bottom-right-radius: 50rem;">
                        <span class="visually-hidden">Seçenekler</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 p-2">
                        <li>
                            <a class="dropdown-item rounded small py-2 d-flex align-items-center gap-2" href="{{ route('siparis.guncelleVeKar', ['kapsam' => 'site']) }}" onclick="handleSyncAndUpdate(event, this, 'push')">
                                <i class="fa-solid fa-globe text-dark w-20 sync-icon"></i> Sadece Site
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item rounded small py-2 d-flex align-items-center gap-2" href="{{ route('siparis.guncelleVeKar', ['kapsam' => 'etsy']) }}" onclick="handleSyncAndUpdate(event, this)">
                                <i class="fa-brands fa-etsy text-warning w-20 sync-icon"></i> Sadece Etsy
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item btn-sync-hover rounded small py-2 d-flex align-items-center gap-2" href="{{ route('siparis.guncelleVeKar') }}" onclick="handleSyncAndUpdate(event, this)">
                                <i class="fa-solid fa-sync text-success w-20 sync-icon"></i> Tümünü Güncelle
                            </a>
                        </li>
                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a class="dropdown-item rounded small py-2 d-flex align-items-center gap-2" href="{{ route('siparis.guncelleVeKar', ['kapsam' => 'site', 'gun' => 15]) }}" onclick="handleSyncAndUpdate(event, this, 'push')">
                                <i class="fa-solid fa-calendar-days text-primary w-20 sync-icon"></i> Son 15 Gün (Site)
                            </a>
                        </li>
                    </ul>
                </div>

                <a href="{{ route('siparisler.manuel.create') }}" class="btn btn-dark rounded-pill px-4 shadow-sm fw-semibold d-flex align-items-center gap-2" style="height: 40px;">
                    <i class="fa-solid fa-plus small"></i> <span>Yeni</span>
                </a>
            </div>
        </div>



        {{-- ORDER LIST --}}
        <div class="d-flex flex-column gap-3">
            {{-- TABLO HEADER (Görsel Yardımcı) --}}
            <div class="row px-4 py-2 text-muted x-small text-uppercase fw-bold d-none d-lg-flex">
                <div class="col-5">Sipariş Bilgisi</div>
                <div class="col-2 text-center">Durum</div>
                <div class="col-2 text-center">Ürün Adedi</div>
                <div class="col-2 text-end">
                    Tutar 
                    <button class="btn btn-link p-0 text-secondary border-0 ms-1" id="toggleProfitBtn" title="Kâr Göster/Gizle">
                        <i class="fa-solid fa-eye"></i>
                    </button>
                </div>
                <div class="col-1 text-end"></div>
            </div>

            @forelse($siparisler->groupBy('SiparisID') as $siparisID => $urunler)
                @php
                    $anaSiparis = $urunler->first();
                    $hediyeCeki = $anaSiparis->HediyeCekiTutari ?? 0;
                    $odemeIndirimi = $anaSiparis->odemeIndirimi ?? 0;
                    // Müşteri: "Sadece site (ID:1) miktar ile çarpılmalı, diğerlerinde çarpılmayacak"
                    // Müşteri (Etsy Update): is_manuel=0 ise Birim*Miktar, is_manuel=1 ise Toplam
                    if ($anaSiparis->PazaryeriID == 1) {
                         $toplamTutar = $urunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari) * $u->Miktar) - $hediyeCeki - $odemeIndirimi;
                    } 
                    elseif ($anaSiparis->PazaryeriID == 3) {
                         if ((int)($anaSiparis->is_manuel ?? 0) === 0) {
                              $toplamTutar = $urunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari) * $u->Miktar) - $hediyeCeki - $odemeIndirimi;
                         } else {
                              $toplamTutar = $urunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari)) - $hediyeCeki - $odemeIndirimi;
                         }
                    }
                    else {
                         $toplamTutar = $urunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari)) - $hediyeCeki - $odemeIndirimi;
                    }
                    // Yeni mantık: SiparisKar artık direkt veritabanından geliyor ve hediye çeki düşülmüş net tutar.
                    $toplamKar = $anaSiparis->SiparisKar ?? 0;
                    $badge = $platformBadges[$anaSiparis->PazaryeriID] ?? ['bg' => '#6c757d', 'text' => 'Diğer', 'icon' => 'fa-solid fa-store', 'image' => ''];
                    
                    // Statü renkleri
                    $statusColor = match($anaSiparis->SiparisDurumu) {
                        0 => 'warning',
                        5 => 'primary',
                        6 => 'success',
                        8 => 'danger',
                        default => 'secondary'
                    };
                @endphp

                <div class="card border-0 shadow-sm rounded-5 mb-1 hover-shadow transition-all group-card">
                    {{-- CARD HEADER: ORDER SUMMARY --}}
                    <div class="card-header bg-white border-0 py-3 px-4 d-flex flex-wrap align-items-center rounded-5">
                        
                        {{-- 1. SOL: Order Info (Geniş alan) --}}
                        <div class="col-12 col-lg-5 mb-2 mb-lg-0 d-flex align-items-center gap-3">
                            {{-- LOGO ALANI (PLATFORM ICON) --}}
                            <div class="bg-card border rounded-circle d-flex align-items-center justify-content-center flex-shrink-0 shadow-sm p-2" style="width: 50px; height: 50px;">
                                @if($badge['image'])
                                    <img src="{{ $badge['image'] }}" class="{{ isset($badge['image_dark']) ? 'd-dark-none' : '' }}" style="width: 100%; height: 100%; object-fit: contain;">
                                    @if(isset($badge['image_dark']))
                                        <img src="{{ $badge['image_dark'] }}" class="d-dark-block" style="width: 100%; height: 100%; object-fit: contain;">
                                    @endif
                                @else
                                    <i class="{{ $badge['icon'] }} fs-4" style="color: {{ $badge['bg'] }};"></i>
                                @endif
                            </div>

                            <div>
                                <h6 class="fw-bold text-dark mb-1 d-flex align-items-center gap-2">
                                    {{ $anaSiparis->MusteriAdi }}
                                    @if(($anaSiparis->MusteriSiparisSayisi ?? 1) > 1)
                                        <span class="badge rounded-pill fw-bold d-inline-flex align-items-center gap-1 shadow-sm" 
                                              style="background: rgba(245, 158, 11, 0.15); color: #d97706; font-size: 0.65rem; border: 1px solid rgba(245, 158, 11, 0.2) !important;" 
                                              title="{{ $anaSiparis->MusteriSiparisSayisi }}. Siparişi">
                                            <i class="fa-solid fa-star small"></i>{{ $anaSiparis->MusteriSiparisSayisi }}. Sipariş
                                        </span>
                                    @endif
                                    @if(isset($anaSiparis->OdemeTipi) && (int)$anaSiparis->OdemeTipi === 1)
                                        <span class="badge bg-info text-white shadow-sm border-0 rounded-pill py-1 px-2 d-inline-flex align-items-center gap-1" style="background: linear-gradient(to right, #0dcaf0, #0aa2c0); font-size: 0.7rem;">
                                            <i class="fa-solid fa-money-bill-transfer"></i> Havale
                                        </span>
                                    @endif
                                    @if(($anaSiparis->MusteriIptalSayisi ?? 0) > 1)
                                        <span class="badge rounded-pill fw-bold d-inline-flex align-items-center gap-1 ms-1 shadow-sm" 
                                              style="background: rgba(239, 68, 68, 0.12); color: #dc2626; font-size: 0.65rem; border: 1px solid rgba(239, 68, 68, 0.15) !important;" 
                                              title="Müşterinin {{ $anaSiparis->MusteriIptalSayisi }} iptal/iade siparişi var!">
                                            <i class="fa-solid fa-triangle-exclamation small"></i>{{ $anaSiparis->MusteriIptalSayisi }} İptal/İade
                                        </span>
                                    @endif
                                </h6>
                                <div class="d-flex align-items-center gap-2 text-muted x-small flex-wrap">
                                    <span class="font-monospace fw-semibold">#{{ $anaSiparis->SiparisID }}</span>
                                    @if($anaSiparis->PazaryeriID == 1 && !empty($anaSiparis->SiparisNo))
                                        <span class="text-secondary opacity-50">•</span>
                                        <span class="font-monospace fw-semibold text-primary" title="Sipariş No">{{ $anaSiparis->SiparisNo }}</span>
                                    @endif
                                    <span class="text-secondary opacity-50">•</span>
                                    <span class="d-flex align-items-center gap-1 text-dark fw-medium">
                                        {{ $badge['text'] }}
                                    </span>
                                    <span class="text-secondary opacity-50">•</span>
                                    <span>{{ \Carbon\Carbon::parse($anaSiparis->SiparisTarihi)->format('d.m.Y H:i') }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- 2. ORTA: Durum --}}
                        <div class="col-6 col-lg-2 text-lg-center">
                            @php
                                $statusStyle = match((int)$anaSiparis->SiparisDurumu) {
                                    0 => [
                                        'bg' => 'rgba(245, 158, 11, 0.12)', 
                                        'text' => '#d97706', 
                                        'label' => 'Hazırlanıyor',
                                        'icon' => 'fa-solid fa-clock-rotate-left',
                                        'border' => 'rgba(245, 158, 11, 0.2)'
                                    ],
                                    5 => [
                                        'bg' => 'rgba(13, 110, 253, 0.12)', 
                                        'text' => '#0d6efd', 
                                        'label' => 'Üretiliyor',
                                        'icon' => 'fa-solid fa-hammer',
                                        'border' => 'rgba(13, 110, 253, 0.2)'
                                    ],
                                    6 => [
                                        'bg' => 'rgba(16, 185, 129, 0.12)', 
                                        'text' => '#059669', 
                                        'label' => 'Kargolandı',
                                        'icon' => 'fa-solid fa-truck-fast',
                                        'border' => 'rgba(16, 185, 129, 0.2)'
                                    ],
                                    8 => [
                                        'bg' => 'rgba(239, 68, 68, 0.12)', 
                                        'text' => '#dc2626', 
                                        'label' => 'İptal',
                                        'icon' => 'fa-solid fa-circle-xmark',
                                        'border' => 'rgba(239, 68, 68, 0.2)'
                                    ],
                                    9 => [
                                        'bg' => 'rgba(99, 102, 241, 0.12)', 
                                        'text' => '#4f46e5', 
                                        'label' => 'İade Edildi',
                                        'icon' => 'fa-solid fa-reply-all',
                                        'border' => 'rgba(99, 102, 241, 0.2)'
                                    ],
                                    default => [
                                        'bg' => 'rgba(107, 114, 128, 0.12)', 
                                        'text' => '#4b5563', 
                                        'label' => 'Bilinmiyor',
                                        'icon' => 'fa-solid fa-question',
                                        'border' => 'rgba(107, 114, 128, 0.2)'
                                    ]
                                };
                            @endphp
                            <span class="badge badge-premium" 
                                  style="background: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['text'] }}; border-color: {{ $statusStyle['border'] }} !important; min-width: 130px; justify-content: center; box-shadow: none; padding: 0.4rem 1rem;">
                                <i class="{{ $statusStyle['icon'] }} small"></i>
                                {{ $statusStyle['label'] }}
                            </span>
                        </div>

                        {{-- 3. ORTA: Adet --}}
                        <div class="col-6 col-lg-2 text-center text-secondary small fw-semibold">
                            <i class="fa-solid fa-layer-group text-muted opacity-75 me-1"></i> {{ $urunler->sum('Miktar') }} Ürün
                        </div>

                        {{-- 4. SAĞ: Tutar --}}
                        <div class="col-6 col-lg-2 text-end">
                            <div class="fw-bold text-dark fs-6">{{ number_format($toplamTutar, 2, ',', '.') }} ₺</div>
                    @php
                       $hasMissingGram = $urunler->contains(function($u) use ($hediyeKodlari) {
                           if ($u->UrunGram > 0) return false;
                           
                           $isHediye = false;
                           foreach ($hediyeKodlari as $hk) {
                               if (strcasecmp($u->StokKodu, $hk) === 0) {
                                   $isHediye = true;
                                   break;
                               }
                           }
                           return !$isHediye;
                       });
                    @endphp

                    {{-- Profit Check --}}
                    @if($hasMissingGram && $anaSiparis->SiparisDurumu != 8 && $anaSiparis->SiparisDurumu != 9)
                        <div class="x-small fw-bold text-danger bg-danger bg-opacity-10 px-2 py-0.5 rounded-pill d-inline-block mt-1">
                            <i class="fa-solid fa-triangle-exclamation me-1"></i>Gram Eksik
                        </div>
                    @else
                        <div class="x-small fw-semibold profit-display {{ $toplamKar > 0 ? 'text-success' : ($toplamKar < 0 ? 'text-danger' : 'text-secondary') }}">
                            {{ $toplamKar > 0 ? '+' : '' }}{{ number_format($toplamKar, 2, ',', '.') }} ₺
                        </div>
                    @endif
                        </div>

                        {{-- 5. BUTTONS --}}
                        <div class="col-6 col-lg-1 text-end">
                            <button class="btn btn-light btn-sm rounded-circle w-30px h-30px p-0" type="button" data-bs-toggle="collapse" data-bs-target="#order_{{ $siparisID }}" aria-expanded="false">
                                <i class="fa-solid fa-chevron-down text-muted transition-transform"></i>
                            </button>
                            <div class="d-inline-block dropdown ms-1">
                                <button class="btn btn-light btn-sm rounded-circle w-30px h-30px p-0" data-bs-toggle="dropdown"><i class="fa-solid fa-ellipsis-vertical text-muted small"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2">
                                    <li><a class="dropdown-item rounded small" href="/urun-detay/{{ $anaSiparis->SiparisID }}"><i class="fa-solid fa-eye text-primary w-20"></i> Detaylar</a></li>
                                    <li><a class="dropdown-item rounded small" href="#" data-bs-toggle="modal" data-bs-target="#notModal" data-siparis-id="{{ $anaSiparis->SiparisID }}"><i class="fa-solid fa-note-sticky text-info w-20"></i> Notlar</a></li>
                                    @if($anaSiparis->PazaryeriID == 3)
                                    <li>
                                        <a class="dropdown-item rounded small d-flex align-items-center justify-content-between" href="#" onclick="toggleUSA('{{ $anaSiparis->SiparisID }}'); return false;">
                                            <span><i class="fa-solid fa-flag-usa w-20 text-primary"></i> Amerika Siparişi</span> 
                                            <i class="fa-solid fa-check text-success ms-2 {{ $anaSiparis->isUSA ? '' : 'd-none' }}" id="usaCheck{{ $anaSiparis->SiparisID }}"></i>
                                        </a>
                                    </li>
                                    @endif
                                        <li><hr class="dropdown-divider my-2"></li>
                                        <li>
                                            <form action="{{ route('siparis.durumGuncelle', $anaSiparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="0"><button class="dropdown-item rounded small"><i class="fa-solid fa-hourglass w-20 text-warning"></i> Hazırlanıyor</button></form>
                                            <form action="{{ route('siparis.durumGuncelle', $anaSiparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="5"><button class="dropdown-item rounded small"><i class="fa-solid fa-hammer w-20 text-primary"></i> Üretiliyor</button></form>
                                            <form action="{{ route('siparis.durumGuncelle', $anaSiparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="6"><button class="dropdown-item rounded small"><i class="fa-solid fa-truck w-20 text-success"></i> Kargolandı</button></form>
                                            <form action="{{ route('siparis.durumGuncelle', $anaSiparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="8"><button class="dropdown-item rounded small"><i class="fa-solid fa-ban w-20 text-danger"></i> İptal</button></form>
                                            <form action="{{ route('siparis.durumGuncelle', $anaSiparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="9"><button class="dropdown-item rounded small"><i class="fa-solid fa-rotate-left w-20 text-danger"></i> İade</button></form>
                                        </li>
                                    @if($anaSiparis->PazaryeriID != 1)
                                        <li><hr class="dropdown-divider my-2"></li>
                                        <li><a href="#" class="dropdown-item rounded small text-danger" data-bs-toggle="modal" data-bs-target="#silModal{{ $anaSiparis->SiparisID }}"><i class="fa-solid fa-trash w-20"></i> Sil</a></li>
                                    @endif
                                </ul>
                            </div>
                        </div>

                        {{-- NOT BAR (Varsa) --}}
                        @if($anaSiparis->SonNot)
                            <div class="col-12 mt-2">
                                <div class="bg-warning bg-opacity-10 text-warning-emphasis px-3 py-2 rounded-3 small border border-warning-subtle d-inline-block">
                                    <i class="fa-solid fa-sticky-note me-2"></i> {{ $anaSiparis->SonNot }}
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- COLLAPSE BODY (ÜRÜNLER) --}}
                    {{-- Varsayılan olarak AÇIK olsun mu? User "compact" istedi, belki kapalı daha iyi ama ürünleri görmek istiyor. Açık bırakalım ya da basit tablo. --}}
                    {{-- User previous request said "show order then products underneath", so showing them directly is better. "Collapse" might hide info. --}}
                    {{-- Let's make it ALWAYS VISIBLE but styled as a minimal list inside the card --}}
                    
                    <div class="collapse" id="order_{{ $siparisID }}">
                    <div class="card-body bg-body bg-opacity-50 p-0 border-top">
                        <table class="table table-borderless table-sm mb-0">
                            <tbody>
                                @foreach($urunler as $urun)
                                    <tr class="align-middle">
                                        <td class="ps-5 ps-lg-5 py-2" style="width: 50%;">
                                            <div class="d-flex align-items-center gap-3">
                                                <i class="fa-solid fa-turn-up fa-rotate-90 text-muted opacity-25 ms-lg-3"></i>
                                                <div>
                                                    <div class="fw-semibold text-dark small d-flex align-items-center gap-2">
                                                        {{ $urun->UrunAdi }}
                                                        @php
                                                            $isProductHediye = false;
                                                            foreach ($hediyeKodlari as $hk) {
                                                                if (strcasecmp($urun->StokKodu, $hk) === 0) {
                                                                    $isProductHediye = true;
                                                                    break;
                                                                }
                                                            }
                                                        @endphp

                                                        @if($isProductHediye)
                                                            <span class="badge bg-success-subtle text-success border border-success-subtle py-0 px-2" style="font-size: 0.65rem;">
                                                                <i class="fa-solid fa-gift me-1"></i>Hediye
                                                            </span>
                                                        @endif
                                                    </div>
                                                    <div class="text-muted x-small font-monospace">{{ $urun->StokKodu }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-secondary small">{{ $urun->UrunKategori }}</td>
                                        <td class="text-center small">
                                            <div class="d-flex align-items-center justify-content-center gap-2">
                                                @if($urun->Miktar > 1) <span class="badge bg-dark rounded-pill">x{{ $urun->Miktar }}</span> @else <span class="text-muted">1 ad.</span> @endif
                                                <span class="badge bg-light text-secondary border fw-normal">{{ $urun->UrunGram > 0 ? number_format($urun->UrunGram, 2) . 'g' : '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4 small fw-semibold text-dark">
                                            @php
                                                $isProductHediyePrice = false;
                                                foreach ($hediyeKodlari as $hk) {
                                                    if (strcasecmp($urun->StokKodu, $hk) === 0) {
                                                        $isProductHediyePrice = true;
                                                        break;
                                                    }
                                                }
                                            @endphp

                                            @if($isProductHediyePrice)
                                                <span class="text-muted">-</span>
                                            @else
                                                {{ number_format(($urun->Tutar + $urun->KdvTutari) * $urun->Miktar, 2, ',', '.') }} ₺
                                            @endif
                                        </td>
                                        {{-- Detail Button --}}
                                        <td class="text-end pe-3" style="width: 5%;">
                                            <a href="/urun-detay/{{ $siparisID }}/{{ $urun->StokKodu }}" class="btn btn-white btn-sm border shadow-sm text-primary rounded-circle w-30px h-30px d-inline-flex align-items-center justify-content-center" title="Ürün Detayı">
                                                <i class="fa-solid fa-eye small"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    </div>
                </div>
                
                {{-- SİLME MODALI --}}
                @if($anaSiparis->PazaryeriID != 1)
                    @push('modals')
                    <div class="modal fade" id="silModal{{ $anaSiparis->SiparisID }}" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered modal-sm">
                            <div class="modal-content border-0 shadow rounded-4">
                                <div class="modal-body text-center p-4">
                                    <h6 class="fw-bold mb-3">Silinsin mi?</h6>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button class="btn btn-light btn-sm px-3" data-bs-dismiss="modal">İptal</button>
                                        <form action="{{ route('manuel.sil', $anaSiparis->SiparisID) }}" method="POST"> @csrf @method('DELETE') <button class="btn btn-danger btn-sm px-3">Sil</button> </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endpush
                @endif
            @empty
                <div class="text-center py-5">
                    <div class="bg-white rounded-circle shadow-sm d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                        <i class="fa-solid fa-ghost text-muted fs-1 opacity-50"></i>
                    </div>
                    <h5 class="fw-bold text-secondary">Sipariş Yok</h5>
                    <p class="text-muted small">Kriterlere uygun kayıt bulunamadı.</p>
                </div>
            @endforelse
        </div>

        {{-- PAGINATION --}}
        @if(isset($pagination) && $pagination['last_page'] > 1)
            <nav class="d-flex justify-content-between align-items-center mt-4 px-2">
                <div class="text-muted small">
                    Toplam {{ number_format($pagination['total']) }} sipariş, 
                    {{ $pagination['from'] }}-{{ $pagination['to'] }} arası gösteriliyor
                </div>
                <ul class="pagination pagination-sm mb-0">
                    {{-- Önceki --}}
                    @if($pagination['current_page'] > 1)
                        <li class="page-item">
                            <a class="page-link rounded-start-pill" href="{{ route('siparisler.index', array_merge(request()->except('page'), ['page' => $pagination['current_page'] - 1])) }}">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>
                    @endif
                    
                    {{-- Sayfa Numaraları --}}
                    @for($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['last_page'], $pagination['current_page'] + 2); $i++)
                        <li class="page-item {{ $i == $pagination['current_page'] ? 'active' : '' }}">
                            <a class="page-link" href="{{ route('siparisler.index', array_merge(request()->except('page'), ['page' => $i])) }}">{{ $i }}</a>
                        </li>
                    @endfor
                    
                    {{-- Sonraki --}}
                    @if($pagination['current_page'] < $pagination['last_page'])
                        <li class="page-item">
                            <a class="page-link rounded-end-pill" href="{{ route('siparisler.index', array_merge(request()->except('page'), ['page' => $pagination['current_page'] + 1])) }}">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    @endif
                </ul>
            </nav>
        @endif
    </div>
</div>

{{-- MODAL --}}
@push('modals')
<div class="modal fade" id="notModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-0 pb-0"><h6 class="fw-bold">Notlar</h6><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div id="notlarListesi" class="d-flex flex-column gap-2 mb-3"></div>
                <form id="notEkleForm" method="POST" class="d-flex gap-2">
                    @csrf <input type="text" name="not" class="form-control form-control-sm" placeholder="Not..." required> <button class="btn btn-dark btn-sm">Ekle</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endpush

<style>
    .w-30px { width: 30px; }
    .h-30px { height: 30px; }
    .w-20 { width: 20px; text-align: center; }
    .x-small { font-size: 0.75rem; }
    .border-dashed { border-style: dashed !important; }
    .hover-shadow:hover { box-shadow: 0 .5rem 1rem rgba(0,0,0,.05)!important; transform: translateY(-1px); }
    .transition-all { transition: all 0.2s ease; }
    .shadow-primary-sm { box-shadow: 0 4px 10px rgba(13, 110, 253, 0.2); }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const notModalEl = document.getElementById('notModal');
    if (!notModalEl) return;
    notModalEl.addEventListener('show.bs.modal', e => {
        const btn = e.relatedTarget;
        const id = btn.getAttribute('data-siparis-id');
        const list = document.getElementById('notlarListesi');
        document.getElementById('notEkleForm').action = `/siparisler/${id}/not-ekle`;
        list.innerHTML = 'Yükleniyor...';
        fetch(`/siparisler/${id}/notlar`).then(r=>r.json()).then(d=>{
            list.innerHTML='';
            d.forEach(n=> list.innerHTML+=`<div class="bg-light p-2 rounded small d-flex justify-content-between">${n.Not} <form action="/siparisler/${id}/not-sil/${n.ID}" method="POST">@csrf @method('DELETE')<button class="btn btn-link text-danger p-0 pt-1"><i class="fa-solid fa-times"></i></button></form></div>`);
        });
    });
});
</script>



{{-- SIDEBAR NOT ALANI --}}
<div id="noteSidebar" class="position-fixed top-50 end-0 bg-white shadow-lg transition-transform rounded-start-4" style="height: 75vh; width: 400px; transform: translate(100%, -50%); z-index: 1050; border-left: 1px solid #dee2e6;">
    <div class="d-flex flex-column h-100 rounded-start-4 overflow-hidden">
        {{-- Header --}}
        <div class="p-3 border-bottom d-flex justify-content-between align-items-center bg-light">
            <h6 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-pen-to-square text-warning me-2"></i>Notlarım
            </h6>
            <button class="btn btn-sm btn-light rounded-circle border" onclick="toggleNoteSidebar()">
                <i class="fa-solid fa-times text-secondary"></i>
            </button>
        </div>
        
        {{-- Body (Quill Editor) --}}
        <div class="flex-grow-1 d-flex flex-column" style="background-color: var(--bg-card);">
            <div id="quill-toolbar" class="border-bottom border-0"></div>
            <div id="editor-container" class="flex-grow-1 border-0" style="font-size: 1rem;"></div>
        </div>

        {{-- Footer --}}
        <div class="p-2 border-top bg-light x-small text-muted text-center d-flex justify-content-between align-items-center px-3">
            <span><i class="fa-solid fa-database text-secodary me-1"></i>Veritabanına kaydedilir</span>
            <span id="saveStatus" class="text-success fw-bold opacity-0 transition-all">Kaydedildi</span>
        </div>
    </div>
</div>

{{-- TRIGGER BUTTON (Sağ Kenar) --}}
<button id="noteToggleBtn" class="position-fixed top-50 end-0 translate-middle-y btn btn-warning text-dark shadow-lg rounded-start-pill py-2 pe-2 ps-1 border border-end-0 d-flex align-items-center gap-1 small" style="z-index: 1040; transform: translate(0, -50%); transition: transform 0.3s ease;" onclick="toggleNoteSidebar()">
    <i class="fa-solid fa-chevron-left x-small"></i>
    <span class="writing-mode-vertical" style="writing-mode: vertical-rl; transform: rotate(180deg); font-size: 0.75rem;">Notlar</span>
</button>

{{-- Quill.js CSS & JS (v2.0.2 for Checklists) --}}
<link href="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.snow.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/quill@2.0.2/dist/quill.js"></script>

<script>
    const sidebar = document.getElementById('noteSidebar');
    const toggleBtn = document.getElementById('noteToggleBtn');
    const saveStatus = document.getElementById('saveStatus');
    let quill;

    document.addEventListener('DOMContentLoaded', () => {
        // Init Quill
        quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }, { 'list': 'check' }],
                    [{ 'color': [] }, { 'background': [] }],
                    ['clean']
                ]
            },
            placeholder: 'Notlarınızı buraya yazın...',
        });

        // Load Notes from DB
        fetch('/notlar/get')
            .then(r => r.json())
            .then(data => {
                if(data.icerik && data.icerik !== '<p><br></p>') {
                    quill.root.innerHTML = data.icerik;
                } else {
                    // Default to Checklist mode if empty
                    quill.formatLine(0, 1, 'list', 'check');
                }
            });

        // Auto Save (Debounce)
        let saveTimeout;
        quill.on('text-change', function() {
            saveStatus.style.opacity = '0';
            clearTimeout(saveTimeout);
            saveTimeout = setTimeout(() => {
                saveNotes();
            }, 1000);
        });
    });

    function saveNotes() {
        const content = quill.root.innerHTML; // Quill 2.0: .root replaced .container.firstChild usually, but let's check docs. Actually quill.getSemanticHTML() is better in 2.0 but .root.innerHTML works for compatibility. 
        // Note: Quill 2.0 uses semantically correct HTML for lists.
        
        // However, in 2.0 `quill.root.innerHTML` is still the way to get HTML. 
        // But wait, user wanted "list check". 
        
        fetch('/notlar/save', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ icerik: content })
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                saveStatus.style.opacity = '1';
                setTimeout(() => { saveStatus.style.opacity = '0'; }, 2000);
            }
        });
    }

    function toggleNoteSidebar() {
        // Check if transform contains 'translate(0' (meaning it is visible on X axis)
        const isOpen = sidebar.style.transform.includes('translate(0');
        
        if (isOpen) {
            // CLOSE
            sidebar.style.transform = 'translate(100%, -50%)';
            toggleBtn.style.transform = 'translate(0, -50%)'; // Show button
            toggleBtn.style.opacity = '1';
        } else {
            // OPEN
            sidebar.style.transform = 'translate(0%, -50%)';
            toggleBtn.style.transform = 'translate(100%, -50%)'; // Hide button
            toggleBtn.style.opacity = '0';
        }
    }

    function toggleUSA(id) {
        // Optimistic UI Update
        const checkIcon = document.getElementById('usaCheck' + id);
        const isChecked = !checkIcon.classList.contains('d-none');
        
        if (isChecked) {
            checkIcon.classList.add('d-none');
        } else {
            checkIcon.classList.remove('d-none');
        }

        fetch('/siparisler/toggle-usa/' + id, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(r => r.json())
        .then(data => {
            if(!data.success) {
                alert(data.message);
                // Revert if failed
                if (isChecked) checkIcon.classList.remove('d-none');
                else checkIcon.classList.add('d-none');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Bir hata oluştu.');
             // Revert
             if (isChecked) checkIcon.classList.remove('d-none');
             else checkIcon.classList.add('d-none');
        });
    }
</script>

<style>
    /* --- MODERN UI STYLES --- */
    
    /* 1. Sidebar Container */
    #noteSidebar {
        background: var(--bg-card); /* Slight transparency */
        backdrop-filter: blur(10px); /* Glass effect */
        box-shadow: -10px 0 50px rgba(0,0,0,0.1) !important;
        border-left: 1px solid var(--border-color) !important;
    }

    /* 2. Header */
    #noteSidebar .border-bottom {
        border-color: rgba(0,0,0,0.05) !important;
        background: transparent !important;
    }

    /* 3. Quill Toolbar (Modern & Minimal) */
    .ql-toolbar.ql-snow { 
        border: none; 
        background: transparent; 
        padding: 15px 20px;
        opacity: 0.7;
        transition: opacity 0.3s;
    }
    .ql-toolbar.ql-snow:hover { opacity: 1; }
    
    .ql-editor { padding: 0 20px 20px 20px; }
    .ql-editor.ql-blank::before { font-style: normal; color: var(--text-muted-light); }

    /* 5. Custom Scrollbar */
    #noteSidebar ::-webkit-scrollbar { width: 6px; }
    #noteSidebar ::-webkit-scrollbar-track { background: transparent; }
    #noteSidebar ::-webkit-scrollbar-thumb { background: #e0e0e0; border-radius: 10px; }
    #noteSidebar ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }

    /* 6. Checklist Strikethrough */
    .ql-editor li[data-list="checked"] {
        text-decoration: line-through;
        color: #adb5bd; /* Lighter gray */
        transition: all 0.2s ease;
    }
    .ql-editor li[data-list="checked"]::before { background: #dee2e6 !important; border-color: #dee2e6 !important; } /* Checkbox color */

    /* 7. Toggle Button */
    #noteToggleBtn {
        border: none !important;
        box-shadow: -5px 5px 20px rgba(255, 193, 7, 0.4) !important;
        background: linear-gradient(135deg, #ffc107, #ffdb72);
        color: #583a00 !important;
        font-weight: 700;
        letter-spacing: 1px;
    }
    #noteToggleBtn:hover {
        transform: translate(0, -50%) scale(1.05) !important; /* Slight grow */
    }

    /* Transitions */
    .transition-transform { transition: transform 0.4s cubic-bezier(0.16, 1, 0.3, 1); /* Elegant easing */ }
    .transition-all { transition: opacity 0.3s ease; }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleBtn = document.getElementById('toggleProfitBtn');
        const icon = toggleBtn.querySelector('i');
        
        // Function to update UI based on state
        function updateProfitVisibility(show) {
            const displays = document.querySelectorAll('.profit-display');
            displays.forEach(el => {
                if(show) {
                    el.classList.remove('d-none');
                } else {
                    el.classList.add('d-none');
                }
            });
            
            // Update Icon
            if(show) {
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                toggleBtn.classList.remove('opacity-50');
            } else {
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                toggleBtn.classList.add('opacity-50');
            }
        }

        // Initialize from localStorage (Default: Show/True)
        let showProfit = localStorage.getItem('showProfit') !== 'false';
        updateProfitVisibility(showProfit);

        // Click Event
        toggleBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation(); // prevent affecting sort or other headers if any
            showProfit = !showProfit;
            localStorage.setItem('showProfit', showProfit);
            updateProfitVisibility(showProfit);
        });
    });
</script>
    {{-- LIGHTWEIGHT LOADING OVERLAY (replaces Bootstrap modals to prevent stuck backdrops) --}}
    <div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:99999; justify-content:center; align-items:center;">
        <div style="background:var(--bg-card, #fff); border-radius:1.5rem; padding:3rem; text-align:center; max-width:500px; width:90%; max-height:90vh; overflow-y:auto; box-shadow:0 25px 50px rgba(0,0,0,0.25);">
            <div id="overlayContent">
                <div class="spinner-border text-primary mb-4" style="width: 3rem; height: 3rem;" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="fw-bold mb-2" style="color:var(--text-main,#111)">İşlem Devam Ediyor</h5>
                <p class="mb-0" style="color:var(--text-muted,#6b7280)">Lütfen bekleyiniz...</p>
            </div>
        </div>
    </div>

    <script>
        function showOverlay(title, subtitle) {
            var overlay = document.getElementById('loadingOverlay');
            var content = document.getElementById('overlayContent');
            content.innerHTML = '<div class="spinner-border text-primary mb-4" style="width:3rem;height:3rem;" role="status"><span class="visually-hidden">Loading...</span></div>' +
                '<h5 class="fw-bold mb-2" style="color:var(--text-main,#111)">' + title + '</h5>' +
                '<p class="mb-0" style="color:var(--text-muted,#6b7280)">' + subtitle + '</p>';
            overlay.style.display = 'flex';
        }

        // Canlı progress polling için
        window.__syncPollTimer = null;
        function startSyncPolling() {
            var overlay = document.getElementById('overlayContent');
            overlay.innerHTML =
                '<div class="spinner-border text-primary mb-3" style="width:2.5rem;height:2.5rem;"></div>' +
                '<h5 class="fw-bold mb-2" style="color:var(--text-main,#111)">Siparişler Çekiliyor</h5>' +
                '<pre id="liveProgress" class="small text-start bg-dark text-white p-3 rounded overflow-auto mt-2 mb-0" ' +
                'style="max-height:280px;white-space:pre-wrap;font-family:monospace;">Başlıyor...</pre>';

            var lastLen = 0;
            window.__syncPollTimer = setInterval(function() {
                fetch('{{ route("sync.progress") }}', { cache: 'no-store' })
                    .then(function(r) { return r.json(); })
                    .then(function(data) {
                        var pre = document.getElementById('liveProgress');
                        if (!pre || !data.lines) return;
                        if (data.lines.length !== lastLen) {
                            pre.textContent = data.lines.join('\n');
                            pre.scrollTop = pre.scrollHeight;
                            lastLen = data.lines.length;
                        }
                    })
                    .catch(function() {}); // Sessiz hata, polling devam etsin
            }, 500);
        }
        function stopSyncPolling() {
            if (window.__syncPollTimer) {
                clearInterval(window.__syncPollTimer);
                window.__syncPollTimer = null;
            }
        }

        window.handleSyncAndUpdate = function(event, element, scope) {
            event.preventDefault();
            showOverlay('Siparişler Çekiliyor', 'Pazaryerlerinden yeni siparişler alınıyor ve kâr analizleri yapılıyor...');
            startSyncPolling();

            // 1. Önce siparişleri çekiyoruz (guncelleVeKar rotası)
            fetch(element.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
            .then(function(response) {
                if(response.redirected) return {success: true};
                return response.json();
            })
            .then(function(data) {
                if(data && data.success === false) {
                    throw new Error(data.message || 'Siparişler çekilirken hata oluştu.');
                }

                // Detay logu sakla (Kaydedildi/Güncellendi çıktıları)
                window.__siparisDetayLog = (data && data.log) ? data.log : [];

                // 2. Siparişler çekildikten sonra uzak veritabanına gönderiyoruz
                showOverlay('Siparişler Gönderiliyor', 'Yeni siparişler başarıyla çekildi, şimdi hedef sunucuya aktarılıyor...');
                
                return fetch('{{ route("siparis.sync") }}', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    }
                });
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (typeof stopSyncPolling === 'function') stopSyncPolling();
                var content = document.getElementById('overlayContent');
                content.innerHTML = '<div class="mb-3 text-success"><i class="fa-solid fa-circle-check" style="font-size:4rem;"></i></div>' +
                    '<h5 class="fw-bold text-success mb-0">İşlem Tamamlandı</h5>';
                setTimeout(function() { window.location.reload(); }, 500);
            })
            .catch(function(error) {
                if (typeof stopSyncPolling === 'function') stopSyncPolling();
                var content = document.getElementById('overlayContent');
                // Yerel sync başarılı olduysa yine tamamlandı göster
                if (window.__siparisDetayLog && window.__siparisDetayLog.length > 0) {
                    content.innerHTML = '<div class="mb-3 text-success"><i class="fa-solid fa-circle-check" style="font-size:4rem;"></i></div>' +
                        '<h5 class="fw-bold text-success mb-0">İşlem Tamamlandı</h5>';
                    setTimeout(function() { window.location.reload(); }, 500);
                } else {
                    content.innerHTML = '<div class="mb-3 text-danger"><i class="fa-solid fa-circle-xmark" style="font-size:4rem;"></i></div>' +
                        '<h5 class="fw-bold text-danger mb-2">Hata Oluştu</h5>' +
                        '<p class="small text-muted mb-3">' + error.message + '</p>' +
                        '<button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" onclick="window.location.reload();">Kapat</button>';
                }
            });
        };

        document.addEventListener('DOMContentLoaded', function() {
            // GÖNDER BUTONU: AJAX ile çalışır (Önceki gönder butonu varsa korunur)
            var syncBtn = document.getElementById('syncOrdersBtn');
            if(syncBtn) {
                syncBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    showOverlay('Siparişler Gönderiliyor', 'Veriler karşı sunucuya gönderiliyor...');

                    fetch('{{ route("siparis.sync") }}', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(function(response) { return response.json(); })
                    .then(function(data) {
                        var content = document.getElementById('overlayContent');
                        if (data.success) {
                            content.innerHTML = '<div class="mb-3 text-success"><i class="fa-solid fa-circle-check" style="font-size:4rem;"></i></div>' +
                                '<h5 class="fw-bold text-success mb-3">İşlem Tamamlandı</h5>' +
                                '<p class="small text-start bg-light p-3 rounded border overflow-auto" style="max-height:200px;color:var(--text-main,#111);">' + data.message + '</p>';
                            setTimeout(function() { window.location.reload(); }, 1500);
                        } else {
                            throw new Error(data.message || 'Bir hata oluştu.');
                        }
                    })
                    .catch(function(error) {
                        console.error('Error:', error);
                        var content = document.getElementById('overlayContent');
                        content.innerHTML = '<div class="mb-3 text-danger"><i class="fa-solid fa-circle-xmark" style="font-size:4rem;"></i></div>' +
                            '<h5 class="fw-bold text-danger mb-2">Hata Oluştu</h5>' +
                            '<p class="small text-start bg-light p-3 rounded border overflow-auto" style="max-height:250px;white-space:pre-wrap;word-break:break-all;color:var(--text-muted,#6b7280);">' + error.message + '</p>' +
                            '<button type="button" class="btn btn-secondary px-4 rounded-pill" onclick="document.getElementById(\'loadingOverlay\').style.display=\'none\'">Kapat</button>';
                    });
                });
            }
        });
    </script>
@endsection
