@extends('layouts.app')

@section('title', 'Manuel Sipariş Ekle')

@section('content')
<div class="container py-5" style="max-width: 900px;">

    {{-- ÜST BAŞLIK --}}
    <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-file-pen text-primary me-2"></i>Manuel Sipariş Oluştur
            </h3>
            <p class="text-muted small mb-0">Sistem dışından gelen satışları buradan kaydedebilirsiniz.</p>
        </div>
        <a href="{{ route('siparisler.index') }}" class="btn btn-light border shadow-sm fw-semibold text-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Listeye Dön
        </a>
    </div>

    {{-- BİLDİRİMLER --}}
    @if(session('hata'))
        <div class="alert alert-danger shadow-sm border-0 border-start border-danger border-4 rounded-3">
            <i class="fa-solid fa-circle-exclamation me-2"></i>{{ session('hata') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-warning shadow-sm border-0 border-start border-warning border-4 rounded-3">
            <h6 class="fw-bold text-dark"><i class="fa-solid fa-triangle-exclamation me-2"></i>Lütfen bilgileri kontrol edin:</h6>
            <ul class="mb-0 ps-3 small text-dark">
                @foreach ($errors->all() as $hata)
                    <li>{{ $hata }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- FORM KARTI --}}
    <div class="card border-0 shadow-lg rounded-5 overflow-hidden">
        <div class="card-header bg-white border-bottom p-4">
            <h6 class="fw-bold text-secondary text-uppercase ls-1 mb-0">Sipariş Detayları</h6>
        </div>
        
        <div class="card-body p-4 p-md-5 bg-light bg-opacity-25">
            <form action="{{ route('siparisler.manuel.store') }}" method="POST" class="needs-validation" novalidate>
                @csrf

                {{-- BÖLÜM 1: MÜŞTERİ & PLATFORM --}}
                <div class="row g-4 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">Müşteri Adı Soyadı</label>
                        {{-- ÖZEL CSS SINIFI: merged-group --}}
                        <div class="input-group merged-group">
                            <span class="input-group-text"><i class="fa-solid fa-user text-muted"></i></span>
                            <input type="text" name="AdiSoyadi" value="{{ old('AdiSoyadi') }}" class="form-control shadow-none" placeholder="Örn: Ahmet Yılmaz" required>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold text-dark small">Satış Platformu</label>
                        <div class="input-group merged-group">
                            <span class="input-group-text"><i class="fa-solid fa-shop text-muted"></i></span>
                            <select name="PazaryeriID" id="pazaryeriSelect" class="form-select shadow-none" required>
                                <option value="">Platform Seçiniz...</option>
                                @foreach($pazaryerleri as $p)
                                    <option value="{{ $p->id }}" @selected(old('PazaryeriID') == $p->id)>
                                        {{ $p->Ad }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- DİNAMİK ALAN: USA SİPARİŞİ --}}
                <div id="usaBox" class="mb-4" style="display: none;">
                    <div class="p-3 bg-primary bg-opacity-10 border border-primary border-opacity-25 rounded-3 d-flex align-items-center shadow-sm">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="isUSA" id="isUSA" value="1" style="width: 3em; height: 1.5em; cursor: pointer;">
                            <label class="form-check-label fw-bold ms-3 text-primary" for="isUSA" style="cursor: pointer;">
                                <i class="fa-solid fa-flag-usa me-1"></i> Bu sipariş Amerika'ya (USA) gönderilecek
                            </label>
                        </div>
                    </div>
                    <div class="form-text small mt-1 ms-1 text-primary opacity-75">
                        <i class="fa-solid fa-info-circle me-1"></i>Vergi hesaplamaları USA oranlarına göre yapılır.
                    </div>
                </div>

                <hr class="border-secondary opacity-10 my-4">

                {{-- BÖLÜM 2: ÜRÜN BİLGİLERİ --}}
                {{-- Modern Başlık ve Aksiyon Alanı --}}
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-center p-3 mb-4 bg-white rounded-3 shadow-sm border border-light gap-3">
                    <div class="d-flex align-items-center">
                        <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                            <i class="fa-solid fa-box-open fs-5"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold text-dark mb-0 ls-1 text-uppercase">Ürün Bilgileri</h6>
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">Siparişe ait ürünleri giriniz.</p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-center bg-light rounded-pill p-1 ps-3 border">
                        <span class="small text-muted fw-semibold me-2 d-none d-sm-inline">Listede yok mu?</span>
                        <a href="{{ route('urunler.create') }}" target="_blank" class="btn btn-sm btn-white bg-white text-dark fw-bold rounded-pill shadow-sm border px-3 transition-hover">
                            <i class="fa-solid fa-plus text-primary me-1"></i> Yeni Tanımla
                        </a>
                    </div>
                </div>

                {{-- Ürün Başlıkları --}}
                <div class="row g-3 mb-2 px-3 d-none d-md-flex">
                    <div class="col-md-4">
                        <label class="form-label fw-bold text-muted small text-uppercase ls-1 mb-0">Stok Kodu (SKU)</label>
                    </div>
                    <div class="col-md-2 text-center">
                        <label class="form-label fw-bold text-muted small text-uppercase ls-1 mb-0">Adet</label>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small text-uppercase ls-1 mb-0">Birim Fiyat</label>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold text-muted small text-uppercase ls-1 mb-0">Toplam</label>
                    </div>
                </div>

                {{-- Ürünlerin Ekleneceği Alan --}}
                <div id="urunListesi" class="d-flex flex-column gap-3 mb-3">
                    {{-- İlk Ürün Satırı (Dinamik olarak eklenecek) --}}
                    <div class="urun-satiri p-3 border rounded-3 bg-white shadow-sm">
                        <div class="row g-3 align-items-center">
                            <div class="col-md-4">
                                <label class="form-label fw-bold text-dark small d-md-none">Stok Kodu (SKU)</label>
                                <input type="text" name="UrunKodu[]" class="form-control shadow-sm border-light bg-light bg-opacity-50" placeholder="Örn: CH061" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-bold text-dark small d-md-none">Adet</label>
                                <input type="number" name="Adet[]" min="1" value="1" class="form-control text-center fw-bold shadow-sm border-light bg-light bg-opacity-50" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-bold text-dark small d-md-none">Birim Fiyat</label>
                                <input type="text" name="BirimFiyati[]" class="form-control fw-bold birim-fiyat-input shadow-sm border-light bg-light bg-opacity-50" placeholder="0,00">
                            </div>
                            <div class="col-md-3 d-flex align-items-center">
                                <div class="flex-grow-1 me-2">
                                    <label class="form-label fw-bold text-dark small d-md-none">Toplam</label>
                                    <input type="text" name="SatisFiyati[]" class="form-control fw-bold toplam-birim-fiyat-input shadow-sm border-light bg-light bg-opacity-50" placeholder="0,00">
                                </div>
                                {{-- İlk satırda silme butonu olmaz, JS ile eklenecek --}}
                                <div class="sil-btn-container"></div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Satır Ekle Butonu --}}
                <div class="text-end mb-4">
                    <button type="button" id="urunEkleBtn" class="btn btn-sm btn-success fw-semibold rounded-pill shadow-sm px-4">
                        <i class="fa-solid fa-plus me-1"></i> Yeni Satır Ekle
                    </button>
                </div>

                <div class="mt-4 d-flex flex-column flex-md-row gap-3 align-items-end">
                    <div class="flex-grow-1">
                        <label class="form-label fw-bold text-dark small">Sipariş Tarihi</label>
                        <div class="input-group merged-group shadow-sm">
                            <span class="input-group-text"><i class="fa-regular fa-calendar-days text-muted"></i></span>
                            <input type="datetime-local" name="Tarih" class="form-control shadow-none" value="{{ now()->format('Y-m-d\TH:i') }}">
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <label class="form-label fw-bold text-dark small">Toplam Tutar</label>
                        <div class="input-group merged-group shadow-sm">
                            <input type="text" id="toplamTutar" class="form-control fw-bold fs-5 text-end shadow-none" value="0,00" readonly>
                            <span class="input-group-text fw-bold text-dark bg-white border-start-0" id="currencySymbol">₺</span>
                        </div>
                    </div>
                </div>

                {{-- AKSİYON BUTONU --}}
                <div class="d-grid mt-5">
                    <button type="submit" class="btn btn-dark py-3 fw-bold shadow rounded-3 transition-hover">
                        <i class="fa-solid fa-floppy-disk me-2"></i> SİPARİŞİ KAYDET
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

