@extends('layouts.app')

@section('title', 'Ayarlar Yönetimi')

@section('content')
<div class="container-fluid pt-2 pb-5" style="min-height: 100vh;">

    <div class="container" style="max-width: 1000px;">
        
        {{-- BAŞLIK VE TARİH SEÇİCİ --}}
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3">
            
            {{-- TARİH SEÇİCİ --}}
            <div class="bg-card p-2 rounded-pill shadow-sm border d-inline-flex align-items-center">
                <label for="ayarTarihi" class="fw-bold text-secondary ps-2 pe-2 small">AYAR TARİHİ:</label>
                <input type="date" id="ayarTarihi" class="form-control border-0 bg-transparent fw-bold text-primary" 
                       value="{{ request('tarih', now()->format('Y-m-d')) }}"
                       style="box-shadow: none !important; outline: none !important;">
            </div>

            <div class="text-center">
                <h3 class="fw-bold text-dark mb-1">⚙️ Ayarlar Merkezi</h3>
                <p class="text-muted small mb-0">Sistem genel ayarları ve pazaryeri komisyonlarını buradan yönetebilirsiniz.</p>
            </div>

            <div class="d-flex gap-2">
                <a href="{{ route('kategoriler.index') }}" class="btn btn-outline-primary flex-shrink-0">
                    <i class="fa-solid fa-percent me-2"></i>Kâr Oranları
                </a>
                <a href="{{ route('ayarlar.gecmis') }}" class="btn btn-outline-secondary flex-shrink-0">
                    <i class="fa-solid fa-clock-rotate-left me-2"></i>Geçmiş Kayıtlar
                </a>
            </div>
        </div>

        {{-- BİLDİRİMLER --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 border-start border-success border-4" role="alert">
                <i class="fa-solid fa-circle-check me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('hata'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 border-start border-danger border-4" role="alert">
                <i class="fa-solid fa-circle-exclamation me-2"></i> {{ session('hata') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- GÜZEL GÖRÜNEN BAŞLIKLAR --}}
        <div class="bg-card p-3 rounded-4 shadow-sm mb-4 border d-flex justify-content-center align-items-center">
             <h5 class="mb-0 fw-bold text-dark"><i class="fa-solid fa-sliders me-2"></i> Sistem Yapılandırması & Maliyetler</h5>
        </div>

        {{-- SEKME İÇERİKLERİ (ARTIK TEK SAYFA) --}}
        <div class="tab-content" id="settingTabsContent">

            {{-- GENEL AYARLAR --}}
            <div class="tab-pane fade show active" id="genel" role="tabpanel">
                <div class="card shadow border-0 rounded-4 overflow-hidden">
                    <div class="card-header bg-dark text-white py-3 text-center">
                        <h6 class="mb-0 fw-bold text-uppercase ls-1">Genel Yapılandırma & Pazaryeri Komisyonları</h6>
                    </div>
                    <div class="card-body p-4 p-md-5">
<form method="POST" action="/ayarlar" class="needs-validation" novalidate id="ayarForm">
    @csrf
    <input type="hidden" name="tarih" id="formTarih" value="{{ request('tarih', now()->format('Y-m-d')) }}">
    
    <div class="row g-5">
        <div class="col-md-6 position-relative">
            <h6 class="text-secondary fw-bold border-bottom pb-2 mb-4">🇹🇷 TL & Üretim Giderleri</h6>
            
            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold small text-muted">Altın Fiyatı (₺)</label>
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid"></i></span>
                        <input type="number" step="0.01" name="altin_fiyat" value="{{ $ayar->altin_fiyat }}" class="form-control border-start-0 ps-0 fw-bold text-dark">
                    </div>
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold small text-muted">Ayar</label>
                    <input type="number" name="ayar" value="{{ $ayar->ayar }}" class="form-control form-control-sm fw-bold text-center">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold small text-muted">İşçilik (₺)</label>
                <input type="number" step="0.01" name="iscilik" value="{{ $ayar->iscilik }}" class="form-control form-control-sm">
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold small text-muted">Yurtiçi Kargo (₺)</label>
                <input type="number" step="0.01" name="kargo" value="{{ $ayar->kargo }}" class="form-control form-control-sm">
            </div>

            <div class="row g-3 mt-1">
                <div class="col-6">
                    <label class="form-label fw-semibold small text-muted">Kutu + Poşet (₺)</label>
                    <input type="number" step="0.01" name="kutu" value="{{ $ayar->kutu }}" class="form-control form-control-sm">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold small text-muted">Reklam Gideri (₺)</label>
                    <input type="number" step="0.01" name="reklam" value="{{ $ayar->reklam }}" class="form-control form-control-sm">
                </div>
            </div>
            
            <div class="mt-3">
                <label class="form-label fw-semibold small text-muted">KDV Oranı (%)</label>
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" name="kdv" value="{{ $ayar->kdv }}" class="form-control">
                    <span class="input-group-text">%</span>
                </div>
            </div>

            {{-- Dikey Çizgi (Mobilde gizli) --}}
            <div class="d-none d-md-block position-absolute top-0 end-0 h-100 border-end" style="margin-right: -1.5rem;"></div>
        </div>

        <div class="col-md-6 ps-md-5">
            <h6 class="text-secondary fw-bold border-bottom pb-2 mb-4">🌍 Döviz & Pazaryeri Komisyonları</h6>

            <div class="row g-3">
                <div class="col-6">
                    <label class="form-label fw-semibold small text-muted">Dolar Kuru (₺)</label>
                    <input type="number" step="0.0001" name="dolar_kuru" value="{{ $ayar->dolar_kuru }}" class="form-control form-control-sm fw-bold border-primary border-opacity-25">
                </div>
                <div class="col-6">
                    <label class="form-label fw-semibold small text-muted">Altın ($)</label>
                    <input type="number" step="0.01" name="altin_usd" value="{{ $ayar->altin_usd }}" class="form-control form-control-sm fw-bold">
                </div>
            </div>

            <div class="mt-3">
                <label class="form-label fw-semibold small text-muted">Yurtdışı Kargo ($ veya ₺)</label>
                <input type="number" step="0.01" name="kargo_yurtdisi" value="{{ $ayar->kargo_yurtdisi }}" class="form-control form-control-sm">
            </div>

            <div class="card bg-body border-0 mt-4">
                <div class="card-body py-3 px-3">
                    <h6 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-percent me-1"></i> Pazaryeri Komisyonları (Tarihsel %)</h6>
                    <div class="row g-2">
                        <div class="col-6 mb-2">
                            <label class="form-label x-small text-muted mb-1">Site (%)</label>
                            <input type="number" step="0.1" name="komisyon_site" value="{{ $ayar->komisyon_site_display }}" class="form-control form-control-sm bg-card fw-bold text-primary">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label x-small text-muted mb-1">Trendyol (%)</label>
                            <input type="number" step="0.1" name="komisyon_trendyol" value="{{ $ayar->komisyon_trendyol_display }}" class="form-control form-control-sm bg-card fw-bold text-primary">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label x-small text-muted mb-1">Etsy (%)</label>
                            <input type="number" step="0.1" name="komisyon_etsy" value="{{ $ayar->komisyon_etsy_display }}" class="form-control form-control-sm bg-card fw-bold text-primary">
                        </div>
                        <div class="col-6 mb-2">
                            <label class="form-label x-small text-muted mb-1">Hipicon (%)</label>
                            <input type="number" step="0.1" name="komisyon_hipicon" value="{{ $ayar->komisyon_hipicon_display }}" class="form-control form-control-sm bg-card fw-bold text-primary">
                        </div>
                    </div>
                </div>
            </div>

            <div class="card bg-body border-0 mt-3">
                <div class="card-body py-3 px-3">
                    <h6 class="small fw-bold text-dark mb-3"><i class="fa-solid fa-brands fa-etsy me-1"></i> Etsy Ek Ayarlar</h6>
                    <div class="mb-2">
                        <label class="form-label x-small text-muted mb-1">Shipping Cost ($)</label>
                        <input type="number" step="0.01" name="etsy_ship_cost" value="{{ $ayar->etsy_ship_cost }}" class="form-control form-control-sm bg-card">
                    </div>
                    <div class="mb-0">
                        <label class="form-label x-small text-muted mb-1">USA Tax Rate (%)</label>
                        <input type="number" step="0.0001" name="etsy_usa_tax_rate" value="{{ $ayar->etsy_usa_tax_rate }}" class="form-control form-control-sm bg-card">
                    </div>
                </div>
            </div>

            <div class="card border-0 mt-4 shadow-sm overflow-hidden" style="border-radius: 12px; background: rgba(255,255,255,0.6); backdrop-filter: blur(10px);">
                <div class="card-header bg-gradient-gift text-white border-0 py-2 px-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 small fw-bold"><i class="fa-solid fa-gift me-2"></i>Hediye Stok Yönetimi</h6>
                        <span class="badge bg-white bg-opacity-25 x-small fw-normal">Dinamik Filtre</span>
                    </div>
                </div>
                <div class="card-body p-3">
                    <p class="text-muted x-small mb-3">
                        <i class="fa-solid fa-circle-info me-1 text-primary"></i> 
                        Buraya eklediğiniz stok kodları <b>maliyet ve kâr hesaplamalarından muaf tutulur.</b> Kodları virgülle ayırarak yazınız.
                    </p>
                    <div class="input-group input-group-sm shadow-sm">
                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-key"></i></span>
                        <input type="text" name="hediye_kodlari" value="{{ $ayar->hediye_kodlari ?? 'crmhediye' }}" 
                               class="form-control border-start-0 ps-1 fw-semibold text-dark bg-white" 
                               placeholder="crmhediye, hediye2, vs.">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4 text-muted">
    <button type="submit" class="btn btn-dark w-100 py-3 fw-bold shadow-sm rounded-3">
        <i class="fa-solid fa-check-circle me-2"></i> DEĞİŞİKLİKLERİ KAYDET
    </button>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const tarihInput = document.getElementById('ayarTarihi');
    const form = document.getElementById('ayarForm');
    const formTarihInput = document.getElementById('formTarih');

    tarihInput.addEventListener('change', function() {
        const secilenTarih = this.value;
        
        // Formun gizli tarih inputunu güncelle
        formTarihInput.value = secilenTarih;

        // API'den veriyi çek
        fetch(`/api/ayarlar/${secilenTarih}`)
            .then(response => {
                if (!response.ok) {
                    // Eğer o tarihte veya öncesinde hiç ayar yoksa formu sıfırla
                    form.reset();
                    tarihInput.value = secilenTarih; // Tarih inputunu koru
                    formTarihInput.value = secilenTarih;
                    console.warn('Bu tarih için ayar bulunamadı, form sıfırlandı.');
                    return;
                }
                return response.json();
            })
            .then(data => {
                if (data) {
                    // Formdaki her bir inputu gelen veriyle doldur
                    for (const key in data) {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            let value = data[key];
                            
                            // Komisyon alanlarını 100 ile çarp (API'den decimal geliyor, UI yüzde bekliyor)
                            if (key.startsWith('komisyon_')) {
                                value = (parseFloat(value) * 100).toFixed(1);
                            }
                            
                            // Input tipine göre doldur
                            if (input.type === 'number') {
                                input.value = value;
                            } else {
                                input.value = value || '';
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Ayarlar yüklenirken bir hata oluştu:', error);
                alert('Ayarlar yüklenemedi. Lütfen sayfayı yenileyin.');
            });
    });
});
</script>
@endpush

                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

{{-- EK CSS --}}
<style>
    .nav-pills .nav-link {
        color: #6c757d;
        transition: all 0.3s ease;
    }
    .nav-pills .nav-link.active {
        background-color: #212529 !important; /* Dark butonu */
        color: #fff !important;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .form-control:focus {
        box-shadow: 0 0 0 0.25rem rgba(33, 37, 41, 0.15);
        border-color: #212529;
    }
    .x-small {
        font-size: 0.75rem;
    }
    .ls-1 {
        letter-spacing: 1px;
    }
    .bg-gradient-gift {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    }
    .hover-shadow:hover {
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
        transform: translateY(-2px);
    }
    .transition-all {
        transition: all 0.3s ease;
    }
</style>

@endsection