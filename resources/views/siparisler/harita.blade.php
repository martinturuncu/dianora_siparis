@extends('layouts.app')

@section('content')
<style>
    .harita-container {
        position: relative;
        width: 100%;
        height: calc(100vh - 100px);
        background: #1a1a2e;
        border-radius: 12px;
        overflow: hidden;
        margin-top: 15px;
    }

    #turkiye-harita {
        width: 100%;
        height: 100%;
        background: #16213e;
    }

    .harita-filtre {
        position: absolute;
        top: 15px;
        left: 15px;
        z-index: 1000;
        background: rgba(22, 33, 62, 0.95);
        padding: 12px 18px;
        border-radius: 10px;
        border: 1px solid rgba(255,255,255,0.1);
        backdrop-filter: blur(10px);
    }

    .harita-filtre form {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    .harita-filtre input[type="date"] {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: white;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 13px;
    }

    .harita-filtre .btn-filtre {
        background: #f39c12;
        border: none;
        color: #1a1a2e;
        font-weight: 600;
        padding: 6px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .quick-filters {
        display: flex;
        gap: 5px;
        margin-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.1);
        padding-bottom: 10px;
    }

    .btn-quick {
        background: rgba(255,255,255,0.05);
        border: 1px solid rgba(255,255,255,0.1);
        color: #ccc;
        font-size: 11px;
        padding: 4px 10px;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .btn-quick:hover {
        background: rgba(255,255,255,0.15);
        color: white;
    }

    .btn-quick.active {
        background: #f39c12;
        color: #1a1a2e;
        border-color: #f39c12;
        font-weight: 600;
    }

    .geri-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        z-index: 1000;
        background: #3498db;
        border: none;
        color: white;
        padding: 8px 16px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: 500;
        display: none;
    }

    .geri-btn:hover {
        background: #2980b9;
    }

    .legend {
        position: absolute;
        bottom: 20px;
        right: 15px;
        z-index: 1000;
        background: rgba(22, 33, 62, 0.95);
        padding: 12px 15px;
        border-radius: 8px;
        border: 1px solid rgba(255,255,255,0.1);
        color: white;
    }

    .legend h5 {
        margin: 0 0 8px 0;
        font-size: 11px;
        color: #95a5a6;
        text-transform: uppercase;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        margin-bottom: 4px;
    }

    .legend-color {
        width: 20px;
        height: 12px;
        border-radius: 2px;
    }

    .yukleniyor {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(0,0,0,0.8);
        padding: 25px 40px;
        border-radius: 12px;
        text-align: center;
        z-index: 2000;
    }

    .yukleniyor-spinner {
        width: 40px;
        height: 40px;
        border: 3px solid rgba(255,255,255,0.2);
        border-top-color: #f39c12;
        border-radius: 50%;
        animation: spin 1s linear infinite;
        margin: 0 auto 10px;
    }

    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    .leaflet-popup-content-wrapper {
        background: rgba(22, 33, 62, 0.95);
        color: white;
        border-radius: 8px;
    }

    .leaflet-popup-tip {
        background: rgba(22, 33, 62, 0.95);
    }

    .ilce-yukleniyor {
        position: absolute;
        bottom: 80px;
        left: 15px;
        z-index: 1000;
        background: rgba(243, 156, 18, 0.9);
        padding: 8px 15px;
        border-radius: 6px;
        color: #1a1a2e;
        font-weight: 500;
        display: none;
    }
</style>

<div class="container-fluid px-3">
    <div class="harita-container">
        
        <!-- Yükleniyor -->
        <div class="yukleniyor" id="yukleniyor">
            <div class="yukleniyor-spinner"></div>
            <div class="text-white">Harita Yükleniyor...</div>
        </div>

        <!-- İlçe Yükleniyor -->
        <div class="ilce-yukleniyor" id="ilceYukleniyor">
            <i class="fa fa-spinner fa-spin"></i> İlçeler yükleniyor...
        </div>

        <!-- Geri Butonu -->
        <button class="geri-btn" id="geriBtn">
            ← Türkiye'ye Dön
        </button>

        <!-- Filtre -->
        <div class="harita-filtre">
            <div class="quick-filters">
                <button type="button" class="btn-quick" onclick="setQuickFilter('bugun')">Bugün</button>
                <button type="button" class="btn-quick" onclick="setQuickFilter('bu-hafta')">Bu Hafta</button>
                <button type="button" class="btn-quick" onclick="setQuickFilter('bu-ay')">Bu Ay</button>
                <button type="button" class="btn-quick" onclick="setQuickFilter('tum-zamanlar')">Tüm Zamanlar</button>
            </div>
            <form id="filterForm" method="GET">
                <input type="date" name="baslangic" id="baslangic" value="{{ request('baslangic') }}">
                <span class="text-white">-</span>
                <input type="date" name="bitis" id="bitis" value="{{ request('bitis') }}">
                <button type="submit" class="btn-filtre">Filtrele</button>
                @if(request('baslangic') || request('bitis'))
                    <a href="{{ url()->current() }}" class="btn btn-sm btn-outline-light">✕</a>
                @endif
            </form>
        </div>

        <!-- Legend -->
        <div class="legend">
            <h5>Satılan Ürün</h5>
            <div class="legend-item">
                <div class="legend-color" style="background: #b71c1c;"></div>
                <span>100+ ürün</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #e53935;"></div>
                <span>50-100 ürün</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ff5722;"></div>
                <span>20-50 ürün</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ff9800;"></div>
                <span>10-20 ürün</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffc107;"></div>
                <span>5-10 ürün</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #ffeb3b;"></div>
                <span>1-5 ürün</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: #263238;"></div>
                <span>0 ürün</span>
            </div>
        </div>

        <!-- Harita -->
        <div id="turkiye-harita"></div>
    </div>
</div>

<!-- Leaflet CSS & JS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Hızlı Filtre Fonksiyonu
    window.setQuickFilter = function(type) {
        const startInput = document.getElementById('baslangic');
        const endInput = document.getElementById('bitis');
        const now = new Date();
        
        let start, end;
        
        switch(type) {
            case 'bugun':
                start = end = formatDate(now);
                break;
            case 'bu-hafta':
                const day = now.getDay() || 7; // Pazartesi=1, ..., Pazar=7
                const monday = new Date(now);
                monday.setDate(now.getDate() - (day - 1));
                start = formatDate(monday);
                end = formatDate(now);
                break;
            case 'bu-ay':
                const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
                start = formatDate(firstDay);
                end = formatDate(now);
                break;
            case 'tum-zamanlar':
                start = '';
                end = '';
                break;
        }
        
        startInput.value = start;
        endInput.value = end;
        document.getElementById('filterForm').submit();
    }

    function formatDate(date) {
        const d = new Date(date);
        let month = '' + (d.getMonth() + 1);
        let day = '' + d.getDate();
        const year = d.getFullYear();

        if (month.length < 2) month = '0' + month;
        if (day.length < 2) day = '0' + day;

        return [year, month, day].join('-');
    }

    // Aktif butonu vurgula
    const urlParams = new URLSearchParams(window.location.search);
    const bas = urlParams.get('baslangic');
    const bit = urlParams.get('bitis');
    const today = formatDate(new Date());

    if (!bas && !bit) {
        document.querySelector('.btn-quick[onclick*="tum-zamanlar"]').classList.add('active');
    } else if (bas === today && bit === today) {
        document.querySelector('.btn-quick[onclick*="bugun"]').classList.add('active');
    }
    // Hafta ve Ay için tam eşleşme kontrolü eklenebilir ama bugün ve tüm zamanlar en kritiği
    
    // Sipariş verisi (Backend'den)
    const siparisVerisi = @json($mapData ?? []);
    
    // Veriyi plaka kodu bazlı objeye çevir (Türkçe karakter sorununu önlemek için ID bazlı eşleştirme)
    const ilVerileri = {};
    siparisVerisi.forEach(item => {
        ilVerileri[item.id] = {
            value: item.value,
            districts: item.districts || []
        };
    });

    // Haritayı oluştur
    const map = L.map('turkiye-harita', {
        center: [39.0, 35.5],
        zoom: 6,
        minZoom: 5,
        maxZoom: 12,
        zoomControl: false
    });

    // Zoom kontrolü
    L.control.zoom({ position: 'bottomleft' }).addTo(map);

    // Layer grupları
    let illerLayer = null;
    let ilcelerLayer = null;
    let ilceGeoJSON = null;
    let aktifIl = null;
    let aktifIlId = null;

    // Renk fonksiyonu (Sarı → Turuncu → Kırmızı gradyan)
    function getColor(value) {
        return value > 100 ? '#b71c1c' :  // Koyu kırmızı
               value > 50  ? '#e53935' :  // Kırmızı
               value > 20  ? '#ff5722' :  // Turuncu-kırmızı
               value > 10  ? '#ff9800' :  // Turuncu
               value > 5   ? '#ffc107' :  // Amber
               value > 0   ? '#ffeb3b' :  // Sarı
                             '#263238';   // Koyu gri (0 sipariş)
    }

    // İl stili
    function ilStyle(feature) {
        const mapId = 'TR-' + String(feature.id).padStart(2, '0');
        const veri = ilVerileri[mapId] || { value: 0 };
        
        return {
            fillColor: getColor(veri.value),
            weight: 1.5,
            opacity: 1,
            color: '#1a1a2e',
            fillOpacity: 0.8
        };
    }

    // İlçe stili
    function ilceStyle(feature) {
        const ilceAdi = feature.properties.name;
        
        // Aktif ilin ilçe verilerini bul
        let ilceValue = 0;
        if (aktifIlId && ilVerileri[aktifIlId]) {
            const ilceData = ilVerileri[aktifIlId].districts.find(d => 
                d.category.toLowerCase() === ilceAdi.toLowerCase()
            );
            if (ilceData) ilceValue = ilceData.value;
        }
        
        return {
            fillColor: getColor(ilceValue),
            weight: 1,
            opacity: 1,
            color: '#16213e',
            fillOpacity: 0.85
        };
    }

    // Hover efekti
    function onMouseOver(e) {
        e.target.setStyle({
            weight: 3,
            color: '#f39c12',
            fillOpacity: 0.95
        });
        e.target.bringToFront();
    }

    function onMouseOut(e) {
        if (illerLayer) illerLayer.resetStyle(e.target);
    }

    function onMouseOutIlce(e) {
        if (ilcelerLayer) ilcelerLayer.resetStyle(e.target);
    }

    // İle tıklandığında
    function onIlClick(e) {
        const ilAdi = e.target.feature.properties.name;
        const mapId = 'TR-' + String(e.target.feature.id).padStart(2, '0');
        aktifIl = ilAdi;
        aktifIlId = mapId;
        
        // Zoom yap
        map.fitBounds(e.target.getBounds(), { padding: [50, 50] });
        
        // İlçeleri göster
        setTimeout(() => ilceleriGoster(ilAdi, mapId), 300);
    }

    // İlçeleri göster
    function ilceleriGoster(ilAdi, mapId) {
        if (!ilceGeoJSON) {
            document.getElementById('ilceYukleniyor').style.display = 'block';
            return;
        }

        // İller layer'ını gizle
        if (illerLayer) map.removeLayer(illerLayer);
        
        // Bu ile ait ilçeleri filtrele
        const ilceFeatures = ilceGeoJSON.features.filter(f => {
            const ilceIl = f.properties.il || f.properties.province || '';
            return ilceIl.toLowerCase().includes(ilAdi.toLowerCase().substring(0, 3));
        });

        if (ilceFeatures.length === 0) {
            // İlçe bulunamadıysa tüm ilçeleri göster ve kullanıcıyı bilgilendir
            console.log('İlçe bulunamadı, tüm haritayı göster');
            ilceleriGosterTumu();
            return;
        }

        // Point geometrileri filtrele
        const filteredFeatures = ilceFeatures.filter(f => 
            f.geometry && f.geometry.type !== 'Point'
        );

        // İlçe layer'ını oluştur
        ilcelerLayer = L.geoJSON({ type: 'FeatureCollection', features: filteredFeatures }, {
            style: ilceStyle,
            onEachFeature: function(feature, layer) {
                const ilceAdi = feature.properties.name;
                let ilceValue = 0;
                
                if (mapId && ilVerileri[mapId]) {
                    const ilceData = ilVerileri[mapId].districts.find(d => 
                        d.category.toLowerCase() === ilceAdi.toLowerCase()
                    );
                    if (ilceData) ilceValue = ilceData.value;
                }

                layer.on({
                    mouseover: onMouseOver,
                    mouseout: onMouseOutIlce
                });

                layer.bindTooltip(`<strong>${ilceAdi}</strong><br>📦 ${ilceValue} sipariş`, {
                    permanent: false,
                    direction: 'center'
                });
            }
        }).addTo(map);

        document.getElementById('geriBtn').style.display = 'block';
    }

    // Tüm ilçeleri göster (zoom seviyesine göre)
    function ilceleriGosterTumu() {
        if (!ilceGeoJSON) return;
        
        if (illerLayer) map.removeLayer(illerLayer);
        
        // Tüm ilçe verilerini düz bir objeye çevir (hızlı erişim için)
        const tumIlceVerileri = {};
        Object.keys(ilVerileri).forEach(ilAdi => {
            const ilData = ilVerileri[ilAdi];
            if (ilData.districts) {
                ilData.districts.forEach(d => {
                    // İlçe adını normalize et (küçük harf)
                    const key = d.category.toLowerCase();
                    if (!tumIlceVerileri[key]) {
                        tumIlceVerileri[key] = 0;
                    }
                    tumIlceVerileri[key] += d.value;
                });
            }
        });
        
        ilcelerLayer = L.geoJSON(ilceGeoJSON, {
            // Point (marker) geometrilerini gösterme, sadece Polygon'ları göster
            filter: function(feature) {
                return feature.geometry && feature.geometry.type !== 'Point';
            },
            pointToLayer: function() {
                return null; // Point'leri hiç oluşturma
            },
            style: function(feature) {
                const ilceAdi = (feature.properties.name || '').toLowerCase();
                const ilceValue = tumIlceVerileri[ilceAdi] || 0;
                
                return {
                    fillColor: getColor(ilceValue),
                    weight: 0.5,
                    opacity: 0.8,
                    color: '#1a1a2e',
                    fillOpacity: 0.8
                };
            },
            onEachFeature: function(feature, layer) {
                const ilceAdi = feature.properties.name || '';
                const ilceValue = tumIlceVerileri[ilceAdi.toLowerCase()] || 0;
                
                layer.on({
                    mouseover: onMouseOver,
                    mouseout: onMouseOutIlce
                });
                
                layer.bindTooltip(`<strong>${ilceAdi}</strong><br>📦 ${ilceValue} sipariş`, {
                    permanent: false,
                    direction: 'center'
                });
            }
        }).addTo(map);

        document.getElementById('geriBtn').style.display = 'block';
    }

    // İllere geri dön
    function illeriGoster() {
        aktifIl = null;
        aktifIlId = null;
        
        if (ilcelerLayer) {
            map.removeLayer(ilcelerLayer);
            ilcelerLayer = null;
        }
        
        if (illerLayer) {
            illerLayer.addTo(map);
        }
        
        map.setView([39.0, 35.5], 6);
        document.getElementById('geriBtn').style.display = 'none';
    }

    // Geri butonu
    document.getElementById('geriBtn').addEventListener('click', illeriGoster);

    // İller GeoJSON yükle
    fetch('https://raw.githubusercontent.com/cihadturhan/tr-geojson/master/geo/tr-cities-utf8.json')
        .then(response => response.json())
        .then(data => {
            illerLayer = L.geoJSON(data, {
                style: ilStyle,
                onEachFeature: function(feature, layer) {
                    const ilAdi = feature.properties.name;
                    const mapId = 'TR-' + String(feature.id).padStart(2, '0');
                    const veri = ilVerileri[mapId] || { value: 0, districts: [] };

                    layer.on({
                        mouseover: onMouseOver,
                        mouseout: onMouseOut,
                        click: onIlClick
                    });

                    layer.bindTooltip(`<strong>${ilAdi}</strong><br>📦 ${veri.value} sipariş`, {
                        permanent: false,
                        direction: 'center'
                    });
                }
            }).addTo(map);

            document.getElementById('yukleniyor').style.display = 'none';
            console.log('✓ İller yüklendi');
        });

    // İlçeler GeoJSON yükle (arka planda)
    fetch('{{ asset("maps/turkiye-ilceler.geojson") }}')
        .then(response => response.json())
        .then(data => {
            ilceGeoJSON = data;
            document.getElementById('ilceYukleniyor').style.display = 'none';
            console.log('✓ İlçeler yüklendi:', data.features.length);
        })
        .catch(err => {
            console.warn('İlçe GeoJSON yüklenemedi:', err);
        });

    // Zoom değiştiğinde
    map.on('zoomend', function() {
        const zoom = map.getZoom();
        
        // Zoom 8'den büyükse ve ilçeler yüklüyse, tüm ilçeleri göster
        if (zoom >= 8 && ilceGeoJSON && !ilcelerLayer) {
            ilceleriGosterTumu();
        }
        // Zoom 7'den küçükse illere dön
        else if (zoom < 7 && ilcelerLayer) {
            illeriGoster();
        }
    });
});
</script>
@endsection
