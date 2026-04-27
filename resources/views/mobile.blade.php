@extends('layouts.mobile')

@section('title', 'Mobil Panel')

@section('content')
<div class="container py-3">
    {{-- Üst Özet Kartları --}}
    <div class="row g-2 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-primary text-white">
                <div class="small opacity-75">Günlük Ciro</div>
                <div class="fs-4 fw-bold">{{ number_format($gunlukCiro, 2, ',', '.') }} ₺</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 {{ $gunlukKar >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                <div class="small opacity-75">Günlük Kâr</div>
                <div class="fs-4 fw-bold">{{ number_format($gunlukKar, 2, ',', '.') }} ₺</div>
            </div>
        </div>
        <div class="col-12 mt-2">
            <div class="card border-0 shadow-sm rounded-4 p-3 bg-dark text-white d-flex flex-row justify-content-between align-items-center">
                <div class="d-flex gap-3">
                    <div>
                        <div class="small opacity-75">Sipariş</div>
                        <div class="fs-5 fw-bold">{{ $gunlukToplam }} Adet</div>
                    </div>
                    <div class="vr opacity-25"></div>
                    <div>
                        <div class="small opacity-75">Ürün</div>
                        <div class="fs-5 fw-bold">{{ $gunlukUrunAdedi }} Adet</div>
                    </div>
                </div>
                <a href="{{ route('siparis.guncelleVeKar') }}" class="btn btn-warning rounded-pill px-3 fw-bold">
                    <i class="fa-solid fa-sync me-1"></i> Güncelle
                </a>
            </div>
        </div>
    </div>

    {{-- Sipariş Listesi --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Son Siparişler</h6>
        <a href="{{ route('siparisler.index') }}" class="small text-decoration-none">Tümünü Gör</a>
    </div>

    <div class="d-flex flex-column gap-2">
        @foreach($siparisler as $siparis)
            <a href="{{ route('siparis.show', $siparis->SiparisID) }}" class="card border-0 shadow-sm rounded-4 p-3 text-decoration-none text-dark hover-shadow transition-all">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="fw-bold small">{{ $siparis->AdiSoyadi }}</div>
                        <div class="x-small text-muted mb-1">#{{ $siparis->SiparisID }} • {{ \Carbon\Carbon::parse($siparis->Tarih)->format('H:i') }} • {{ $siparis->UrunAdedi }} Ürün</div>
                        @php
                            $status = match((int)$siparis->SiparisDurumu) {
                                0 => ['label' => 'Hazır.', 'color' => 'warning'],
                                5 => ['label' => 'Üret.', 'color' => 'primary'],
                                6 => ['label' => 'Kargo', 'color' => 'success'],
                                8 => ['label' => 'İptal', 'color' => 'danger'],
                                default => ['label' => '?', 'color' => 'secondary']
                            };
                        @endphp
                        <span class="badge bg-{{ $status['color'] }} bg-opacity-10 text-{{ $status['color'] }} x-small px-2 py-1 rounded-pill">
                            {{ $status['label'] }}
                        </span>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold small text-primary mb-1">{{ number_format($siparis->ToplamTutar, 2, ',', '.') }} ₺</div>
                        <div class="x-small fw-bold {{ $siparis->SiparisKar > 0 ? 'text-success' : 'text-danger' }}">
                            {{ $siparis->SiparisKar > 0 ? '+' : '' }}{{ number_format($siparis->SiparisKar, 2, ',', '.') }} ₺
                        </div>
                    </div>
                </div>
            </a>
        @endforeach
    </div>
</div>

<style>
    .x-small { font-size: 0.7rem; }
    .hover-shadow:hover { background-color: var(--bg-body); }
    body.dark-mode .text-dark { color: #fff !important; }
</style>
@endsection