{{-- ÖZEL CSS --}}
<style>
    .ls-1 { letter-spacing: 1px; }
    .small-btn-font { font-size: 0.8rem; }

    /* --- MERGED GROUP (BİRLEŞİK KUTU) CSS --- */
    .merged-group {
        border: 1px solid #ced4da;
        border-radius: 0.5rem;
        background-color: #fff;
        overflow: hidden;
        transition: all 0.2s ease-in-out;
    }
    .merged-group:focus-within {
        border-color: #212529;
        box-shadow: 0 0 0 0.25rem rgba(33, 37, 41, 0.1);
    }
    .merged-group .form-control,
    .merged-group .form-select,
    .merged-group .input-group-text {
        border: none !important;
        box-shadow: none !important;
        background-color: transparent;
    }
    .merged-group .input-group-text {
        background-color: #fff; 
        padding-right: 10px;
    }
    .transition-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15) !important;
    }

    /* Inputlardaki gölgeyi kaldır */
    .form-control:focus, .form-select:focus {
        box-shadow: none !important;
        border-color: #212529;
    }
</style>

{{-- JAVASCRIPT --}}
<script>
document.addEventListener("DOMContentLoaded", function() {
    const dropdown = document.getElementById('pazaryeriSelect');
    const usaBox = document.getElementById('usaBox');
    const currencySymbol = document.getElementById('currencySymbol');
    const urunListesi = document.getElementById('urunListesi');

    function kontrolEt() {
        const selectedText = dropdown.options[dropdown.selectedIndex].text.toLowerCase();
        const selectedVal = dropdown.value;

        // Etsy kontrolü
        if (selectedVal == "3" || selectedText.includes("etsy")) {
            usaBox.style.display = "block";
            setTimeout(() => { usaBox.style.opacity = "1"; }, 10);
            currencySymbol.innerText = "$"; 
        } else {
            usaBox.style.display = "none";
            usaBox.style.opacity = "0";
            currencySymbol.innerText = "₺"; 
            document.querySelectorAll('#toplamTutar ~ #currencySymbol').forEach(el => el.innerText = '₺');
        }

        hesaplaToplam();
    }

    dropdown.addEventListener("change", kontrolEt);
    kontrolEt();

    // --- YENİ: ÇOKLU ÜRÜN EKLEME ---
    const urunEkleBtn = document.getElementById('urunEkleBtn');
    // İlk satırı klonla ama içindeki input değerlerini temizle
    let ilkUrunSatiri = urunListesi.querySelector('.urun-satiri').cloneNode(true);
    ilkUrunSatiri.querySelectorAll('input').forEach(input => input.value = '');
    ilkUrunSatiri.querySelector('input[name="Adet[]"]').value = '1';

    // Helper function to format numbers
    function formatNumber(num) {
        return num.toFixed(2).replace('.', ',');
    }

    urunEkleBtn.addEventListener('click', function() {
        const yeniSatir = ilkUrunSatiri.cloneNode(true);
        
        // Silme butonu ekle
        const silmeButonuContainer = yeniSatir.querySelector('.sil-btn-container');
        silmeButonuContainer.innerHTML = `<button type="button" class="btn btn-sm btn-outline-danger sil-btn shadow-sm" title="Satırı Sil"><i class="fa-solid fa-trash-can"></i></button>`;
        
        urunListesi.appendChild(yeniSatir);
        hesaplaToplam(); 
    });

    // Silme butonu (Event Delegation)
    urunListesi.addEventListener('click', function(e) {
        if (e.target.closest('.sil-btn')) {
            e.target.closest('.urun-satiri').remove();
            hesaplaToplam();
        }
    });

    // Input değişikliklerini dinle (Event Delegation)
    urunListesi.addEventListener('input', function(e) {
        const target = e.target;
        const satir = target.closest('.urun-satiri');
        if (!satir) return;

        const adetInput = satir.querySelector('input[name="Adet[]"]');
        const birimFiyatInput = satir.querySelector('.birim-fiyat-input');
        const toplamBirimFiyatInput = satir.querySelector('.toplam-birim-fiyat-input');

        if (target === adetInput || target === birimFiyatInput) { 
            const adet = parseFloat(adetInput.value.replace(',', '.')) || 0;
            const birimFiyat = parseFloat(birimFiyatInput.value.replace(',', '.')) || 0;
            toplamBirimFiyatInput.value = (adet * birimFiyat > 0) ? formatNumber(adet * birimFiyat) : '';
            hesaplaToplam();
        } else if (target === toplamBirimFiyatInput) { 
            const adet = parseFloat(adetInput.value.replace(',', '.')) || 0;
            const toplam = parseFloat(toplamBirimFiyatInput.value.replace(',', '.')) || 0;
            if (adet > 0 && toplam > 0) {
                birimFiyatInput.value = formatNumber(toplam / adet);
            } else {
                birimFiyatInput.value = '';
            }
            hesaplaToplam();
        }
    });

    // Toplam tutarı hesaplama
    function hesaplaToplam() {
        let toplam = 0;
        urunListesi.querySelectorAll('.urun-satiri').forEach(satir => {
            const satirToplami = parseFloat(satir.querySelector('.toplam-birim-fiyat-input').value.replace(',', '.')) || 0;
            toplam += satirToplami;
        });
        document.getElementById('toplamTutar').value = formatNumber(toplam);
    }

    hesaplaToplam();
});
</script>
@endsection