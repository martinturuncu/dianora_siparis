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
            --border-color: #e5e7eb;
        }
        body.dark-mode {
            --bg-body: #111111;
            --bg-card: #1e1e1e;
            --text-main: #e5e7eb;
            --border-color: #333333;
        }
        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: sans-serif;
        }
        .mobile-header {
            background: #000;
            color: #fff;
            padding: 15px;
            border-bottom-left-radius: 25px;
            border-bottom-right-radius: 25px;
        }
        .card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            color: var(--text-main);
        }
    </style>
</head>
<body class="{{ (isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'enabled') ? 'dark-mode' : '' }}">
    
    <div class="mobile-header d-flex justify-content-between align-items-center shadow">
        <div class="d-flex align-items-center gap-2">
            <img src="/images/logo.png" style="height: 20px; filter: brightness(0) invert(1);">
            <span class="fw-bold">Mobil</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="{{ route('siparisler.index') }}" class="text-white text-decoration-none small">
                <i class="fa-solid fa-desktop me-1"></i> Masaüstü
            </a>
            <div id="mobileModeToggle" style="cursor: pointer;">
                <i class="fa-solid fa-moon"></i>
            </div>
        </div>
    </div>

    @yield('content')

    <script>
        const toggle = document.getElementById('mobileModeToggle');
        toggle.addEventListener('click', () => {
            document.body.classList.toggle('dark-mode');
            const mode = document.body.classList.contains('dark-mode') ? 'enabled' : 'disabled';
            document.cookie = "darkMode=" + mode + "; path=/";
        });
    </script>
</body>
</html>
