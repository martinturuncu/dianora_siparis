<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Giriş Yap - Dianora</title>
    <link rel="icon" type="image/png" href="{{ asset('images/dianora_favicon.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #111111;
            color: #e5e7eb;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            overflow: hidden;
        }

        .login-container {
            width: 100%;
            max-width: 400px;
            padding: 2rem;
            background-color: #1e1e1e;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.4);
            border: 1px solid #333333;
            text-align: center;
        }

        .logo-container img {
            height: 50px;
            margin-bottom: 2rem;
            filter: brightness(0) invert(1);
        }

        .form-control {
            background-color: #2d2d2d !important;
            border: 1px solid #444444 !important;
            color: #ffffff !important;
            padding: 12px 15px;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .form-control:focus {
            border-color: #666666 !important;
            box-shadow: 0 0 0 0.25rem rgba(255,255,255,0.1) !important;
        }

        .form-control::placeholder {
            color: #888888;
        }

        .btn-login {
            background-color: #ffffff;
            color: #000000;
            border: none;
            padding: 12px;
            border-radius: 12px;
            font-weight: 600;
            width: 100%;
            margin-top: 1rem;
            transition: all 0.3s;
        }

        .btn-login:hover {
            background-color: #e0e0e0;
            transform: translateY(-2px);
        }

        .error-msg {
            color: #ff6b6b;
            font-size: 0.85rem;
            margin-top: 0.5rem;
            text-align: left;
        }
        
        .bg-circles {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            overflow: hidden;
            z-index: -1;
        }
        
        .circle {
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(255,255,255,0.03) 0%, rgba(255,255,255,0) 70%);
        }
        
        .c1 { width: 600px; height: 600px; top: -200px; left: -200px; }
        .c2 { width: 800px; height: 800px; bottom: -300px; right: -200px; }
    </style>
</head>
<body>
    <div class="bg-circles">
        <div class="circle c1"></div>
        <div class="circle c2"></div>
    </div>

    <div class="login-container">
        <div class="logo-container">
            <img src="{{ asset('images/logo.png') }}" alt="Dianora Logo">
        </div>
        
        <h5 class="mb-4 fw-bold" style="color: #ffffff;">Yönetim Paneli</h5>

        <form action="{{ route('login') }}" method="POST">
            @csrf
            <div class="mb-3 text-start">
                <div class="input-group">
                    <span class="input-group-text border-0" style="background-color: #2d2d2d; color: #888; border-top-left-radius: 12px; border-bottom-left-radius: 12px;">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" name="password" class="form-control border-start-0" placeholder="Erişim Şifresi" required autofocus style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                </div>
                @error('password')
                    <div class="error-msg"><i class="fa-solid fa-circle-exclamation me-1"></i>{{ $message }}</div>
                @enderror
            </div>

            <button type="submit" class="btn btn-login">
                Giriş Yap <i class="fa-solid fa-arrow-right ms-2"></i>
            </button>
        </form>
    </div>
</body>
</html>
