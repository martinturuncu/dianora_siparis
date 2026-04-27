@extends('layouts.app')

@section('title', 'Ürün Finansal Detayı')

@section('content')
<div class="container-fluid py-4 bg-light" style="min-height: 100vh;">
    <div class="container" style="max-width: 1200px;">

        {{-- ÜST HEADER --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            <div>
                <h3 class="fw-bold text-dark mb-0">
                    <i class="fa-solid fa-chart-pie text-primary me-2"></i>Finansal Analiz
                </h3>
                <p class="text-muted small mb-0">Ürün bazlı kârlılık ve maliyet dökümü.</p>
            </div>
            
            <div class="d-flex gap-2">
                {{-- 🔥 YENİ BUTON --}}
                <button type="button" class="btn btn-dark shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#detayEkstraModal">
                    <i class="fa-solid fa-plus-minus me-2"></i>Gelir/Gider Ekle
                </button>

                <a href="{{ route('siparisler.index') }}" class="btn btn-white border shadow-sm fw-semibold text-secondary">
                    <i class="fa-solid fa-arrow-left me-2"></i>Listeye Dön
                </a>
            </div>
        </div>

        {{-- Bildirimler --}}
        @if ($errors->any())
            <div class="alert alert-danger shadow-sm border-0 border-start border-danger border-4">
                <h6 class="fw-bold">Bir Hata Oluştu!</h6>
                <ul class="mb-0 ps-3">
                    @foreach ($errors->all() as $error)
                        <li><small>{{ $error }}</small></li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4" role="alert">
                <i class="fa-solid fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- ANA KART --}}
        <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
            
            {{-- ÜRÜN BAŞLIK BAR --}}
            <div class="card-header bg-white border-bottom p-4">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center text-center text-md-start">
                    <div>
                        <h4 class="fw-bold text-dark mb-1">
                            {{ $urun->UrunAdi }}
                            <span class="badge bg-light text-dark border ms-2 fw-normal" style="font-size: 0.6em; vertical-align: middle;">{{ $urun->StokKodu }}</span>
                        </h4>
                        <div class="text-muted small">
                            <span class="me-3"><i class="fa-solid fa-layer-group me-1"></i> {{ $urun->KategoriAdi ?? 'Genel' }}</span>
                            <span class="me-3"><i class="fa-solid fa-hashtag me-1"></i> Sipariş: #{{ $urun->SiparisID }}</span>
                            
                            @if(isset($sonuclar['miktar']) && $sonuclar['miktar'] > 1)
                                <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25">
                                    <i class="fa-solid fa-box-open me-1"></i> {{ $sonuclar['miktar'] }} Adet
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- PAZARYERİ BADGE & KURLAR --}}
                    <div class="mt-3 mt-md-0 text-md-end">
                        <div class="d-flex flex-column align-items-center align-items-md-end gap-2">
                            {{-- Badge --}}
                            @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                <span class="badge bg-warning text-dark border border-warning px-3 py-2 rounded-pill shadow-sm">
                                    <i class="fa-brands fa-etsy me-1"></i> ETSY (USD)
                                </span>
                            @else
                                <span class="badge bg-dark px-3 py-2 rounded-pill shadow-sm">
                                    <i class="fa-solid fa-shop me-1"></i> {{ $sonuclar['pazaryeri'] }} (TL)
                                </span>
                            @endif

                            {{-- Kur Bilgileri (Modern Pill) --}}
                            <div class="d-inline-flex gap-3 small text-secondary bg-light bg-opacity-75 px-3 py-1 rounded-3 border">
                                <span title="Hesaplamada kullanılan Dolar Kuru">
                                    <i class="fa-solid fa-money-bill-1-wave text-success me-1"></i>
                                    Kur: <strong>{{ number_format($ayar->dolar_kuru, 4, ',', '.') }} ₺</strong>
                                </span>
                                <div class="vr opacity-25"></div>
                                <span title="Hesaplamada kullanılan Altın Fiyatı">
                                    <i class="fa-regular fa-gem text-warning me-1"></i>
                                    @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                        Altın USD: <strong>{{ number_format($ayar->altin_usd, 2, ',', '.') }} $</strong>
                                    @else
                                        Altın TL: <strong>{{ number_format($ayar->altin_fiyat, 2, ',', '.') }} ₺</strong>
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                {{-- 🔥 YENİ: BİRLEŞTİRİLMİŞ ADET BİLGİLENDİRME KUTUSU --}}
                @if(isset($sonuclar['miktar']) && $sonuclar['miktar'] > 1)
                    <div class="alert alert-info bg-info bg-opacity-25 border-info border-opacity-50 small p-3 m-4 mb-0 rounded-3">
                        <i class="fa-solid fa-circle-info me-2"></i>
                        Bu siparişte <strong>{{ $sonuclar['miktar'] }} adet</strong> ürün bulunmaktadır. Aşağıdaki tüm hesaplamalar bu adede göre yapılmıştır.
                    </div>
                @endif

                <div class="row g-0 h-100">

                    {{-- SOL KOLON: MALİYETLER VE GİDERLER --}}
                    <div class="col-lg-5 border-end bg-light bg-opacity-50">
                        <div class="p-4 p-md-5">
                            <h6 class="fw-bold text-secondary text-uppercase ls-1 mb-4">
                                <i class="fa-solid fa-wallet me-2 text-dark"></i>Maliyet & Giderler
                            </h6>

                            {{-- Ürün Özellikleri --}}
                            <div class="d-flex justify-content-between align-items-center mb-3 p-3 bg-white rounded border shadow-sm">
                                <div>
                                    <small class="text-muted d-block">Ürün Gramı</small>
                                    <span class="fw-bold text-dark fs-5">{{ number_format($urun->Gram, 2) }} gr</span>
                                </div>
                                @if(isset($sonuclar['miktar']) && $sonuclar['miktar'] > 1)
                                    <div class="text-end">
                                        <small class="text-muted d-block">Toplam Ağırlık</small>
                                        <span class="fw-bold text-primary">{{ number_format($urun->Gram * $sonuclar['miktar'], 2) }} gr</span>
                                    </div>
                                @endif
                            </div>

                            {{-- Gider Listesi --}}
                            <ul class="list-group list-group-flush bg-transparent">
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-bottom-0 px-0 py-2">
                                    <div>
                                        <span class="text-secondary"><i class="fa-solid fa-ring text-muted me-2"></i>Altın Maliyeti</span>
                                        @if(isset($sonuclar['miktar']) && $sonuclar['miktar'] > 1)
                                            <small class="text-muted d-block ps-4">({{ number_format($sonuclar['altinMaliyeti'] ?? 0 / $sonuclar['miktar'], 2, ',', '.') }} ₺ x {{ $sonuclar['miktar'] }})</small>
                                        @endif
                                    </div>
                                     <span class="fw-semibold text-dark">
                                         @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                             {{ number_format($sonuclar['altinMaliyetUSD'], 2) }} $ <small class="text-muted">({{ number_format($sonuclar['altinMaliyetUSD'] * $ayar->dolar_kuru, 2) }} ₺)</small>
                                         @else
                                             {{ number_format($sonuclar['altinMaliyeti'], 2, ',', '.') }} ₺
                                             @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                                <small class="text-muted"> (~{{ number_format($sonuclar['altinMaliyeti'] / $ayar->dolar_kuru, 2) }} $)</small>
                                             @endif
                                         @endif
                                     </span>
                                </li>
                                <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-bottom-0 px-0 py-2">
                                    <div>
                                        <span class="text-secondary"><i class="fa-solid fa-hammer text-muted me-2"></i>İşçilik & Diğer</span>
                                        @if(isset($sonuclar['miktar']) && $sonuclar['miktar'] > 1)
                                            <small class="text-muted d-block ps-4">({{ number_format($sonuclar['gider'] / $sonuclar['miktar'], 2, ',', '.') }} ₺ x {{ $sonuclar['miktar'] }})</small>
                                        @endif
                                    </div>
                                     <span class="fw-semibold text-dark">
                                         @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                             {{ number_format($sonuclar['giderUSD'], 2) }} $ <small class="text-muted">({{ number_format($sonuclar['giderUSD'] * $ayar->dolar_kuru, 2) }} ₺)</small>
                                         @else
                                             {{ number_format($sonuclar['gider'], 2, ',', '.') }} ₺
                                             @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                                <small class="text-muted"> (~{{ number_format($sonuclar['gider'] / $ayar->dolar_kuru, 2) }} $)</small>
                                             @endif
                                         @endif
                                     </span>
                                </li>
                                @if(isset($sonuclar['isUSA']) && $sonuclar['isUSA'])
                                    <li class="list-group-item bg-transparent d-flex justify-content-between align-items-center border-bottom-0 px-0 py-2">
                                        <span class="text-secondary"><i class="fa-solid fa-plane text-muted me-2"></i>ShipEntegra</span>
                                        <span class="fw-semibold text-dark">{{ number_format($ayar->etsy_ship_cost, 2) }} $</span>
                                    </li>
                                @endif
                            </ul>

                            {{-- TOPLAM MALİYET KARTI --}}
                            <div class="mt-4 p-4 bg-dark text-white rounded-4 shadow-sm position-relative overflow-hidden">
                                 <div class="position-relative z-2 d-flex flex-column">
                                     <span class="fw-semibold text-white-50 text-uppercase small mb-1 ls-1">Toplam Maliyet</span>
                                     <span class="fw-bold fs-3">
                                         @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                             {{ number_format($sonuclar['toplamMaliyetUSD'], 2) }} $
                                         @else
                                             {{ number_format($sonuclar['toplamMaliyet'], 2, ',', '.') }} ₺
                                         @endif
                                     </span>
                                     @if(! (isset($sonuclar['etsy']) && $sonuclar['etsy']) && isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                        <div class="small text-white-50 fw-normal mt-1">~ {{ number_format($sonuclar['toplamMaliyet'] / $ayar->dolar_kuru, 2) }} $</div>
                                     @endif
                                 </div>
                                <i class="fa-solid fa-money-bill-transfer position-absolute text-white opacity-10" 
                                   style="font-size: 5rem; right: -15px; bottom: -15px; transform: rotate(-15deg); z-index: 1;"></i>
                            </div>
                        </div>
                    </div>

                    {{-- SAĞ KOLON: SATIŞ VE KÂR --}}
                    <div class="col-lg-7">
                        <div class="p-4 p-md-5">
                            <h6 class="fw-bold text-secondary text-uppercase ls-1 mb-4">
                                <i class="fa-solid fa-cash-register me-2 text-success"></i>Satış & Gelir Analizi
                            </h6>

                            <div class="table-responsive">
                                <table class="table table-borderless align-middle mb-0">
                                    <tbody>
                                        {{-- BRÜT SATIŞ --}}
                                        <tr>
                                            <td class="ps-0 text-muted"><i class="fa-solid fa-tag me-2"></i>Brüt Satış Fiyatı
                                                @if(isset($sonuclar['miktar']) && $sonuclar['miktar'] > 1)<small class="d-block text-muted">({{ number_format($sonuclar['gercekSatis'] / $sonuclar['miktar'], 2, ',', '.') }} ₺ x {{ $sonuclar['miktar'] }})</small>@endif
                                            </td>                                            <td class="text-end fw-bold fs-5 text-dark">
                                                @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                                    {{ number_format($urun->Tutar / $ayar->dolar_kuru, 2) }} $
                                                    <div class="small text-muted fw-normal">({{ number_format($urun->Tutar, 2) }} ₺)</div>
                                                @else
                                                    {{ number_format($sonuclar['gercekSatis'], 2, ',', '.') }} ₺
                                                @endif
                                            </td>
                                        </tr>

                                        {{-- KESİNTİLER --}}
                                        <tr class="border-top">
                                            <td colspan="2" class="ps-0 pt-3 pb-2">
                                                <span class="badge bg-light text-secondary border">Kesintiler (Vergi & Komisyon)</span>
                                            </td>
                                        </tr>

                                        @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                            <tr>
                                                <td class="ps-0 text-secondary small">Etsy Komisyonu (%{{ number_format($sonuclar['komisyon'] * 100, 2) }})</td>
                                                <td class="text-end text-danger small">- {{ number_format(($urun->Tutar/$ayar->dolar_kuru) * $sonuclar['komisyon'], 2) }} $</td>
                                            </tr>
                                            @if($sonuclar['isUSA'])
                                                <tr>
                                                    <td class="ps-0 text-secondary small">USA Tax (%{{ number_format($ayar->etsy_usa_tax_rate * 100, 2) }})</td>
                                                    <td class="text-end text-danger small">
                                                        @php 
                                                            $usaVergi = ($urun->Tutar - ($urun->Tutar * $sonuclar['komisyon'])) * $ayar->etsy_usa_tax_rate; 
                                                        @endphp
                                                        - {{ number_format($usaVergi / $ayar->dolar_kuru , 2) }} $
                                                    </td>
                                                </tr>
                                            @endif
                                         @else
                                             <tr>
                                                 <td class="ps-0 text-secondary small">KDV</td>
                                                 <td class="text-end text-danger small">
                                                    - {{ number_format($sonuclar['vergi'], 2, ',', '.') }} ₺
                                                    @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                                        <span class="text-muted"> (~{{ number_format($sonuclar['vergi'] / $ayar->dolar_kuru, 2) }}$)</span>
                                                    @endif
                                                 </td>
                                             </tr>
                                             <tr>
                                                  <td class="ps-0 text-secondary small">Pazaryeri Komisyonu (%{{ number_format($sonuclar['komisyon'] * 100, 2, ',', '.') }})</td>
                                                 <td class="text-end text-danger small">
                                                    - {{ number_format($sonuclar['odenenKomisyon'], 2, ',', '.') }} ₺
                                                    @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                                        <span class="text-muted"> (~{{ number_format($sonuclar['odenenKomisyon'] / $ayar->dolar_kuru, 2) }}$)</span>
                                                    @endif
                                                 </td>
                                             </tr>
                                         @endif

                                        {{-- 🔥 EKSTRA GELİR/GİDER SATIRI --}}
                                        @if(isset($ekstralar) && ($ekstralar->ToplamGelir > 0 || $ekstralar->ToplamGider > 0))
                                        <tr class="border-top">
                                            <td colspan="2" class="ps-0 pt-3 pb-2">
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                    Ekstra İşlemler (Sipariş Bazlı)
                                                </span>
                                            </td>
                                        </tr>
                                         @if($ekstralar->ToplamGelir > 0)
                                             <tr>
                                                 <td class="ps-0 text-secondary small">Ek Gelirler</td>
                                                 <td class="text-end text-success fw-bold small">
                                                    + {{ number_format($ekstralar->ToplamGelir, 2, ',', '.') }} ₺
                                                    @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                                        <span class="text-muted"> (~{{ number_format($ekstralar->ToplamGelir / $ayar->dolar_kuru, 2) }}$)</span>
                                                    @endif
                                                 </td>
                                             </tr>
                                         @endif
                                         @if($ekstralar->ToplamGider > 0)
                                             <tr>
                                                 <td class="ps-0 text-secondary small">Ek Giderler</td>
                                                 <td class="text-end text-danger fw-bold small">
                                                    - {{ number_format($ekstralar->ToplamGider, 2, ',', '.') }} ₺
                                                    @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                                        <span class="text-muted"> (~{{ number_format($ekstralar->ToplamGider / $ayar->dolar_kuru, 2) }}$)</span>
                                                    @endif
                                                 </td>
                                             </tr>
                                         @endif
                                        @endif


                                        {{-- NET SATIŞ (ARA TOPLAM) --}}
                                        <tr class="border-top">
                                            <td class="ps-0 text-primary fw-semibold pt-3">Net Ele Geçen</td>
                                             <td class="text-end text-primary fw-bold pt-3 fs-5">
                                                 @if(isset($sonuclar['etsy']) && $sonuclar['etsy'])
                                                     {{ number_format($sonuclar['netUSD'], 2) }} $
                                                 @else
                                                     {{ number_format($sonuclar['gercekNetSatis'], 2, ',', '.') }} ₺
                                                     @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                                        <div class="small text-muted fw-normal">(~ {{ number_format($sonuclar['gercekNetSatis'] / $ayar->dolar_kuru, 2) }} $)</div>
                                                     @endif
                                                 @endif
                                             </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            {{-- BÜYÜK FİNAL KARTI: NET KÂR --}}
                            @php
                                // Servisten gelen kâr
                                $baseProfit = isset($sonuclar['etsy']) && $sonuclar['etsy'] ? $sonuclar['karTL'] : $sonuclar['gercekKar'];
                                
                                // Ekstraları dahil et
                                $ekGelir = $ekstralar->ToplamGelir ?? 0;
                                $ekGider = $ekstralar->ToplamGider ?? 0;
                                
                                // Final Kâr
                                $finalKar = $baseProfit + $ekGelir - $ekGider;
                                $isProfit = $finalKar >= 0;
                            @endphp

                            <div class="mt-4 p-4 rounded-4 text-white shadow-sm position-relative overflow-hidden" 
                                 style="background: {{ $isProfit ? 'linear-gradient(135deg, #198754, #20c997)' : 'linear-gradient(135deg, #dc3545, #ff6b6b)' }};">
                                
                                <i class="fa-solid {{ $isProfit ? 'fa-arrow-trend-up' : 'fa-arrow-trend-down' }} position-absolute text-white opacity-25" 
                                   style="font-size: 6rem; right: -20px; bottom: -20px; transform: rotate(-15deg);"></i>

                                <div class="position-relative z-1">
                                    <div class="d-flex justify-content-between align-items-end">
                                        <div>
                                            <h6 class="text-uppercase opacity-75 fw-bold mb-1">
                                                {{ $isProfit ? 'Toplam Net Kâr' : 'Toplam Zarar' }}
                                            </h6>
                                            <h2 class="display-5 fw-bold mb-0">
                                                {{ number_format($finalKar, 2, ',', '.') }} ₺
                                            </h2>
                                            
                                            {{-- Eski Kâr Bilgisi (Eğer değişiklik varsa göster) --}}
                                            @if($ekGelir > 0 || $ekGider > 0)
                                                <small class="opacity-50 mt-1 d-block">
                                                    (Ürün Kârı: {{ number_format($baseProfit, 2) }} ₺ 
                                                    @if($ekGelir > 0) <span class="text-white fw-bold">+{{ number_format($ekGelir,0) }}₺</span> @endif
                                                    @if($ekGider > 0) <span class="text-white fw-bold">-{{ number_format($ekGider,0) }}₺</span> @endif
                                                    )
                                                </small>
                                            @endif
                                        </div>
                                         @if(isset($ayar->dolar_kuru) && $ayar->dolar_kuru > 0)
                                             <div class="text-end">
                                                 @php $finalKarUSD = $finalKar / $ayar->dolar_kuru; @endphp
                                                 <div class="fs-4 fw-bold opacity-75">{{ number_format($finalKarUSD, 2) }} $</div>
                                                 <small class="opacity-50">~Döviz Karşılığı</small>
                                             </div>
                                         @endif
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-footer bg-light text-center text-muted py-3 small">
                <i class="fa-regular fa-clock me-1"></i> Hesaplama Tarihi: {{ now()->format('d.m.Y H:i') }} 
            </div>
        </div>

    </div>
</div>

{{-- 🔥 EKSTRA EKLEME MODALI --}}
<div class="modal fade" id="detayEkstraModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content border-0 shadow rounded-4">
            <div class="modal-header border-0 pb-0">
                <h6 class="modal-title fw-bold">Ekstra İşlem (#{{ $urun->SiparisID }})</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('siparis.ekstraEkle') }}" method="POST">
                    @csrf
                    <input type="hidden" name="siparis_id" value="{{ $urun->SiparisID }}">
                    
                    <div class="btn-group w-100 mb-3" role="group">
                        <input type="radio" class="btn-check" name="tur" id="detayGelir" value="GELIR">
                        <label class="btn btn-outline-success" for="detayGelir"><i class="fa-solid fa-plus me-1"></i>Gelir</label>
                      
                        <input type="radio" class="btn-check" name="tur" id="detayGider" value="GIDER" checked>
                        <label class="btn btn-outline-danger" for="detayGider"><i class="fa-solid fa-minus me-1"></i>Gider</label>
                    </div>

                    {{-- Para Birimi Seçimi --}}
                    <div class="mb-3">
                        <label class="form-label small text-muted d-block">Para Birimi</label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="para_birimi" id="pbTL" value="TL" {{ (!isset($sonuclar['etsy']) || !$sonuclar['etsy']) ? 'checked' : '' }} onchange="setCurrency('TL')">
                            <label class="btn btn-outline-secondary btn-sm" for="pbTL">₺ Türk Lirası</label>

                            <input type="radio" class="btn-check" name="para_birimi" id="pbUSD" value="USD" {{ (isset($sonuclar['etsy']) && $sonuclar['etsy']) ? 'checked' : '' }} onchange="setCurrency('USD')">
                            <label class="btn btn-outline-secondary btn-sm" for="pbUSD">$ Amerikan Doları</label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Tutar</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0" id="currencySymbol">
                                {{ (isset($sonuclar['etsy']) && $sonuclar['etsy']) ? '$' : '₺' }}
                            </span>
                            <input type="number" step="0.01" name="tutar" class="form-control border-start-0" placeholder="0.00" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-muted">Açıklama</label>
                        <textarea name="aciklama" class="form-control" rows="2" placeholder="Örn: Kargo farkı..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-dark w-100 fw-semibold">Kaydet</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function setCurrency(type) {
        const symbol = type === 'TL' ? '₺' : '$';
        document.getElementById('currencySymbol').innerText = symbol;
    }
</script>

<style>
    .ls-1 { letter-spacing: 1px; }
    .x-small { font-size: 0.75rem; }
</style>
@endsection