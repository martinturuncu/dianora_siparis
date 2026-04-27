@extends('layouts.app')

@section('title', 'Aylık Net Kazanç')

@section('content')
@php
    $verilerCol = collect($veriler);
    $toplamCiro = $verilerCol->sum('ciro');
    $toplamNet = $verilerCol->sum('net_kalan');
    $toplamAdet = $verilerCol->sum('adet');
    $toplamReklamButcesi = $verilerCol->sum('reklam_payi');
    $toplamReklamGideri = $verilerCol->sum('reklam_gideri_toplam');
@endphp

<div class="container-fluid px-4 pt-2 pb-4">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-gray-800 mb-0">Finansal Performans</h1>
            <p class="text-muted small mb-0">Aylık net kazanç ve reklam gideri takibi.</p>
        </div>
        <div class="d-flex align-items-center gap-3">
            {{-- Yıl Filtresi --}}
            <div class="btn-group shadow-sm" role="group">
                <a href="{{ route('aylik_net.index', ['yil' => 'all']) }}" 
                   class="btn btn-sm {{ $secilenYil == 'all' ? 'btn-primary' : 'btn-light border text-secondary' }} fw-medium px-3">
                    Tüm Zamanlar
                </a>
                @foreach($mevcutYillar as $yil)
                    <a href="{{ route('aylik_net.index', ['yil' => $yil]) }}" 
                       class="btn btn-sm {{ $secilenYil == $yil ? 'btn-primary' : 'btn-light border text-secondary' }} fw-medium px-3">
                        {{ $yil }}
                    </a>
                @endforeach
            </div>

           <span class="badge bg-white text-secondary border shadow-sm fw-medium px-3 py-2 d-none d-md-block">
                <i class="fa-solid fa-rotate me-2"></i>Veriler Güncel
           </span>
        </div>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="row g-3 mb-4">
         {{-- Ciro --}}
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-5 h-100 border-start border-4 border-primary">
                <div class="card-body py-3">
                    <div class="text-uppercase small fw-bold text-primary mb-1">Toplam Ciro</div>
                    <div class="h3 mb-0 fw-bold text-gray-800">{{ number_format($toplamCiro, 2, ',', '.') }} ₺</div>
                    <div class="small text-muted mt-2">
                        <i class="fa-solid fa-basket-shopping me-1"></i> {{ $toplamAdet }} Adet Satış
                    </div>
                </div>
            </div>
        </div>
         {{-- Net Kar --}}
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-5 h-100 border-start border-4 border-success">
                <div class="card-body py-3">
                    <div class="text-uppercase small fw-bold text-success mb-1">Toplam Net Kâr</div>
                    <div class="h3 mb-0 fw-bold text-gray-800">{{ number_format($toplamNet, 2, ',', '.') }} ₺</div>
                    <div class="small text-muted mt-2">
                        @if($toplamCiro > 0)
                            <i class="fa-solid fa-percent me-1"></i> %{{ number_format(($toplamNet / $toplamCiro) * 100, 1) }} Marj
                        @else
                            -
                        @endif
                    </div>
                </div>
            </div>
        </div>
         {{-- Reklam Butcesi --}}
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-5 h-100 border-start border-4 border-info">
                <div class="card-body py-3">
                    <div class="text-uppercase small fw-bold text-info mb-1">Top. Reklam Havuzu</div>
                    <div class="h3 mb-0 fw-bold text-gray-800">{{ number_format($toplamReklamButcesi, 2, ',', '.') }} ₺</div>
                    <div class="small text-muted mt-2">
                        Ürünlerden ayrılan pay
                    </div>
                </div>
            </div>
        </div>
         {{-- Reklam Gideri --}}
        <div class="col-12 col-md-3">
            <div class="card border-0 shadow-sm rounded-5 h-100 border-start border-4 border-danger">
                <div class="card-body py-3">
                    <div class="text-uppercase small fw-bold text-danger mb-1">Top. Reklam Gideri</div>
                    <div class="h3 mb-0 fw-bold text-gray-800">{{ number_format($toplamReklamGideri, 2, ',', '.') }} ₺</div>
                    <div class="small text-muted mt-2">
                        Google + Meta Harcamaları
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- DATA TABLE --}}
    <div class="card shadow-sm border-0 rounded-5 overflow-hidden">
        <div class="card-header bg-white py-3 border-0">
            <h6 class="m-0 fw-bold text-secondary">Aylık Detay Tablosu (Sonuçlar)</h6>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light text-uppercase small text-muted">
                    <tr>
                        <th class="ps-4" style="width: 12%;">Dönem</th>
                        <th class="text-end" style="width: 10%;">Satış / Adet</th>
                        <th class="text-end" style="width: 12%;">Ciro</th>
                        <th class="text-end text-success" style="width: 11%;">Ürün Kârı</th>
                        <th class="text-end text-primary" style="width: 11%;">Reklam Payı</th>
                        <th class="text-center" style="width: 18%;">Reklam Giderleri (Google / Meta)</th>
                        <th class="text-end" style="width: 13%;">Net Kalan</th>
                        <th class="text-end pe-4" style="width: 13%;">SON TOTAL</th>
                    </tr>
                </thead>
                <tbody class="border-top-0">
                    @forelse($veriler as $veri)
                        @php $net = $veri['net_kalan']; @endphp
                        <tr>
                            {{-- Donem --}}
                            <td class="ps-4 fw-bold text-dark">
                                {{ strtoupper($veri['tarih_format']) }}
                            </td>
                            
                            {{-- Satis Adedi --}}
                            <td class="text-end">
                                <span class="badge bg-secondary bg-opacity-10 text-secondary rounded-pill px-3">
                                    {{ $veri['adet'] }} Adet
                                </span>
                                @if(isset($veri['hediye_adedi']) && $veri['hediye_adedi'] > 0)
                                    <div class="x-small text-muted mt-1 opacity-75">
                                        +{{ $veri['hediye_adedi'] }} Hediye
                                    </div>
                                @endif
                            </td>

                            {{-- Ciro --}}
                            <td class="text-end font-monospace fw-bold text-secondary fs-6">
                                {{ number_format($veri['ciro'], 2, ',', '.') }} ₺
                            </td>

                            {{-- Urun Kari --}}
                            <td class="text-end font-monospace text-success fw-bold">
                                {{ number_format($veri['urun_kari'], 2, ',', '.') }} ₺
                            </td>

                            {{-- Reklam Payi --}}
                            {{-- Reklam Payi --}}
                            <td class="text-end font-monospace text-primary fw-bold">
                                +{{ number_format($veri['reklam_payi'], 2, ',', '.') }} ₺
                            </td>

                            {{-- Reklam INPUTS --}}
                            <td class="py-2">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    {{-- Google --}}
                                    <div class="input-group input-group-sm" style="width: 110px;">
                                        <span class="input-group-text bg-white border-danger text-danger border-end-0 ps-2 pe-1" style="font-size: 0.75rem;">
                                            <i class="fa-brands fa-google"></i>
                                        </span>
                                        <input type="number" step="0.01" 
                                               class="form-control border-danger border-start-0 text-center fw-bold text-danger f-input"
                                               data-yil="{{ $veri['yil'] }}"
                                               data-ay="{{ $veri['ay'] }}"
                                               data-tip="google"
                                               value="{{ number_format($veri['reklam_google'], 2, '.', '') }}"
                                               placeholder="0"
                                               onfocus="this.select()"
                                               onchange="giderKaydet(this)">
                                    </div>

                                    {{-- Meta --}}
                                    <div class="input-group input-group-sm" style="width: 110px;">
                                        <span class="input-group-text bg-white border-primary text-primary border-end-0 ps-2 pe-1" style="font-size: 0.8rem;">
                                            <i class="fa-brands fa-meta"></i>
                                        </span>
                                        <input type="number" step="0.01" 
                                               class="form-control border-primary border-start-0 text-center fw-bold text-primary f-input"
                                               data-yil="{{ $veri['yil'] }}"
                                               data-ay="{{ $veri['ay'] }}"
                                               data-tip="meta"
                                               value="{{ number_format($veri['reklam_meta'], 2, '.', '') }}"
                                               placeholder="0"
                                               onfocus="this.select()"
                                               onchange="giderKaydet(this)">
                                    </div>
                                </div>
                                <div class="text-center x-small text-muted mt-1">
                                    Toplam Gider: <span class="fw-bold text-danger">-{{ number_format($veri['reklam_gideri_toplam'], 2, ',', '.') }} ₺</span>
                                </div>
                            </td>

                            {{-- Net Kalan --}}
                            <td class="text-end">
                                <div class="d-flex flex-column align-items-end">
                                    <span class="fw-bold fs-6 font-monospace {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ number_format($net, 2, ',', '.') }} ₺
                                    </span>
                                    @if(isset($veri['iscilik_payi']) && $veri['iscilik_payi'] > 0)
                                        <div class="x-small text-success mt-1 opacity-75" title="İşçilik">
                                            +{{ number_format($veri['iscilik_payi'], 2, ',', '.') }} ₺ (İşç)
                                        </div>
                                    @endif
                                </div>
                            </td>

                            {{-- SON TOTAL --}}
                            <td class="text-end pe-4">
                                @php 
                                    $sonTotal = $net + ($veri['iscilik_payi'] ?? 0);
                                @endphp
                                <span class="fw-bold fs-5 font-monospace {{ $sonTotal >= 0 ? 'text-success' : 'text-primary' }}" style="letter-spacing: -0.5px;">
                                    {{ number_format($sonTotal, 2, ',', '.') }} ₺
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                Kayıtlı veri bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    
    {{-- FOOTER INFO --}}
    <div class="mt-3 text-end text-muted x-small">
        <i class="fa-solid fa-circle-info me-1"></i>
        Net Kalan = (Ürün Kârı + Reklam Payı) - (Google + Meta Giderleri)
    </div>

