@extends('layouts.app')

@section('title', 'Kâr Oranları Yönetimi')

@section('content')
<div class="bg-light min-vh-100 pb-5 position-relative">
    
    {{-- 1. ÜST DEKORATİF ALAN (Siyah Header) --}}
    {{-- pt-4'ü kaldırdım, daha yukarı aldım --}}
    <div class="bg-light w-100" style="height: 260px; border-bottom-left-radius: 30px; border-bottom-right-radius: 30px;">
        <div class="container pt-2 pb-3" style="max-width: 900px;">
            
            {{-- Başlık --}}
            <div class="d-flex justify-content-between align-items-start text-black mb-3">
                <div>
                    <h2 class="fw-bold mb-1">Kâr Oranları</h2>
                    <p class="text-black-50 mb-0 small">Kategorilere özel kâr marjlarını yönetin.</p>
                </div>
                <div class="d-none d-md-block opacity-25">
                    <i class="fa-solid fa-percent fa-3x"></i>
                </div>
            </div>

            {{-- Arama Kutusu --}}
            <div class="position-relative">
                <input type="text" id="searchInput" 
                       class="form-control form-control-lg border-0 shadow-lg rounded-pill ps-5" 
                       style="height: 55px; font-size: 1rem;"
                       placeholder="Kategori adı, kodu veya ID ile arayın..." 
                       autocomplete="off">
                <i class="fa-solid fa-magnifying-glass position-absolute top-50 start-0 translate-middle-y ms-4 text-muted fs-5"></i>
            </div>
        </div>
    </div>

    {{-- 2. LİSTE ALANI (Negatif margin ile yukarı çekme) --}}
    <div class="container position-relative" style="max-width: 900px; margin-top: -50px;">

        {{-- Mesajlar --}}
        @if(session('basari'))
            <div class="alert alert-success border-0 shadow rounded-3 d-flex align-items-center mb-4 bg-white">
                <i class="fa-solid fa-circle-check fs-4 me-3 text-success"></i>
                <div class="fw-medium">{{ session('basari') }}</div>
                <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <form method="POST" action="{{ route('kategoriler.guncelle') }}" id="marginForm">
            @csrf
            
            <div class="d-flex flex-column gap-3" id="categoryList">
                @foreach($kategoriler as $kategori)
                    @php
                        $colors = ['primary', 'success', 'danger', 'warning', 'info', 'dark'];
                        $color = $colors[$kategori->Id % count($colors)];
                        $initial = mb_substr($kategori->KategoriAdi, 0, 1);
                    @endphp

                    {{-- KART --}}
                    <div class="category-card card border-0 shadow-sm rounded-4 overflow-hidden bg-white transition-hover" 
                         data-name="{{ strtolower($kategori->KategoriAdi) }}" 
                         data-code="{{ strtolower($kategori->KategoriKodu) }}"
                         data-id="{{ $kategori->Id }}">
                        
                        <div class="card-body p-3 d-flex align-items-center gap-3">
                            
                            {{-- A. İKON --}}
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-{{ $color }} bg-opacity-10 text-{{ $color }} fw-bold fs-5 flex-shrink-0" 
                                 style="width: 48px; height: 48px;">
                                {{ $initial }}
                            </div>

                            {{-- B. BİLGİLER --}}
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="d-flex align-items-baseline gap-2 mb-1">
                                    <span class="text-muted small fw-bold">#{{ $kategori->Id }}</span>
                                    <h6 class="mb-0 fw-bold text-dark text-truncate" style="font-size: 1.05rem;">
                                        {{ $kategori->KategoriAdi }}
                                    </h6>
                                </div>
                                
                                {{-- Sadece Kod Rozeti Kaldı ('Varsayılan Oran' yazısı silindi) --}}
                                <div>
                                    <span class="badge bg-light text-secondary border border-secondary border-opacity-25 font-monospace rounded-2" style="font-size: 0.7rem;">
                                        {{ $kategori->KategoriKodu }}
                                    </span>
                                </div>
                            </div>

                            {{-- C. INPUT ALANI --}}
                            <div class="position-relative" style="width: 130px;">
                                <input type="number" 
                                       step="0.1" 
                                       min="0" 
                                       name="kategoriler[{{ $kategori->Id }}]" 
                                       value="{{ $kategori->KarOrani }}" 
                                       class="form-control form-control-lg border bg-light text-end fw-bold pe-5 profit-input" 
                                       placeholder="0"
                                       data-original="{{ $kategori->KarOrani }}"
                                       style="font-size: 1.1rem;">
                                <span class="position-absolute top-50 end-0 translate-middle-y pe-3 text-muted fw-bold bg-transparent">%</span>
                            </div>

                        </div>
                        
                        <div class="change-indicator bg-warning position-absolute bottom-0 start-0 w-0" style="height: 4px; transition: width 0.3s;"></div>
                    </div>
                @endforeach
            </div>

            {{-- BOŞ DURUM --}}
            <div id="noResults" class="text-center py-5 d-none">
                <div class="mb-3 opacity-25">
                    <i class="fa-solid fa-magnifying-glass fa-3x"></i>
                </div>
                <h5 class="text-muted fw-semibold">Eşleşen kategori bulunamadı</h5>
            </div>

            {{-- FLOATING ACTION BAR --}}
            <div class="fixed-bottom p-3" style="pointer-events: none; z-index: 1050;">
                <div class="container" style="max-width: 900px;">
                    <div class="card border-0 shadow-lg rounded-pill bg-dark text-white p-2 mx-auto float-bar" 
                         style="pointer-events: auto; transform: translateY(150%); transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);">
                        
                        <div class="d-flex align-items-center justify-content-between ps-3 pe-1">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning text-dark rounded-circle fw-bold d-flex align-items-center justify-content-center me-2" 
                                     style="width: 24px; height: 24px; font-size: 0.8rem;" id="changeCount">0</div>
                                <span class="fw-medium small">Değişiklik yapıldı</span>
                            </div>

                            <button type="submit" class="btn btn-light rounded-pill fw-bold px-4 py-2 shadow-sm">
                                <i class="fa-solid fa-check me-2"></i> KAYDET
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </form>
    </div>
</div>

{{-- CSS --}}
<style>
    .transition-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .category-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.08) !important; }
    
    .profit-input:focus { 
        background-color: #fff !important; 
        border-color: #212529 !important;
        box-shadow: none !important;
        color: #000;
    }

    .category-card.modified { background-color: #fffdf5 !important; border: 1px solid #ffeeba !important; }
    .category-card.modified .change-indicator { width: 100% !important; }
</style>

{{-- JAVASCRIPT (Arama ve Değişiklik Takibi) --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchInput');
    const cards = document.querySelectorAll('.category-card');
    const noResults = document.getElementById('noResults');
    const inputs = document.querySelectorAll('.profit-input');
    const floatBar = document.querySelector('.float-bar');
    const changeCountSpan = document.getElementById('changeCount');

    searchInput.addEventListener('keyup', function() {
        const term = this.value.toLowerCase().trim();
        let visibleCount = 0;

        cards.forEach(card => {
            const name = card.dataset.name;
            const code = card.dataset.code;
            const id = card.dataset.id;
            
            if (name.includes(term) || code.includes(term) || id.includes(term)) {
                card.classList.remove('d-none');
                visibleCount++;
            } else {
                card.classList.add('d-none');
            }
        });

        if (visibleCount === 0) noResults.classList.remove('d-none');
        else noResults.classList.add('d-none');
    });

    inputs.forEach(input => {
        input.addEventListener('input', updateBar);
    });

    function updateBar() {
        let changes = 0;
        inputs.forEach(input => {
            const original = parseFloat(input.dataset.original);
            const current = parseFloat(input.value);
            const card = input.closest('.category-card');

            if (input.value !== '' && original !== current) {
                card.classList.add('modified');
                changes++;
            } else {
                card.classList.remove('modified');
            }
        });

        changeCountSpan.innerText = changes;
        if (changes > 0) {
            floatBar.style.transform = "translateY(0)";
        } else {
            floatBar.style.transform = "translateY(150%)";
        }
    }
});
</script>
@endsection