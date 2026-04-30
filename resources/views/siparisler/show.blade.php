@extends('layouts.app')

@section('title', 'Sipariş Detayı #' . $siparis->SiparisID)

@section('content')
<div class="py-4 bg-body" style="min-height: 100vh;">
    <div class="container-fluid" style="max-width: 1600px;">

@if(session('success'))
<div class="container mt-3">
    <div class="alert alert-success d-flex align-items-center mb-0" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>
        <div>{{ session('success') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
</div>
@endif

@if(session('hata'))
<div class="container mt-3">
    <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <div>{{ session('hata') }}</div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
</div>
@endif

@if($errors->any())
<div class="container mt-3">
    <div class="alert alert-danger d-flex align-items-center mb-0" role="alert">
        <i class="fa-solid fa-triangle-exclamation me-2"></i>
        <div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
    </div>
</div>
@endif

@php
    $platformBadges = [
        1 => ['bg' => '#212529', 'text' => 'dianorapiercing.com', 'icon' => 'fa-solid fa-globe', 'image' => asset('images/dianora_icon.png'), 'image_dark' => asset('images/dianora_icon_beyaz.png')],
        2 => ['bg' => '#f27a1a', 'text' => 'Trendyol', 'icon' => '', 'image' => asset('images/trendyol_icon.png')],
        3 => ['bg' => '#F1641E', 'text' => 'Etsy', 'icon' => 'fa-brands fa-etsy', 'image' => ''],
        4 => ['bg' => '#146eb4', 'text' => 'Hipicon', 'icon' => '', 'image' => asset('images/hipicon_icon.png')],
        5 => ['bg' => '#FF6000', 'text' => 'Hepsiburada', 'icon' => 'fa-solid fa-bag-shopping', 'image' => ''],
        6 => ['bg' => '#5f259f', 'text' => 'N11', 'icon' => 'fa-solid fa-n', 'image' => ''],
    ];

    $badge = $platformBadges[$siparis->PazaryeriID] ?? ['bg' => '#6c757d', 'text' => 'Diğer', 'icon' => 'fa-solid fa-store', 'image' => ''];
    
    // Toplam Hesaplamaları
    // NOT: Etsy'de Tutar zaten birim fiyat olarak geliyor, diğer pazaryerlerinde de birim fiyat.
    // Ancak daha önce Etsy için satır toplamı sanılmıştı. Kullanıcı geri bildirimi ile düzeltildi.
    // Müşteri: "Sadece site (ID:1) miktar ile çarpılmalı"
    // Müşteri (Etsy Update): is_manuel=0 ise Birim*Miktar, is_manuel=1 ise Toplam
    // İptal edilen ürünleri hariç tut
    $aktifUrunler = $urunler->filter(fn($u) => ($u->Durum ?? 0) == 0);
    if ($siparis->PazaryeriID == 1) {
         $toplamUrunTutari = $aktifUrunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari) * $u->Miktar);
    } 
    elseif ($siparis->PazaryeriID == 3) {
         if ((int)($siparis->is_manuel ?? 0) === 0) {
              $toplamUrunTutari = $aktifUrunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari) * $u->Miktar);
         } else {
              $toplamUrunTutari = $aktifUrunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari));
         }
    }
    else {
        // Diğerleri (Hipicon vb) için Tutar satır toplamıdır
        $toplamUrunTutari = $aktifUrunler->sum(fn($u) => ($u->Tutar + $u->KdvTutari));
    }
    // $toplamKar controller'dan geliyor artık
    
    $statusColor = match($siparis->SiparisDurumu) {
        0 => 'warning',
        5 => 'primary',
        6 => 'success',
        8 => 'danger',
        9 => 'danger',
        default => 'secondary'
    };
@endphp

<style>
    @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap');
    
    body {
        font-family: 'Plus Jakarta Sans', sans-serif !important;
    }
    
    .card {
        border-color: var(--border-color) !important;
    }
    
    .table-modern thead th {
        font-weight: 600;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        color: var(--text-muted);
        background-color: var(--bg-body);
        border-bottom: 1px solid var(--border-color);
        padding-top: 1rem;
        padding-bottom: 1rem;
    }
    
    .table-modern tbody td {
        padding-top: 1.25rem;
        padding-bottom: 1.25rem;
        vertical-align: middle;
        border-bottom: 1px solid var(--border-color);
        font-size: 0.9rem;
    }
    
    .table-modern tr:last-child td {
        border-bottom: none;
    }
    
    .icon-box {
        width: 48px;
        height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
    }
    
    .price-text {
        font-family: 'Plus Jakarta Sans', sans-serif;
        font-variant-numeric: tabular-nums;
        letter-spacing: -0.02em;
    }
    
    .gradient-profit {
        background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%) !important;
        --dot-color: #166534;
    }
    
    body.dark-mode .gradient-profit {
        --dot-color: #ffffff;
    }

    .gradient-loss {
        --dot-color: #991b1b;
    }

    body.dark-mode .gradient-loss {
        --dot-color: #ffffff;
    }
    
    .badge-modern {
        font-weight: 500;
        letter-spacing: 0.02em;
        padding: 0.5em 1em;
    }

    .dashed-line {
        border-top: 2px dashed var(--border-color);
        margin: 1.5rem 0;
    }
</style>

