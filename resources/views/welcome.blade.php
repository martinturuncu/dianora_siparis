<!DOCTYPE html>
<html lang="tr">
<head>
<meta charset="UTF-8">
<link href="https://fonts.googleapis.com/css2?family=Marcellus&display=swap" rel="stylesheet">

<style>
    /* Kapsayıcı Ayarları */
    .kayan-yazi-container {
        width: 100%;
        overflow: hidden;
        background: transparent;
        position: relative;
        padding: 10px 0;
        white-space: nowrap;
        z-index: 1000;
        right: 1000px;
    }

    /* Kayan İçerik Ayarları */
    .kayan-yazi-icerik {
        display: inline-block;
        /* Hız: 6 saniye (hızlı) */
        animation: kaymaEfekti 15s linear infinite; 
    }

    /* Yazı Stili */
    .kayan-yazi-icerik span {
        font-family: 'Marcellus', serif;
        font-size: 16px;
        color: #ffffff;
        /* DEĞİŞİKLİK BURADA: Boşluğu 50px'ten 100px'e çıkardık */
        margin-right: 200px; 
        text-shadow: 0px 1px 3px rgba(0,0,0,0.5); 
    }

    /* Animasyon: Sola doğru akış */
    @keyframes kaymaEfekti {
        0% {
            transform: translateX(0);
        }
        100% {
            transform: translateX(-100%); 
        }
    }
</style>
</head>
<body>

    <div class="kayan-yazi-container">
        <div class="kayan-yazi-icerik">
            <span>Tüm Türkiye'ye Ücretsiz Kargo  •  Avrupa'ya Hızlı Gönderim</span>
            <span>Tüm Türkiye'ye Ücretsiz Kargo  •  Avrupa'ya Hızlı Gönderim</span>
         
        </div>
    </div>

</body>
</html>