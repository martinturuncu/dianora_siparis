<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dianora Mobile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --bg-body: #f2f2f2;
            --bg-card: #ffffff;
            --text-main: #111111;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
            --header-bg: #000000;
        }
        body.dark-mode {
            --bg-body: #111111;
            --bg-card: #1e1e1e;
            --text-main: #e5e7eb;
            --text-muted: #9ca3af;
            --border-color: #333333;
            --header-bg: #0a0a0a;
        }
        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: 'Inter', sans-serif;
            padding-top: 70px; /* Space for fixed header */
        }
        .mobile-header {
            background: var(--header-bg);
            color: #fff;
            padding: 12px 20px;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            z-index: 1000;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="{{ (isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled') ? 'dark-mode' : '' }}">
    
    <header class="mobile-header d-flex justify-content-between align-items-center shadow-sm">
        <div class="d-flex align-items-center gap-2">
            <img src="/images/logo.png" style="height: 22px; filter: brightness(0) invert(1);">
            <span class="fw-bold tracking-tight">Mobil</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('siparisler.index') }}" class="btn btn-outline-light btn-sm rounded-pill px-3 border-opacity-25" style="font-size: 0.75rem;">
                <i class="fa-solid fa-desktop me-1"></i> Masaüstü
            </a>
            <div id="mobileModeToggle" class="text-warning" style="cursor: pointer; font-size: 1.2rem;">
                <i class="fa-solid fa-moon"></i>
            </div>
        </div>
    </header>

    @yield('content')

    <script>
        const toggle = document.getElementById('mobileModeToggle');
        const icon = toggle.querySelector('i');
        
        function updateIcon() {
            if (document.body.classList.contains('dark-mode')) {
                icon.className = 'fa-solid fa-sun text-warning';
            } else {
                icon.className = 'fa-solid fa-moon text-white-50';
            }
        }
        updateIcon();

        toggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const mode = document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled';
            document.cookie = "darkMode=" + mode + "; path=/";
            updateIcon();
        });
    </script>
</body>
</html>
