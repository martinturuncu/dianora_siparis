<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dianora Control Panel')</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dianora_favicon.png') }}">

    {{-- Font: Inter (Modern & Okunaklı) --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    {{-- Bootstrap & Icons --}}
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">

    <style>
        :root {
            /* Light Mode Variables */
            --bg-body: #f2f2f2;
            --bg-card: #ffffff;
            --bg-navbar: #000000;
            --bg-ticker: #1a1a1a;
            --text-main: #111111;
            --text-muted: #6b7280;
            --text-muted-light: #9ca3af;
            --border-color: #e5e7eb;
            --input-bg: #ffffff;
            --input-border: #d1d5db;
            --active-item-bg: #ffffff;
            --active-item-text: #000000;
            --nav-link-color: #999999;
            --nav-link-hover: #ffffff;
        }

        body.dark-mode {
            /* Dark Mode Variables */
            --bg-body: #111111;
            --bg-card: #1e1e1e;
            --bg-navbar: #0a0a0a;
            --bg-ticker: #050505;
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --text-muted-light: #6b7280;
            --border-color: #333333;
            --input-bg: #2d2d2d;
            --input-border: #444444;
            --active-item-bg: #333333;
            --active-item-text: #ffffff;
            --nav-link-color: #888888;
            --nav-link-hover: #ffffff;
        }

        body {
            background-color: var(--bg-body);
            font-family: 'Inter', sans-serif;
            color: var(--text-main);
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* --- 1. BİLGİ ŞERİDİ (Ticker) --- */
        .info-ticker {
            background-color: var(--bg-ticker);
            color: var(--text-muted-light);
            font-size: 0.75rem;
            padding: 2px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            font-family: monospace;
        }
        .info-val { color: #fff; font-weight: 700; letter-spacing: 1px; }

        /* --- 2. NAVBAR (MENÜ) --- */
        .navbar {
            background-color: var(--bg-navbar);
            padding: 1rem 0 7rem 0; /* Extended for 80px rounded overlap */
            border-bottom: none;
        }

        .navbar-brand img {
            height: 36px;
            filter: brightness(0) invert(1); 
            transition: opacity 0.3s;
        }
        .navbar-brand:hover img { opacity: 0.8; }

        .nav-link {
            color: var(--nav-link-color) !important;
            font-weight: 500;
            font-size: 0.9rem;
            margin: 0 5px;
            padding: 8px 18px !important;
            border-radius: 50px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: var(--nav-link-hover) !important;
            background-color: rgba(255,255,255,0.1);
        }

        .nav-link.active {
            background-color: var(--active-item-bg);
            color: var(--active-item-text) !important;
            font-weight: 600;
            box-shadow: 0 0 15px rgba(255,255,255,0.1);
        }
        
        .nav-link i { margin-right: 6px; font-size: 1rem; }
        .nav-link.active i { color: inherit; }

        .navbar-toggler { border: 1px solid var(--border-color); }
        .navbar-toggler-icon { filter: invert(1); }

        /* --- CONTENT --- */
        main {
            flex: 1;
            position: relative;
            z-index: 10;
            background-color: var(--bg-body);
            margin-top: -90px; /* Moderate climbing effect */
            border-radius: 80px 80px 0 0; /* Large curves as requested */
            overflow: hidden;
            padding-top: 20px; /* Internal spacing for content */
            box-shadow: 0 -5px 30px rgba(0,0,0,0.08);
        }

        @media (max-width: 768px) {
            main {
                margin-top: -40px;
                border-radius: 40px 40px 0 0;
                padding-top: 10px;
            }
            .navbar {
                padding-bottom: 4rem;
            }
        }

        body.dark-mode main {
            box-shadow: 0 -5px 30px rgba(0,0,0,0.3);
        }

        /* --- FOOTER --- */
        footer {
            background: var(--bg-card);
            border-top: 1px solid var(--border-color);
            padding: 30px 0;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* --- GLOBAL UI COMPONENTS --- */
        .card {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        .modal-content {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }

        .table {
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }

        .table-light {
            background-color: var(--bg-body) !important;
            color: var(--text-main) !important;
        }

        .form-control, .form-select {
            background-color: var(--input-bg) !important;
            border-color: var(--input-border) !important;
            color: var(--text-main) !important;
        }

        .form-control::placeholder {
            color: var(--text-muted-light) !important;
            opacity: 0.7;
        }

        .bg-light, .bg-body {
            background-color: var(--bg-body) !important;
        }

        .bg-white {
            background-color: var(--bg-card) !important;
        }

        .text-dark {
            color: var(--text-main) !important;
        }

        .text-muted {
            color: var(--text-muted) !important;
        }

        .text-secondary {
            color: var(--text-muted-light) !important;
        }

        .border {
            border-color: var(--border-color) !important;
        }

        /* --- VISIBILITY UTILITIES --- */
        .d-dark-none { display: block !important; }
        body.dark-mode .d-dark-none { display: none !important; }
        
        .d-dark-block { display: none !important; }
        body.dark-mode .d-dark-block { display: block !important; }

        /* --- TABLE FIXES --- */
        body.dark-mode .table {
            --bs-table-bg: transparent;
            --bs-table-color: var(--text-main);
            --bs-table-border-color: var(--border-color);
            --bs-table-striped-bg: rgba(255, 255, 255, 0.05);
            --bs-table-hover-bg: rgba(255, 255, 255, 0.07);
            color: var(--text-main) !important;
        }

        body.dark-mode .table-warning {
            --bs-table-bg: rgba(255, 193, 7, 0.15);
            --bs-table-color: #ffc107;
            background-color: rgba(255, 193, 7, 0.15) !important;
            color: #ffc107 !important;
        }

        body.dark-mode .table-success {
            --bs-table-bg: rgba(25, 200, 61, 0.15);
            --bs-table-color: #19c83d;
            background-color: rgba(25, 200, 61, 0.15) !important;
            color: #19c83d !important;
        }

        body.dark-mode .table td, body.dark-mode .table th {
            background-color: inherit !important;
            color: inherit !important;
        }

        body.dark-mode thead.bg-body tr th {
            background-color: var(--bg-body) !important;
            color: var(--text-muted-light) !important;
        }

        /* --- DARK MODE TOGGLE --- */
        .dark-mode-toggle {
            width: 44px;
            height: 22px;
            background-color: #333;
            border: 1px solid #555;
            border-radius: 20px;
            cursor: pointer;
            position: relative;
            display: flex;
            align-items: center;
            padding: 0 4px;
            transition: all 0.3s ease;
        }

        .dark-mode-toggle-circle {
            width: 16px;
            height: 16px;
            background-color: #fff;
            border-radius: 50%;
            position: absolute;
            left: 3px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            z-index: 2;
        }

        body.dark-mode .dark-mode-toggle {
            background-color: #444;
            border-color: #666;
        }

        body.dark-mode .dark-mode-toggle-circle {
            left: calc(100% - 19px);
            background-color: #ffd700;
        }

        .dark-mode-toggle .fa-moon { font-size: 10px; color: #aaa; z-index: 1; margin-left: auto; transition: opacity 0.3s; }
        .dark-mode-toggle .fa-sun { font-size: 10px; color: #ffd700; z-index: 1; position: absolute; left: 6px; opacity: 0; transition: opacity 0.3s; }

        body.dark-mode .dark-mode-toggle .fa-moon { opacity: 0; }
        body.dark-mode .dark-mode-toggle .fa-sun { opacity: 1; }

        /* Miscellaneous */
        .gradient-profit {
            background: linear-gradient(135deg, #dcfce7 0%, #d1fae5 100%) !important;
            border: 1px solid #86efac !important;
        }
        .gradient-loss {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%) !important;
            border: 1px solid #f87171 !important;
        }

        body.dark-mode .gradient-profit {
            background: linear-gradient(135deg, #0d2e0d 0%, #174a17 100%) !important;
            border: 1px solid rgba(25, 200, 61, 0.4) !important;
        }
        body.dark-mode .gradient-loss {
            background: linear-gradient(135deg, #2e0d0d 0%, #4a1717 100%) !important;
            border: 1px solid rgba(255, 107, 107, 0.4) !important;
        }
        
        body.dark-mode .text-success {
            color: #19c83d !important;
        }
        body.dark-mode .bg-success {
            background-color: #19c83d !important;
            color: #000 !important;
        }
        body.dark-mode i.text-success {
            color: #19c83d !important;
        }
        body.dark-mode .border-success, 
        body.dark-mode .border-success-subtle {
            border-color: rgba(25, 200, 61, 0.4) !important;
        }
        body.dark-mode .btn-success {
            background-color: #19c83d !important;
            border-color: #19c83d !important;
            color: #000 !important;
        }
        body.dark-mode .alert-success {
            background-color: rgba(25, 200, 61, 0.1) !important;
            border-color: rgba(25, 200, 61, 0.2) !important;
            color: #19c83d !important;
        }
        
        body.dark-mode .btn-light {
            background-color: #2a2a2a !important;
            border-color: #444 !important;
            color: #e0e0e0 !important;
        }

        /* --- BADGE CONTRAST FIXES --- */
        body.dark-mode .badge.bg-warning.bg-opacity-25 {
            background-color: rgba(255, 193, 7, 0.15) !important;
            color: #ffc107 !important;
            border-color: rgba(255, 193, 7, 0.3) !important;
        }
        body.dark-mode .badge.bg-danger.bg-opacity-10 {
            background-color: rgba(220, 53, 69, 0.15) !important;
            color: #ff6b6b !important;
            border-color: rgba(220, 53, 69, 0.3) !important;
        }

        .btn-white {
            background-color: var(--bg-card) !important;
            color: var(--text-main) !important;
            border-color: var(--border-color) !important;
        }
        .btn-white:hover {
            background-color: var(--bg-body) !important;
        }

        /* --- SHADOWS --- */
        body.dark-mode .shadow-sm {
            box-shadow: 0 .125rem .25rem rgba(0,0,0,.5) !important;
        }
        body.dark-mode .shadow {
            box-shadow: 0 .5rem 1rem rgba(0,0,0,.5) !important;
        }

        /* --- ANIMATIONS --- */
        @keyframes fa-spin-custom {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .btn-sync-hover:hover i.fa-sync, 
        .btn-sync-hover:hover i.fa-arrows-rotate,
        .btn-sync-hover:hover i.fa-rotate {
            animation: fa-spin-custom 1.5s linear infinite;
        }

        /* --- UTILITIES --- */
        .rounded-4 { border-radius: 1.5rem !important; }
        .rounded-5 { border-radius: 3rem !important; }
        
    </style>

    @yield('head')
</head>
<body>

    {{-- 1. EN ÜST BİLGİ ŞERİDİ --}}

    {{-- 1. EN ÜST BİLGİ ŞERİDİ --}}
    <div class="info-ticker">
        <div class="container-fluid px-3 d-flex justify-content-between align-items-center">
            
            {{-- 1. SOL: MörfSoft --}}
            <div class="d-none d-md-block text-secondary">
                Bir MörfSoft projesidir. &nbsp;|&nbsp; Geliştirici: <a href="https://morfingen.info" target="_blank" class="text-secondary text-decoration-underline">MörfSoft</a>
            </div>

            {{-- 2. ORTA: Altın ve Çevirici --}}
            <div class="d-flex align-items-center gap-4">
                @if(isset($currencyPrices['HAS_ALTIN']))
                    <span class="text-secondary"><i class="fa-regular fa-gem me-1"></i>HAS ALTIN:</span>
                    <span>ALIŞ: <span class="info-val">{{ number_format($currencyPrices['HAS_ALTIN']['buy'], 2, ',', '.') }}</span></span>
                    <span class="text-secondary">/</span>
                    <span>SATIŞ: <span class="info-val">{{ number_format($currencyPrices['HAS_ALTIN']['sell'], 2, ',', '.') }}</span></span>
                @else
                    <span class="text-secondary x-small"><i class="fa-solid fa-wifi me-1"></i>Veri Yok</span>
                @endif
                
                <div class="vr bg-secondary mx-2 opacity-50"></div>
                <a href="#" class="text-secondary text-decoration-none small hover-white" data-bs-toggle="modal" data-bs-target="#converterModal">
                    <i class="fa-solid fa-calculator me-1"></i> Gram Çevirici
                </a>
            </div>

            {{-- 3. SAĞ: Ayarlar, Dark Mode, Tarih --}}
            <div class="d-flex align-items-center gap-4">
                <a href="{{ route('mobile') }}" class="btn btn-warning btn-sm rounded-pill px-3 fw-bold me-2" style="font-size: 0.65rem;">
                    <i class="fa-solid fa-mobile-screen-button me-1"></i> Mobil
                </a>

                <a href="{{ url('/ayarlar') }}" class="text-secondary text-decoration-none small hover-white" title="Ayarlar">
                    <i class="fa-solid fa-sliders me-1"></i> Ayarlar
                </a>

                <div class="vr bg-secondary mx-2 opacity-50"></div>
                <form action="{{ route('logout') }}" method="POST" class="m-0 p-0 d-inline">
                    @csrf
                    <button type="submit" class="btn btn-link text-secondary text-decoration-none small hover-white p-0 m-0 border-0" title="Çıkış Yap">
                        <i class="fa-solid fa-right-from-bracket me-1"></i> Çıkış
                    </button>
                </form>

                <div class="vr bg-secondary mx-2 opacity-50"></div>
                <div class="dark-mode-toggle" id="darkModeToggle" title="Toggle Dark Mode" style="transform: scale(0.8);">
                    <i class="fa-solid fa-sun"></i>
                    <div class="dark-mode-toggle-circle"></div>
                    <i class="fa-solid fa-moon"></i>
                </div>

                <div class="vr bg-secondary mx-2 opacity-50"></div>
                <span class="text-white small fw-bold" style="font-family: 'Inter', sans-serif; letter-spacing: 0.5px;">
                    <i class="fa-regular fa-clock text-secondary me-1"></i>
                    {{ now()->format('d.m.Y H:i') }}
                </span>
            </div>

        </div>
        </div>
    </div>

    {{-- 2. NAVBAR --}}
    <nav class="navbar navbar-expand-lg">
        <div class="container">
            
            {{-- LOGO --}}
            <a class="navbar-brand" href="{{ url('/siparisler') }}">
                <img src="/images/logo.png" alt="Dianora">
            </a>

            {{-- MOBİL BUTON --}}
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            {{-- MENÜ --}}
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav ms-auto mt-3 mt-lg-0">
                    
                    <li class="nav-item">
                        <a href="{{ url('/siparisler') }}" class="nav-link {{ request()->is('siparisler*') ? 'active' : '' }}">
                            <i class="fa-solid fa-box"></i> Siparişler
                        </a>
                    </li>

                    

                    <li class="nav-item">
                        <a href="{{ url('/istatistikler') }}" class="nav-link {{ request()->is('istatistikler*') ? 'active' : '' }}">
                            <i class="fa-solid fa-chart-simple"></i> İstatistikler
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('aylik_net.index') }}" class="nav-link {{ request()->routeIs('aylik_net.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-scale-unbalanced-flip"></i> Aylık Net
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ url('/fiyat') }}" class="nav-link {{ request()->is('fiyat*') ? 'active' : '' }}">
                            <i class="fa-solid fa-calculator"></i> Fiyat Sihirbazı
                        </a>
                    </li>



                  

                    <li class="nav-item">
                        <a href="{{ route('urunler.index') }}" class="nav-link {{ request()->is('urunler*') ? 'active' : '' }}">
                            <i class="fa-solid fa-gem"></i> Ürünler
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('doviz.index') }}" class="nav-link {{ request()->routeIs('doviz.index') ? 'active' : '' }}">
                            <i class="fa-solid fa-money-bill-trend-up"></i> Canlı Döviz
                        </a>
                    </li>

                    
                    
                </ul>
            </div>
        </div>
    </nav>

    {{-- 3. İÇERİK --}}
    <main>
        @yield('content')
    </main>
    
    {{-- Mobil Floating Button --}}
    <div class="d-md-none position-fixed bottom-0 end-0 p-4" style="z-index: 1060;">
        <a href="{{ route('mobile') }}" class="btn btn-warning rounded-circle shadow-lg d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
            <i class="fa-solid fa-mobile-screen-button fs-3"></i>
        </a>
    </div>

    @stack('modals')

    {{-- 4. FOOTER --}}
    <footer>
        <div class="container text-center d-flex flex-column align-items-center gap-2">
            
            {{-- Footer Logo (Gri Tonlamalı) --}}
            <img src="/images/logo.png" alt="Logo" style="height: 18px; opacity: 0.3; filter: grayscale(100%);">
            
            <div class="small text-muted">
                &copy; {{ date('Y') }} Dianora Piercing & Jewelry
            </div>
            <div class="small text-muted">
                 Tüm hakları saklıdır.
        </div>
    </footer>

    {{-- Scriptler --}}
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    {{-- Dark Mode Script --}}
    <script>
        const darkModeToggle = document.getElementById('darkModeToggle');
        const body = document.body;
        const circleIcon = darkModeToggle.querySelector('.dark-mode-toggle-circle i');

        // Check localStorage for saved preference
        if (localStorage.getItem('darkMode') === 'enabled') {
            body.classList.add('dark-mode');
        }

        // Toggle dark mode
        darkModeToggle.addEventListener('click', () => {
            body.classList.toggle('dark-mode');
            
            if (body.classList.contains('dark-mode')) {
                localStorage.setItem('darkMode', 'enabled');
            } else {
                localStorage.setItem('darkMode', 'disabled');
            }
        });
    </script>
    
    {{-- CONVERTER MODAL --}}
    <div class="modal fade" id="converterModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content rounded-4 border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 rounded-top-4">
                    <h5 class="modal-title fw-bold"><i class="fa-solid fa-calculator me-2 text-warning"></i>14 Ayar Gram Dönüşüm</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>
                <div class="modal-body p-0">
                    <table class="table table-striped mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Kaynak</th>
                                <th>Hedef</th>
                                <th>Çarpan</th>
                                <th class="text-end pe-4">Örnek (10gr)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">14 Ayar</td>
                                <td class="fw-bold text-dark">18 Ayar</td>
                                <td class="text-primary fw-bold">1.16</td>
                                <td class="text-end pe-4">11.60 gr</td>
                            </tr>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">14 Ayar</td>
                                <td class="fw-bold text-dark">21 Ayar</td>
                                <td class="text-primary fw-bold">1.30</td>
                                <td class="text-end pe-4">13.00 gr</td>
                            </tr>
                            <tr>
                                <td class="ps-4 fw-bold text-secondary">14 Ayar</td>
                                <td class="fw-bold text-dark">22 Ayar</td>
                                <td class="text-primary fw-bold">1.32</td>
                                <td class="text-end pe-4">13.20 gr</td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="p-3 bg-light text-muted x-small border-top">
                        <i class="fa-solid fa-circle-info me-1"></i> Bu çarpanlar yaklaşık değerlerdir ve atölye fire oranlarına göre değişebilir.
                    </div>
                    
                    {{-- HIZLI HESAPLAYICI --}}
                    <div class="p-4 bg-white border-top">
                        <h6 class="fw-bold mb-3 text-dark border-bottom pb-2">Hızlı Hesaplayıcı</h6>
                        <div class="row g-3 align-items-end">
                            <div class="col-4">
                                <label class="form-label small fw-bold text-secondary">14 Ayar Gram</label>
                                <input type="number" id="calcInput" class="form-control fw-bold" placeholder="0.00" step="0.01">
                            </div>
                            <div class="col-4">
                                <label class="form-label small fw-bold text-secondary">Hedef Ayar</label>
                                <select id="calcTarget" class="form-select fw-bold">
                                    <option value="1.16">18 Ayar</option>
                                    <option value="1.30">21 Ayar</option>
                                    <option value="1.32">22 Ayar</option>
                                </select>
                            </div>
                            <div class="col-4">
                                <div class="p-2 bg-light rounded text-center border dashed-border">
                                    <small class="d-block text-muted x-small uppercase">Sonuç</small>
                                    <span id="calcResult" class="fw-bold text-primary fs-5">-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Basit Hesaplama Scripti
        document.addEventListener('DOMContentLoaded', function() {
            const inp = document.getElementById('calcInput');
            const sel = document.getElementById('calcTarget');
            const res = document.getElementById('calcResult');

            function calculate() {
                const val = parseFloat(inp.value);
                const factor = parseFloat(sel.value);
                
                if (!val || val <= 0) {
                    res.innerText = '-';
                    return;
                }
                
                const result = val * factor;
                res.innerText = result.toFixed(2) + ' gr';
            }

            inp.addEventListener('input', calculate);
            sel.addEventListener('change', calculate);
        });
    </script>

    @stack('scripts')
</body>
</html>