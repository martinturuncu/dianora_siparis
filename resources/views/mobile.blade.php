@extends('layouts.mobile')

@section('title', 'Mobil Panel')

@section('content')
<div class="container py-3">
    {{-- Filtreler --}}
    <div class="d-flex gap-1 mb-3 overflow-auto pb-2 no-scrollbar" style="white-space: nowrap;">
        <a href="?period=today" class="btn btn-sm {{ $period == 'today' ? 'btn-dark' : 'btn-outline-dark' }} rounded-pill px-3">Bugün</a>
        <a href="?period=week" class="btn btn-sm {{ $period == 'week' ? 'btn-dark' : 'btn-outline-dark' }} rounded-pill px-3">Bu Hafta</a>
        <a href="?period=month" class="btn btn-sm {{ $period == 'month' ? 'btn-dark' : 'btn-outline-dark' }} rounded-pill px-3">Bu Ay</a>
        <a href="?period=last30" class="btn btn-sm {{ $period == 'last30' ? 'btn-dark' : 'btn-outline-dark' }} rounded-pill px-3">Son 30 Gün</a>
    </div>

    {{-- Üst Özet Kartları --}}
    <div class="row g-2 mb-4">
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 bg-primary text-white">
                <div class="small opacity-75">Ciro ({{ match($period) { 'week'=>'Hafta', 'month'=>'Ay', 'last30'=>'30 Gün', default=>'Gün' } }})</div>
                <div class="fs-4 fw-bold">{{ number_format($gunlukCiro, 2, ',', '.') }} ₺</div>
            </div>
        </div>
        <div class="col-6">
            <div class="card border-0 shadow-sm rounded-4 p-3 h-100 {{ $gunlukKar >= 0 ? 'bg-success' : 'bg-danger' }} text-white">
                <div class="small opacity-75">Kâr ({{ match($period) { 'week'=>'Hafta', 'month'=>'Ay', 'last30'=>'30 Gün', default=>'Gün' } }})</div>
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
                <a href="{{ route('siparis.guncelleVeKar') }}" class="btn btn-warning rounded-pill px-3 fw-bold" onclick="handleSyncAndUpdate(event, this)">
                    <i class="fa-solid fa-sync me-1"></i> Güncelle
                </a>
            </div>
        </div>
    </div>

    {{-- Sipariş Listesi --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h6 class="fw-bold mb-0">Son Siparişler</h6>
        <a href="{{ route('siparisler.index') }}" class="small text-decoration-none">Tümü Gör</a>
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

{{-- LIGHTWEIGHT LOADING OVERLAY --}}
<div id="loadingOverlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:99999; justify-content:center; align-items:center; backdrop-filter: blur(5px);">
    <div style="background:var(--bg-card, #fff); border-radius:1.5rem; padding:2rem; text-align:center; max-width:400px; width:85%; box-shadow:0 25px 50px rgba(0,0,0,0.5);">
        <div id="overlayContent">
            <div class="spinner-border text-primary mb-4" style="width: 3rem; height: 3rem;" role="status">
                <span class="visually-hidden">Yükleniyor...</span>
            </div>
            <h5 class="fw-bold mb-2">Siparişler Güncelleniyor</h5>
            <p class="small text-muted mb-0">Pazaryerlerinden yeni veriler çekiliyor, lütfen sayfayı kapatmayın...</p>
        </div>
    </div>
</div>

<script>
    function showOverlay(title, subtitle) {
        var overlay = document.getElementById('loadingOverlay');
        var content = document.getElementById('overlayContent');
        content.innerHTML = '<div class="spinner-border text-primary mb-4" style="width:3rem;height:3rem;" role="status"><span class="visually-hidden">Yükleniyor...</span></div>' +
            '<h5 class="fw-bold mb-2">' + title + '</h5>' +
            '<p class="small text-muted mb-0">' + subtitle + '</p>';
        overlay.style.display = 'flex';
    }

    window.handleSyncAndUpdate = function(event, element) {
        event.preventDefault();
        showOverlay('Siparişler Çekiliyor', 'Pazaryerlerinden yeni siparişler alınıyor ve kâr analizleri yapılıyor...');

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
            if(data && data.success === false) throw new Error(data.message || 'Hata oluştu.');

            showOverlay('Siparişler Senkronize Ediliyor', 'Yeni veriler merkez sunucuya aktarılıyor...');
            
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
            var content = document.getElementById('overlayContent');
            content.innerHTML = '<div class="mb-3 text-success"><i class="fa-solid fa-circle-check" style="font-size:4rem;"></i></div>' +
                '<h5 class="fw-bold text-success mb-0">İşlem Tamamlandı</h5>';
            setTimeout(function() { window.location.reload(); }, 1000);
        })
        .catch(function(error) {
            var content = document.getElementById('overlayContent');
            content.innerHTML = '<div class="mb-3 text-danger"><i class="fa-solid fa-circle-xmark" style="font-size:4rem;"></i></div>' +
                '<h5 class="fw-bold text-danger mb-2">Hata Oluştu</h5>' +
                '<p class="x-small text-muted mb-3">' + error.message + '</p>' +
                '<button type="button" class="btn btn-secondary btn-sm px-4 rounded-pill" onclick="window.location.reload();">Kapat</button>';
        });
    };
</script>

<style>
    .x-small { font-size: 0.7rem; }
    .hover-shadow:hover { background-color: var(--bg-body); }
    body.dark-mode .text-dark { color: #fff !important; }
</style>
@endsection