<div class="py-5">
    <div class="container container-xl">
        
        {{-- HEADER --}}
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-5 gap-3">
            <div class="d-flex align-items-center gap-4">
                <a href="{{ route('siparisler.index') }}" class="btn btn-white bg-white border border-2 shadow-none rounded-circle w-40px h-40px d-flex align-items-center justify-content-center text-secondary transition-all hover-scale">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div>
                    <div class="d-flex align-items-center gap-3 mb-1">
                        <h3 class="fw-bold text-dark mb-0 ls-tight">Sipariş #{{ $siparis->SiparisID }}</h3>
                        @switch($siparis->SiparisDurumu)
                            @case(0) <span class="badge bg-warning bg-opacity-10 text-warning border border-warning-subtle rounded-3 badge-modern"><i class="fa-solid fa-hourglass-start me-2"></i>Hazırlanıyor</span> @break
                            @case(5) <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-3 badge-modern"><i class="fa-solid fa-hammer me-2"></i>Üretiliyor</span> @break
                            @case(6) <span class="badge bg-success bg-opacity-10 text-success border border-success-subtle rounded-3 badge-modern"><i class="fa-solid fa-truck-fast me-2"></i>Kargolandı</span> @break
                            @case(8) <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-3 badge-modern"><i class="fa-solid fa-ban me-2"></i>İptal</span> @break
                            @case(9) <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-3 badge-modern"><i class="fa-solid fa-rotate-left me-2"></i>İade Edildi</span> @break
                            @default <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary-subtle rounded-3 badge-modern">Bilinmiyor</span>
                        @endswitch
                        
                        {{-- RATES BADGE (Top Right Independent) --}}
                        <div class="px-3 py-1 bg-card border rounded-pill shadow-sm d-flex align-items-center gap-3 ms-2">
                             <div class="d-flex align-items-center gap-1 text-muted small">
                                <i class="fa-solid fa-coins text-warning"></i>
                                <span class="fw-bold text-dark">{{ number_format($siparisKar['dolarKuru'] ?? 0, 2) }} ₺</span>
                             </div>
                             <div class="vr opacity-25"></div>
                             <div class="d-flex align-items-center gap-1 text-muted small">
                                <i class="fa-solid fa-gem text-primary"></i>
                                <span class="fw-bold text-dark">{{ number_format($siparisKar['altinBirimUSD'] ?? 0, 2) }} $</span>
                             </div>
                             <div class="vr opacity-25"></div>
                             <div class="d-flex align-items-center gap-1 text-muted small">
                                <i class="fa-solid fa-circle-dollar-to-slot text-success"></i>
                                <span class="fw-bold text-dark">{{ number_format($siparisKar['altinTL'] ?? 0, 2) }} ₺</span>
                             </div>
                        </div>
                    </div>
                    <div class="text-muted small">
                        <i class="fa-regular fa-clock me-1"></i> {{ \Carbon\Carbon::parse($siparis->Tarih)->format('d F Y, H:i') }}
                    </div>
                </div>
            </div>
            
            <div class="d-flex gap-2">
                <div class="dropdown">
                    <button class="btn btn-light bg-white border border-2 rounded-pill fw-semibold shadow-sm d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-pen-to-square"></i> Durum Güncelle
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-3 p-2">
                        <li>
                            <form action="{{ route('siparis.durumGuncelle', $siparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="0"><button class="dropdown-item rounded small py-2"><i class="fa-solid fa-hourglass w-20 text-warning"></i> Hazırlanıyor</button></form>
                        </li>
                        <li>
                            <form action="{{ route('siparis.durumGuncelle', $siparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="5"><button class="dropdown-item rounded small py-2"><i class="fa-solid fa-hammer w-20 text-primary"></i> Üretiliyor</button></form>
                        </li>
                        <li>
                            <form action="{{ route('siparis.durumGuncelle', $siparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="6"><button class="dropdown-item rounded small py-2"><i class="fa-solid fa-truck w-20 text-success"></i> Kargolandı</button></form>
                        </li>
                        <li>
                            <form action="{{ route('siparis.durumGuncelle', $siparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="8"><button class="dropdown-item rounded small py-2"><i class="fa-solid fa-ban w-20 text-danger"></i> İptal</button></form>
                        </li>
                        <li>
                            <form action="{{ route('siparis.durumGuncelle', $siparis->SiparisID) }}" method="POST">@csrf<input type="hidden" name="durum" value="9"><button class="dropdown-item rounded small py-2"><i class="fa-solid fa-rotate-left w-20 text-danger"></i> İade</button></form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="row g-4">
            {{-- SOL: İçerik --}}
            <div class="col-lg-8">
                
                {{-- Müşteri & Pazar Bilgisi --}}
                <div class="card bg-card rounded-5 border-0 mb-4 position-relative overflow-hidden">
                    <div class="card-body p-4">
                        <div class="d-flex flex-column flex-md-row align-items-md-center gap-4">
                            <div class="icon-box bg-body text-secondary flex-shrink-0">
                                @if($badge['image'])
                                    <img src="{{ $badge['image'] }}" class="{{ isset($badge['image_dark']) ? 'd-dark-none' : '' }}" style="width: 28px; height: 28px; object-fit: contain;">
                                    @if(isset($badge['image_dark']))
                                        <img src="{{ $badge['image_dark'] }}" class="d-dark-block" style="width: 28px; height: 28px; object-fit: contain;">
                                    @endif
                                @else
                                    <i class="{{ $badge['icon'] }} fs-5" style="color: {{ $badge['bg'] }};"></i>
                                @endif
                            </div>
                            <div class="flex-grow-1 border-end-md pe-4">
                                <label class="text-xs text-uppercase text-muted fw-bold mb-1">Müşteri</label>
                                <h5 class="fw-bold text-dark mb-1">{{ $siparis->AdiSoyadi }}</h5>
                                <div class="small text-secondary">
                                    @if($siparis->Il || $siparis->Ilce)
                                        <i class="fa-solid fa-location-dot me-1 text-muted"></i> {{ $siparis->Ilce }} / {{ $siparis->Il }}
                                    @else
                                        <span class="text-muted fst-italic">Adres bilgisi yok</span>
                                    @endif
                                </div>
                            </div>
                            <div class="ps-md-2 border-end-md pe-4">
                                <label class="text-xs text-uppercase text-muted fw-bold mb-1">Platform</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="fw-semibold text-dark">{{ $badge['text'] }}</span>
                                    <span class="badge bg-body text-secondary border px-2 py-1" style="font-size: 0.7rem;">Komisyon: %{{ number_format($siparis->KomisyonOrani * 100, 2, ',', '.') }}</span>
                                </div>
                            </div>
                            <div class="ps-md-2">
                                <form action="{{ route('siparis.updateAyar', $siparis->SiparisID) }}" method="POST" class="d-flex flex-column">
                                    @csrf
                                    <label class="text-xs text-uppercase text-muted fw-bold mb-1">Altın Ayar Oranı</label>
                                    <div class="input-group input-group-sm" style="max-width: 150px;">
                                        <input type="number" step="0.001" min="0" max="1" name="ayar_orani" class="form-control" value="{{ number_format($siparis->ayar_orani ?? 0.585, 3, '.', '') }}" style="font-size: 0.8rem;">
                                        <button type="submit" class="btn btn-dark btn-sm">
                                            <i class="fa-solid fa-save"></i>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- CRM (Müşteri Analizi) KARTI --}}
                @if(isset($musteriGecmisi) && ( ($musteriGecmisi['toplamSiparis'] ?? 0) > 1 || ($musteriGecmisi['toplamIptal'] ?? 0) > 0 ))
                <div class="card bg-card rounded-5 border-0 mb-4 overflow-hidden">
                    {{-- Gradient Header --}}
                    <div class="px-4 py-3 d-flex align-items-center justify-content-between" style="background: var(--bg-body);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-circle w-40px h-40px d-flex align-items-center justify-content-center">
                                <i class="fa-solid fa-user-tag fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-0">Müşteri Sadakati</h6>
                                <span class="x-small text-muted">Sipariş geçmişi analizi</span>
                            </div>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary-subtle rounded-pill px-3 py-2 fw-bold">
                            {{ $musteriGecmisi['toplamSiparis'] }}. Siparişi
                        </span>
                    </div>

                    <div class="card-body p-4 pt-3">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-7">
                                <div class="p-3 bg-body rounded-3 border">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small">Toplam Sipariş Sayısı:</span>
                                        <span class="fw-bold text-dark">{{ $musteriGecmisi['toplamSiparis'] }}</span>
                                    </div>
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <span class="text-muted small">Toplam Aldığı Ürün:</span>
                                        <span class="fw-bold text-dark">{{ $musteriGecmisi['toplamUrun'] ?? 0 }} Adet</span>
                                    </div>
                                    <div class="mt-3 pt-2 border-top border-light text-center">
                                        @if($musteriGecmisi['toplamSiparis'] >= 2)
                                            <span class="badge bg-warning text-dark border border-warning-subtle shadow-sm mb-2">
                                                <i class="fa-solid fa-star me-1"></i>SADIK MÜŞTERİ
                                            </span>
                                        @endif
                                        
                                        @if(($musteriGecmisi['toplamIptal'] ?? 0) > 1)
                                            <div class="d-flex align-items-center gap-2 justify-content-center bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-3 p-2 small fw-bold">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                Dikkat: {{ $musteriGecmisi['toplamIptal'] }} adet iptal/iade siparişi var!
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-5 text-end">
                                <button class="btn btn-outline-secondary btn-sm rounded-pill w-100" type="button" data-bs-toggle="collapse" data-bs-target="#gecmisSiparisler">
                                    <i class="fa-solid fa-clock-rotate-left me-2"></i>Geçmişi Göster
                                </button>
                            </div>
                        </div>

                        {{-- Geçmiş Siparişler Listesi --}}
                        <div class="collapse mt-3" id="gecmisSiparisler">
                            <div class="table-responsive rounded-3 border">
                                <table class="table table-sm table-hover align-middle mb-0 x-small bg-card">
                                    <thead class="bg-body">
                                        <tr>
                                            <th class="ps-3">Tarih</th>
                                            <th>Sipariş No</th>
                                            <th class="text-end pe-3">İşlem</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($musteriGecmisi['oncekiSiparisler'] as $gs)
                                            <tr>
                                                <td class="ps-3 text-nowrap">{{ \Carbon\Carbon::parse($gs->Tarih)->format('d.m.Y') }}</td>
                                                <td><span class="font-monospace text-muted">{{ $gs->SiparisNo }}</span></td>
                                                <td class="text-end pe-3">
                                                    <a href="{{ route('siparis.show', $gs->SiparisID) }}" class="btn btn-link py-0 px-2 text-primary" target="_blank">
                                                        İncele <i class="fa-solid fa-arrow-up-right-from-square ms-1" style="font-size: 0.7em;"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Ürünler Tablosu --}}
                <div class="card bg-card rounded-5 border-0 mb-4">
                    <div class="card-header bg-card border-bottom py-4 px-4">
                        <div class="d-flex align-items-center justify-content-between">
                            <h5 class="fw-bold text-dark mb-0">Ürünler</h5>
                            @php
                                $aktifUrunSayisi = $urunler->filter(fn($u) => ($u->Durum ?? 0) == 0)->sum('Miktar');
                                $iptalUrunSayisi = $urunler->filter(fn($u) => ($u->Durum ?? 0) == 1)->count();
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                @if($iptalUrunSayisi > 0)
                                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger-subtle rounded-pill px-3">{{ $iptalUrunSayisi }} İptal</span>
                                @endif
                                <span class="badge bg-dark rounded-pill px-3">{{ $aktifUrunSayisi }} Adet</span>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-modern align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                    <th class="ps-4">ÜRÜN DETAYI</th>
                                    <th>KATEGORİ</th>
                                    <th class="text-center">ÖZELLİKLER</th>
                                    <th class="text-end">FİYAT</th>
                                    <th class="text-end">MALİYET</th>
                                    <th class="text-end pe-4">KÂR</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($urunler as $u)
                                    @php $isIptal = ($u->Durum ?? 0) == 1; @endphp
                                    <tr style="{{ $isIptal ? 'opacity: 0.4;' : '' }}">
                                        <td class="ps-4">
                                            <div>
                                                <div class="fw-bold text-dark d-flex align-items-center gap-2" style="{{ $isIptal ? 'text-decoration: line-through;' : '' }}">
                                                    {{ $u->UrunAdi }}
                                                    @if($isIptal)
                                                        <span class="badge bg-danger bg-opacity-15 text-danger border border-danger-subtle rounded-pill px-2 py-1" style="font-size: 0.65rem; text-decoration: none;">
                                                            <i class="fa-solid fa-ban me-1"></i>İPTAL
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted font-monospace mt-1">{{ $u->StokKodu }}</div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-secondary border fw-normal">{{ $u->UrunKategori }}</span>
                                        </td>
                                        <td class="text-center">
                                            <div class="d-flex flex-column align-items-center gap-1">
                                                <span class="badge bg-card text-dark border px-2">x{{ $u->Miktar }} Adet</span>
                                                <span class="text-muted x-small font-monospace">{{ ($u->Gram ?? 0) > 0 ? number_format($u->Gram, 2) . 'g' : '-' }}</span>
                                            </div>
                                        </td>
                                        <td class="text-end">
                                            @if($u->isHediye ?? false)
                                                <span class="badge bg-success-subtle text-success border border-success-subtle">
                                                    <i class="fa-solid fa-gift me-1"></i>Hediye
                                                </span>
                                            @elseif($isIptal)
                                                <div class="text-muted small" style="text-decoration: line-through;">{{ number_format(($u->Tutar + $u->KdvTutari), 2, ',', '.') }} ₺</div>
                                            @else
                                                <div class="fw-bold text-dark price-text">{{ number_format(($u->Tutar + $u->KdvTutari), 2, ',', '.') }} ₺</div>
                                                @if($siparis->PazaryeriID == 3)
                                                    @php
                                                        $dolar = $siparisKar['dolarKuru'] ?? $ayar->dolar_kuru ?? 1;
                                                        $isAuto = (int)($siparis->is_manuel ?? 0) === 0;

                                                        if ($isAuto) {
                                                            // API: Tutar Birim Fiyattır.
                                                            $birimTL = ($u->Tutar + $u->KdvTutari);
                                                            $birimUSD = $birimTL / $dolar;
                                                            $satirToplamUSD = $birimUSD * $u->Miktar;
                                                        } else {
                                                            // MANUEL: Tutar Toplam Fiyattır.
                                                            $satirToplamTL = ($u->Tutar + $u->KdvTutari);
                                                            $satirToplamUSD = $satirToplamTL / $dolar;
                                                            $birimUSD = ($u->Miktar > 0) ? ($satirToplamUSD / $u->Miktar) : 0;
                                                        }
                                                    @endphp
                                                    <div class="text-muted x-small mt-1 fst-italic">
                                                        {{ number_format($birimUSD, 2) }} $ x {{ $u->Miktar }} = <span class="fw-bold text-dark small">{{ number_format($satirToplamUSD, 2) }} $</span>
                                                    </div> 
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            @if($isIptal)
                                                <div class="text-muted">-</div>
                                            @elseif($u->isHediye ?? false)
                                                <div class="text-muted">-</div>
                                            @elseif(isset($u->detay['toplamMaliyet']))
                                                {{-- Maliyet hala gösterilebilir, ancak kâr artık sipariş bazlı --}}
                                                <div class="text-secondary price-text small">{{ number_format($u->detay['toplamMaliyet'], 2, ',', '.') }} ₺</div>
                                            @else
                                                <div class="text-muted">-</div>
                                            @endif
                                        </td>
                                        <td class="text-end pe-4">
                                            {{-- Ürün bazlı kâr artık gösterilmiyor çünkü hediye çeki genel düşüyor --}}
                                            <div class="text-muted small">
                                                <i class="fa-solid fa-calculator text-secondary opacity-25" title="Sipariş Toplamında Hesaplanır"></i>
                                            </div>
                                        </td>
                                        <td class="text-end pe-4" style="width: 80px;">
                                            <div class="d-flex align-items-center gap-1 justify-content-end">
                                                <a href="/urun-detay/{{ $siparis->SiparisID }}/{{ $u->StokKodu }}" class="btn btn-white btn-sm border shadow-sm text-primary rounded-circle w-30px h-30px d-inline-flex align-items-center justify-content-center" title="Ürün İncele">
                                                    <i class="fa-solid fa-eye small"></i>
                                                </a>
                                                @if($isIptal)
                                                    {{-- Geri Al Butonu --}}
                                                    <form action="{{ route('siparis.urunIptalGeriAl', [$siparis->SiparisID, $u->Id]) }}" method="POST" onsubmit="return confirm('Ürün iptali geri alınsın mı?')" style="text-decoration: none;">
                                                        @csrf
                                                        <button class="btn btn-white btn-sm border shadow-sm text-success rounded-circle w-30px h-30px d-inline-flex align-items-center justify-content-center" title="İptali Geri Al">
                                                            <i class="fa-solid fa-rotate-left small"></i>
                                                        </button>
                                                    </form>
                                                @else
                                                    {{-- İptal Butonu --}}
                                                    @if(!($u->isHediye ?? false))
                                                    <form action="{{ route('siparis.urunIptal', [$siparis->SiparisID, $u->Id]) }}" method="POST" onsubmit="return confirm('Bu ürünü iptal etmek istediğinize emin misiniz?')" style="text-decoration: none;">
                                                        @csrf
                                                        <button class="btn btn-white btn-sm border shadow-sm text-danger rounded-circle w-30px h-30px d-inline-flex align-items-center justify-content-center" title="Ürünü İptal Et">
                                                            <i class="fa-solid fa-xmark small"></i>
                                                        </button>
                                                    </form>
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>

                    </div>
                </div>
                
                {{-- Notlar --}}
                <div class="card bg-card rounded-5 border-0">
                    <div class="card-header bg-card border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                        <h6 class="fw-bold mb-0">Notlar</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('siparis.not.ekle', $siparis->SiparisID) }}" method="POST" class="mb-4 position-relative">
                            @csrf
                            <input type="text" name="not" class="form-control form-control-lg ps-4 pe-5 fs-6" placeholder="Bir not yazın..." required style="border-radius: 12px;">
                            <button class="btn btn-dark position-absolute top-50 end-0 translate-middle-y me-2 rounded-circle w-30px h-30px p-0 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;"><i class="fa-solid fa-arrow-right small"></i></button>
                        </form>
                        
                        <div class="vstack gap-3">
                            @forelse($notlar as $n)
                                <div class="bg-body p-3 rounded-3 d-flex justify-content-between align-items-center border">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="width-8px height-8px rounded-circle bg-warning"></div>
                                        <div>
                                            <div class="text-dark small mb-0">{{ $n->Not }}</div>
                                            <div class="text-muted x-small">{{ \Carbon\Carbon::parse($n->Tarih)->diffForHumans() }}</div>
                                        </div>
                                    </div>
                                    <form action="{{ route('siparis.not.sil', ['id' => $siparis->SiparisID, 'notId' => $n->ID]) }}" method="POST" onsubmit="return confirm('Silinsin mi?')">
                                        @csrf @method('DELETE')
                                        <button class="btn btn-link text-danger p-0 border-0 opacity-50 hover-opacity-100"><i class="fa-regular fa-trash-can"></i></button>
                                    </form>
                                </div>
                            @empty
                                <div class="text-center text-muted small py-4 bg-light rounded-3 border border-dashed">
                                    <i class="fa-regular fa-note-sticky fs-4 mb-2 d-block opacity-25"></i>
                                    Henüz not eklenmemiş.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

            </div>

            {{-- SAĞ: Özet --}}
            <div class="col-lg-4">
                <div class="card bg-card rounded-5 border-0 sticky-top" style="top: 20px; z-index: 10;">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark mb-4">Ödeme Özeti</h6>
                        
                        {{-- Data Points --}}
                        <div class="vstack gap-3">
                            @if(isset($ayar->altin_satis))
                                <div class="d-flex justify-content-between align-items-center p-3 bg-body rounded-3 border">
                                    <div class="d-flex align-items-center gap-2 small">
                                        <i class="fa-solid fa-coins text-warning"></i>
                                        <span class="fw-medium text-dark">Altın Kuru</span>
                                    </div>
                                    <span class="fw-semibold text-dark price-text">{{ number_format($ayar->altin_satis, 2, ',', '.') }} TL</span>
                                </div>
                            @endif

                            <div class="d-flex justify-content-between small">
                                <span class="fw-medium text-dark">Toplam Ürün Tutarı</span>
                                <span class="fw-semibold text-dark price-text text-nowrap text-end">
                                    @if($siparis->PazaryeriID == 3)
                                        @php $toplamUSD = $toplamUrunTutari / ($siparisKar['dolarKuru'] ?? 1); @endphp
                                        {{ number_format($toplamUSD, 2) }} $
                                        <span class="text-muted x-small">({{ number_format($toplamUrunTutari, 2, ',', '.') }} ₺)</span>
                                    @else
                                        {{-- YENİ MANTIK: İndirimsiz Toplam Service'den Geliyor --}}
                                        @php 
                                            // Service'den gelen ham indirimsiz toplam yoksa (eski kayıt vs), mevcut view hesabını kullan
                                            $gosterilecekUrunTutari = $siparisKar['indirimsizToplam'] ?? $toplamUrunTutari;
                                            
                                            // Eğer havale indirimi varsa ve service yeni total göndermemişse (eski), manuel ekle (eski mantık fallback)
                                            if(!isset($siparisKar['indirimsizToplam']) && ($siparisKar['havale_indirimi'] ?? 0) > 0) {
                                                $gosterilecekUrunTutari += $siparisKar['havale_indirimi'];
                                            }
                                        @endphp
                                        
                                        {{ number_format($gosterilecekUrunTutari, 2, ',', '.') }} ₺
                                    @endif
                                </span>
                            </div>

                            @if(($siparisKar['odemeIndirimiTL'] ?? 0) > 0)
                                <div class="d-flex justify-content-between small">
                                    <span class="fw-medium text-dark">Ödeme İndirimi</span>
                                    <span class="text-danger price-text text-nowrap text-end">
                                        @if($siparis->PazaryeriID == 3)
                                            -{{ number_format($siparisKar['odemeIndirimiUSD'], 2) }} $
                                            <span class="text-muted x-small">(-{{ number_format($siparisKar['odemeIndirimiTL'], 2, ',', '.') }} ₺)</span>
                                        @else
                                            -{{ number_format($siparisKar['odemeIndirimiTL'], 2, ',', '.') }} ₺
                                        @endif
                                    </span>
                                </div>
                            @endif

                            @if(($siparis->HediyeCekiTutari ?? 0) > 0)
                                <div class="d-flex justify-content-between small">
                                    <span class="fw-medium text-dark">Hediye Çeki</span>
                                    <span class="text-danger price-text">-{{ number_format($siparis->HediyeCekiTutari, 2, ',', '.') }} ₺</span>
                                </div>
                            @endif

                            @if(($siparisKar['havale_indirimi'] ?? 0) > 0)
                                <div class="d-flex justify-content-between small">
                                    <span class="fw-medium text-dark">Havale İndirimi (%5)</span>
                                    <span class="text-success price-text">-{{ number_format($siparisKar['havale_indirimi'], 2, ',', '.') }} ₺</span>
                                </div>
                            @endif
                            
                            @if(($siparis->HediyeCekiTutari ?? 0) > 0 || ($siparisKar['havale_indirimi'] ?? 0) > 0 || ($siparisKar['odemeIndirimiTL'] ?? 0) > 0)
                                <div class="d-flex justify-content-between text-dark small fw-bold mt-1 pt-1 border-top border-secondary-subtle">
                                    <span>Alınan Ödeme</span>
                                    <span class="price-text text-nowrap text-end">
                                        @if($siparis->PazaryeriID == 3)
                                            @php
                                                $baseUSD = $toplamUrunTutari / ($siparisKar['dolarKuru'] ?? 1);
                                                $finalUSD = $baseUSD - ($siparisKar['odemeIndirimiUSD'] ?? 0);
                                                
                                                $finalTL = ($gosterilecekUrunTutari ?? 0) 
                                                            - ($siparis->HediyeCekiTutari ?? 0) 
                                                            - ($siparisKar['havale_indirimi'] ?? 0)
                                                            - ($siparisKar['odemeIndirimiTL'] ?? 0);
                                            @endphp
                                            {{ number_format($finalUSD, 2) }} $
                                            <span class="text-muted x-small">({{ number_format($finalTL, 2, ',', '.') }} ₺)</span>
                                        @else
                                            @php
                                                $finalFiyat = ($gosterilecekUrunTutari ?? 0) 
                                                            - ($siparis->HediyeCekiTutari ?? 0) 
                                                            - ($siparisKar['havale_indirimi'] ?? 0)
                                                            - ($siparisKar['odemeIndirimiTL'] ?? 0);
                                            @endphp
                                            {{ number_format($finalFiyat, 2, ',', '.') }} ₺
                                        @endif
                                    </span>
                                </div>
                            @endif
                            
                            <div class="d-flex justify-content-between small">
                                <span class="fw-medium text-dark">Toplam Gramaj</span>
                                <span class="fw-semibold text-dark font-monospace">{{ number_format($toplamGram, 2) }}g</span>
                            </div>


                            @if($toplamEkstra != 0)
                                <div class="d-flex justify-content-between small">
                                    <span class="fw-medium text-dark">Ekstra Gelir/Gider</span>
                                    <span class="fw-bold {{ $toplamEkstra > 0 ? 'text-success' : 'text-danger' }}">
                                        {{ $toplamEkstra > 0 ? '+' : '' }}{{ number_format($toplamEkstra, 2, ',', '.') }} ₺
                                    </span>
                                </div>
                            @endif
                            
                            {{-- Manuel Ekstralar Listesi --}}
                            @if($ekstralar->count() > 0)
                                <div class="border-top border-secondary-subtle pt-2 mt-2">
                                    <label class="fw-bold text-dark text-uppercase mb-2" style="font-size: 0.75rem; letter-spacing: 0.05em;">Manuel İşlemler</label>
                                    <div class="vstack gap-2">
                                        @foreach($ekstralar as $e)
                                            <div class="d-flex justify-content-between align-items-center bg-card border rounded-3 px-3 py-2 shadow-sm transition-all">
                                                <div class="d-flex align-items-center gap-3 overflow-hidden flex-grow-1" style="min-width: 0;">
                                                    <div class="bg-{{ $e->Tur == 'GELIR' ? 'success' : 'danger' }} opacity-75 rounded-circle" style="width: 8px; height: 8px; flex-shrink: 0;"></div>
                                                    <div class="text-truncate">
                                                        <?php 
                                                            // Kur bilgisini (USD içeren parantezleri) temizle
                                                            $temizAciklama = preg_replace('/\s*\([^)]*USD[^)]*\)/i', '', $e->Aciklama ?: ($e->Tur == 'GELIR' ? 'Ek Gelir' : 'Ek Gider'));
                                                        ?>
                                                        <span class="text-dark fw-bold" style="font-size: 0.8rem;" title="{{ $e->Aciklama }}">
                                                            {{ $temizAciklama }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="d-flex align-items-center gap-3 ms-3 flex-shrink-0">
                                                    <span class="fw-bold {{ $e->Tur == 'GELIR' ? 'text-success' : 'text-danger' }} text-nowrap" style="font-size: 0.85rem;">
                                                        {{ $e->Tur == 'GELIR' ? '+' : '-' }}{{ number_format($e->Tutar, 2, ',', '.') }} ₺
                                                    </span>
                                                    <form action="{{ route('siparis.ekstraSil', $e->Id) }}" method="POST" onsubmit="return confirm('Silmek istediğinize emin misiniz?')">
                                                        @csrf @method('DELETE')
                                                        <button class="btn btn-link btn-sm p-0 text-muted opacity-50 hover-opacity-100 transition-all">
                                                            <i class="fa-solid fa-xmark" style="font-size: 0.9rem;"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="dashed-line"></div>
                        
                        {{-- Giderler ve Kar Kalemleri (Eski Yer - Modifiyeli) --}}
                        <div class="vstack gap-2">
                            {{-- 1. Altın Maliyeti --}}
                             <div class="d-flex justify-content-between align-items-center small">
                                <span class="fw-medium text-dark">Altın Maliyeti</span>
                                <span class="text-danger price-text text-nowrap text-end">
                                    @if($siparisKar['etsy'] ?? false)
                                        -{{ number_format($siparisKar['altinMaliyetUSD'], 2) }} $
                                        <span class="text-muted x-small">(-{{ number_format($siparisKar['altin'], 2, ',', '.') }} ₺)</span>
                                    @else
                                        -{{ number_format($toplamAltinMaliyeti, 2, ',', '.') }} ₺
                                    @endif
                                </span>
                            </div>

                            {{-- 2. Komisyon --}}
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="fw-medium text-dark">Komisyon</span>
                                <span class="text-danger price-text text-nowrap text-end">
                                    @if($siparisKar['etsy'] ?? false)
                                        -{{ number_format($siparisKar['komisyonUSD'], 2) }} $
                                        <span class="text-muted x-small">(-{{ number_format($siparisKar['komisyon'], 2, ',', '.') }} ₺)</span>
                                    @else
                                        -{{ number_format($toplamKomisyon, 2, ',', '.') }} ₺
                                    @endif
                                </span>
                            </div>

                            {{-- 3. Vergi --}}
                            <div class="d-flex justify-content-between align-items-center small">
                                <span class="fw-medium text-dark">Vergi</span>
                                <span class="text-danger price-text text-nowrap text-end">
                                    @if($siparisKar['etsy'] ?? false)
                                        -{{ number_format($siparisKar['vergiUSD'], 2) }} $
                                        <span class="text-muted x-small">(-{{ number_format($siparisKar['vergi'], 2, ',', '.') }} ₺)</span>
                                    @else
                                        -{{ number_format($toplamVergi, 2, ',', '.') }} ₺
                                    @endif
                                </span>
                            </div>

                            {{-- ShipEntegra (Etsy USA Kargo) --}}
                            @if(($siparisKar['etsyShipCost'] ?? 0) > 0)
                                <div class="d-flex justify-content-between align-items-center small mb-2">
                                    <span class="fw-medium text-dark">ShipEntegra</span>
                                    <span class="text-danger price-text text-nowrap text-end">
                                        -{{ number_format($siparisKar['etsyShipCost'], 2) }} $
                                        {{-- Kargo gideri için TL karşılığı --}}
                                        @php
                                            $shipTL = $siparisKar['etsyShipCost'] * ($siparisKar['dolarKuru'] ?? 1);
                                        @endphp
                                        <span class="text-muted x-small">(-{{ number_format($shipTL, 2, ',', '.') }} ₺)</span>
                                    </span>
                                </div>
                            @endif
                            
                            {{-- 4. Giderler Main --}}
                            <div class="d-flex justify-content-between align-items-center small clickable" data-bs-toggle="collapse" data-bs-target="#giderDetay" role="button" aria-expanded="false">
                                <span class="d-flex align-items-center gap-1 fw-medium text-dark">
                                    <i class="fa-solid fa-chevron-down x-small" style="font-size: 0.6rem;"></i> 
                                    Giderler
                                </span>
                                <span class="text-danger price-text text-nowrap text-end">
                                    @if($siparisKar['etsy'] ?? false)
                                        -{{ number_format($siparisKar['giderUSD'], 2) }} $
                                        <span class="text-muted x-small">(-{{ number_format($siparisKar['gider'], 2, ',', '.') }} ₺)</span>
                                    @else
                                        -{{ number_format($toplamGider, 2, ',', '.') }} ₺
                                    @endif
                                </span>
                            </div>
                            
                            
                            {{-- Giderler Detay (Collapse) --}}
                            <div class="collapse ps-3 border-start ms-1" id="giderDetay">
                                @php $isEtsy = $siparisKar['etsy'] ?? false; @endphp
                                <div class="vstack gap-1 mt-1">
                                    @foreach([
                                        'iscilik' => 'İşçilik',
                                        'kargo' => 'Kargo (Sbt)',
                                        'kargo_yurtdisi' => 'Yurtdışı Kargo',
                                        'kutu' => 'Kutu/Ambalaj (Sbt)',
                                        'kargo_gidis' => 'Kargo Gidiş',
                                        'kargo_donus' => 'Kargo Dönüş',
                                        'reklam' => 'Reklam'
                                    ] as $key => $label)
                                        @if(($detayliGiderler[$key] ?? 0) > 0)
                                        <div class="d-flex justify-content-between x-small text-muted">
                                            <span>{{ $label }}</span>
                                            <span>
                                                @if($isEtsy)
                                                    <?php 
                                                        $valUSD = $detayliGiderler[$key];
                                                        $displayUSD = ($key == 'kargo_gidis' || $key == 'kargo_donus') ? $valUSD / ($siparisKar['dolarKuru'] ?? 1) : $valUSD;
                                                        $displayTL = ($key == 'kargo_gidis' || $key == 'kargo_donus') ? $valUSD : $valUSD * ($siparisKar['dolarKuru'] ?? 1);
                                                    ?>
                                                    <span class="text-dark fw-bold">{{ number_format($displayUSD, 2) }} $</span>
                                                    <span class="ms-1">({{ number_format($displayTL, 2, ',', '.') }} ₺)</span>
                                                @else
                                                    -{{ number_format($detayliGiderler[$key], 2, ',', '.') }}
                                                @endif
                                            </span>
                                        </div>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="dashed-line"></div>
                        

                        <div class="{{ (!($siparisKar['hesaplanabilir'] ?? true) || $toplamKar < 0) ? 'gradient-loss' : 'gradient-profit' }} p-4 rounded-5 text-center position-relative overflow-hidden">
                            <div class="position-absolute top-0 start-0 w-100 h-100" style="background-image: radial-gradient(var(--dot-color) 1px, transparent 1px); background-size: 20px 20px; opacity: 0.2;"></div>
                            <div class="position-relative z-1">
                                <div class="small fw-bold text-uppercase tracking-wider mb-1" style="letter-spacing: 0.1em; color: var(--text-main); opacity: 0.7;">
                                    @if($siparis->SiparisDurumu == 9) İADE ZARARI
                                    @elseif($siparis->SiparisDurumu == 8) İPTAL ZARARI
                                    @elseif($toplamKar < 0) NET ZARAR
                                    @else NET KÂR
                                    @endif
                                </div>
                                
                                @if($siparisKar['hesaplanabilir'] ?? true)
                                    @php
                                        $profitColor = $toplamKar < 0 ? '#991b1b' : '#14532d';
                                    @endphp
                                    <div class="fw-bolder display-6 mb-0 price-text d-dark-none" style="color: {{ $profitColor }};">{{ number_format($toplamKar, 2, ',', '.') }} ₺</div>
                                    <div class="fw-bolder display-6 mb-0 price-text d-dark-block text-white">{{ number_format($toplamKar, 2, ',', '.') }} ₺</div>
                                    
                                    @if($siparisKar['etsy'] ?? false)
                                        <div class="small fw-bold mt-1 opacity-75 d-dark-none" style="color: {{ $profitColor }};">({{ number_format($siparisKar['karUSD'], 2) }} $)</div>
                                        <div class="small fw-bold mt-1 opacity-75 d-dark-block text-white">({{ number_format($siparisKar['karUSD'], 2) }} $)</div>
                                    @endif
                                @else
                                    <div class="text-danger fw-bolder h3 mb-0">HESAPLANAMADI</div>
                                    <div class="small fw-bold mt-1 text-danger opacity-75"><i class="fa-solid fa-triangle-exclamation me-1"></i>Eksik Gram Bilgisi</div>
                                @endif
                            </div>
                        </div>
                        
                        {{-- Collapse Action --}}
                        <div class="mt-4 text-center">
                            <button class="btn btn-link text-muted text-decoration-none small" type="button" data-bs-toggle="collapse" data-bs-target="#ekstraEkle">
                                <i class="fa-solid fa-sliders me-1"></i> Manuel İşlem Ekle
                            </button>
                        </div>
                        <div class="collapse mt-2" id="ekstraEkle">
                            <div class="bg-body p-3 rounded-3 border">
                                <form action="{{ route('siparis.ekstraEkle') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="siparis_id" value="{{ $siparis->SiparisID }}">
                                    <input type="hidden" name="para_birimi" value="{{ ($siparis->PazaryeriID ?? 0) == 3 ? 'USD' : 'TL' }}">
                                    <div class="mb-2">
                                        <select name="tur" class="form-select form-select-sm">
                                            <option value="GELIR">Gelir (+)</option>
                                            <option value="GIDER">Gider (-)</option>
                                        </select>
                                    </div>
                                    <div class="input-group input-group-sm mb-2">
                                        <input type="number" step="0.01" name="tutar" class="form-control" placeholder="0.00" required>
                                        <span class="input-group-text text-muted">{{ ($siparis->PazaryeriID ?? 0) == 3 ? 'USD' : 'TL' }}</span>
                                    </div>
                                    <input type="text" name="aciklama" class="form-control form-control-sm mb-2" placeholder="Açıklama...">
                                    <button class="btn btn-dark btn-sm w-100 rounded-3">Kaydet</button>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
    </div>
</div>
@endsection
