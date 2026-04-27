@extends('layouts.app')

@section('title', 'Canlı Döviz ve Altın Fiyatları')

@section('content')
<div class="container pt-2 pb-4">
    
    {{-- BAŞLIK --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="fa-solid fa-money-bill-trend-up me-2 text-primary"></i>Canlı Borsa ve Altın</h4>
            <small class="text-muted">Anlık veri akışı sağlanmaktadır.</small>
        </div>
        <div class="text-end">
             <span class="badge bg-body text-secondary border px-3 py-2">
                <i class="fa-regular fa-clock me-1"></i> {{ now()->format('d.m.Y H:i') }}
             </span>
        </div>
    </div>

    <div class="row g-4">
        
        {{-- SOL SÜTUN: TL PİYASASI --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient-primary text-white py-3" style="background: linear-gradient(135deg, #4f46e5 0%, #3730a3 100%);">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-turkish-lira-sign me-2"></i>TL Piyasası</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-4 py-3">ÜRÜN CİNSİ</th>
                                <th class="text-end py-3">ALIŞ</th>
                                <th class="text-end py-3 pe-4">SATIŞ</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- USD --}}
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">
                                    <i class="fa-solid fa-dollar-sign me-2 text-success"></i>USD / TL
                                </td>
                                <td class="text-end font-monospace fs-5">
                                    {{ isset($prices['USD_TL']) ? number_format($prices['USD_TL']['buy'], 4, ',', '.') : '-' }}
                                </td>
                                <td class="text-end pe-4 font-monospace fs-5 fw-bold text-dark">
                                    {{ isset($prices['USD_TL']) ? number_format($prices['USD_TL']['sell'], 4, ',', '.') : '-' }}
                                </td>
                            </tr>

                            {{-- EUR --}}
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">
                                    <i class="fa-solid fa-euro-sign me-2 text-primary"></i>EUR / TL
                                </td>
                                <td class="text-end font-monospace fs-5">
                                    {{ isset($prices['EUR_TL']) ? number_format($prices['EUR_TL']['buy'], 4, ',', '.') : '-' }}
                                </td>
                                <td class="text-end pe-4 font-monospace fs-5 fw-bold text-dark">
                                    {{ isset($prices['EUR_TL']) ? number_format($prices['EUR_TL']['sell'], 4, ',', '.') : '-' }}
                                </td>
                            </tr>

                            {{-- HAS ALTIN --}}
                            <tr class="table-warning">
                                <td class="ps-4 fw-bold text-dark">
                                    <i class="fa-regular fa-gem me-2 text-warning"></i>HAS ALTIN (TL)
                                </td>
                                <td class="text-end font-monospace fs-5 text-dark">
                                    {{ isset($prices['HAS_ALTIN']) ? number_format($prices['HAS_ALTIN']['buy'], 2, ',', '.') : '-' }}
                                </td>
                                <td class="text-end pe-4 font-monospace fs-5 fw-bold text-dark">
                                    {{ isset($prices['HAS_ALTIN']) ? number_format($prices['HAS_ALTIN']['sell'], 2, ',', '.') : '-' }}
                                </td>
                            </tr>

                            {{-- 3. SATIR: AYIRAÇ --}}
                            <tr><td colspan="3" class="bg-body p-1"></td></tr>

                            {{-- MİLYEM HESAPLARI (TL) --}}
                            {{-- 14 AYAR (0.585) --}}
                            <tr>
                                <td class="ps-4 text-muted small">
                                    14 Ayar <span class="badge bg-secondary opacity-50 ms-1">0.585</span>
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['buy'] * 0.585, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                                <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['sell'] * 0.585, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                            </tr>

                            {{-- 18 AYAR (0.750) --}}
                            <tr>
                                <td class="ps-4 text-muted small">
                                    18 Ayar <span class="badge bg-secondary opacity-50 ms-1">0.750</span>
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['buy'] * 0.750, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                                <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['sell'] * 0.750, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                            </tr>

                            {{-- 21 AYAR (0.875) --}}
                            <tr>
                                <td class="ps-4 text-muted small">
                                    21 Ayar <span class="badge bg-secondary opacity-50 ms-1">0.875</span>
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['buy'] * 0.875, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                                <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['sell'] * 0.875, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                            </tr>
                            
                            {{-- 22 AYAR (0.916) --}}
                            <tr>
                                <td class="ps-4 text-muted small">
                                    22 Ayar <span class="badge bg-secondary opacity-50 ms-1">0.916</span>
                                </td>
                                <td class="text-end font-monospace text-muted small">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['buy'] * 0.916, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                                <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                    @if(isset($prices['HAS_ALTIN']))
                                        {{ number_format($prices['HAS_ALTIN']['sell'] * 0.916, 2, ',', '.') }}
                                    @else - @endif
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- SAĞ SÜTUN: USD PİYASASI --}}
        <div class="col-lg-6">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-gradient-success text-white py-3" style="background: linear-gradient(135deg, #059669 0%, #047857 100%);">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-earth-americas me-2"></i>USD Piyasası</h5>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-body">
                            <tr class="text-secondary small fw-bold">
                                <th class="ps-4 py-3">ÜRÜN CİNSİ</th>
                                <th class="text-end py-3">ALIŞ ($)</th>
                                <th class="text-end py-3 pe-4">SATIŞ ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            
                            {{-- USD/KG --}}
                            <tr class="table-success">
                                <td class="ps-4 fw-bold text-dark">
                                    <i class="fa-solid fa-scale-balanced me-2 text-success"></i>USD / KG
                                </td>
                                <td class="text-end font-monospace fs-5 text-dark">
                                    {{ isset($prices['USD_KG']) ? number_format($prices['USD_KG']['buy'], 2, ',', '.') : '-' }}
                                </td>
                                <td class="text-end pe-4 font-monospace fs-5 fw-bold text-dark">
                                    {{ isset($prices['USD_KG']) ? number_format($prices['USD_KG']['sell'], 2, ',', '.') : '-' }}
                                </td>
                            </tr>

                             {{-- 3. SATIR: AYIRAÇ --}}
                             <tr><td colspan="3" class="bg-body p-1"></td></tr>

                             {{-- MİLYEM HESAPLARI (USD) --}}
                             {{-- 14 AYAR (0.585) --}}
                             <tr>
                                 <td class="ps-4 text-muted small">
                                     14 Ayar (USD) <span class="badge bg-secondary opacity-50 ms-1">0.585</span>
                                 </td>
                                 <td class="text-end font-monospace text-muted small">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['buy'] * 0.585, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                                 <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['sell'] * 0.585, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                             </tr>
 
                             {{-- 18 AYAR (0.750) --}}
                             <tr>
                                 <td class="ps-4 text-muted small">
                                     18 Ayar (USD) <span class="badge bg-secondary opacity-50 ms-1">0.750</span>
                                 </td>
                                 <td class="text-end font-monospace text-muted small">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['buy'] * 0.750, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                                 <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['sell'] * 0.750, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                             </tr>

                             {{-- 21 AYAR (0.875) --}}
                             <tr>
                                 <td class="ps-4 text-muted small">
                                     21 Ayar (USD) <span class="badge bg-secondary opacity-50 ms-1">0.875</span>
                                 </td>
                                 <td class="text-end font-monospace text-muted small">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['buy'] * 0.875, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                                 <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['sell'] * 0.875, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                             </tr>
                             
                             {{-- 22 AYAR (0.916) --}}
                             <tr>
                                 <td class="ps-4 text-muted small">
                                     22 Ayar (USD) <span class="badge bg-secondary opacity-50 ms-1">0.916</span>
                                 </td>
                                 <td class="text-end font-monospace text-muted small">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['buy'] * 0.916, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                                 <td class="text-end pe-4 font-monospace fw-bold text-secondary">
                                     @if(isset($prices['USD_KG']))
                                         {{ number_format($prices['USD_KG']['sell'] * 0.916, 2, ',', '.') }}
                                     @else - @endif
                                 </td>
                             </tr>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