</div>

<style>
    .f-input:focus { box-shadow: none; background-color: #fff8f8; }
    /* Spinner remover */
    input[type=number]::-webkit-inner-spin-button, input[type=number]::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .x-small { font-size: 0.75rem; }
</style>

<script>
    async function giderKaydet(input) {
        const yil = input.getAttribute('data-yil');
        const ay = input.getAttribute('data-ay');
        const tip = input.getAttribute('data-tip');
        const val = input.value;
        const orgVal = input.getAttribute('value');
        
        // Visual indicator
        input.style.opacity = '0.5';
        
        try {
            const fd = new FormData();
            fd.append('_token', '{{ csrf_token() }}');
            fd.append('yil', yil);
            fd.append('ay', ay);
            fd.append('tip', tip);
            fd.append('deger', val);
            
            const res = await fetch("{{ route('aylik_net.update') }}", { method: 'POST', body: fd });
            const data = await res.json();
            
            if(data.status) {
                // Short success flash then reload
                input.style.opacity = '1';
                input.style.backgroundColor = '#d1e7dd';
                setTimeout(() => location.reload(), 300);
            } else {
                throw new Error('Save failed');
            }
        } catch (err) {
            console.error(err);
            alert('Hata!');
            input.value = orgVal;
            input.style.opacity = '1';
        }
    }
</script>
@endsection
