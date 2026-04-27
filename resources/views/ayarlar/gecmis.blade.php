@extends('layouts.app')

@section('title', 'Geçmiş Ayarlar')

@section('content')
<div class="container pt-2 pb-4">
    {{-- ÜST HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-0">
                <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>
                Geçmiş Ayar Kayıtları
            </h4>
            <p class="text-muted small mb-0">Tarihsel olarak kaydedilmiş tüm sistem ayarları.</p>
        </div>
        <a href="{{ route('ayarlar.index') }}" class="btn btn-light border shadow-sm">
            <i class="fa-solid fa-arrow-left me-2"></i>
            Güncel Ayarlara Dön
        </a>
    </div>

    {{-- ANA KART --}}
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th class="text-secondary">Geçerlilik Tarihi</th>
                            <th class="text-secondary text-end">Dolar Kuru (₺)</th>
                            <th class="text-secondary text-end">Altın USD ($)</th>
                            <th class="text-secondary text-end">Altın Fiyatı (₺)</th>
                            <th class="text-secondary text-end">İşçilik (₺)</th>
                            <th class="text-secondary text-end">Kargo (₺)</th>
                            <th class="text-secondary text-end">Kutu (₺)</th>
                            <th class="text-secondary text-end">Reklam (₺)</th>
                            <th class="text-secondary text-end">Site %</th>
                            <th class="text-secondary text-end">T.yol %</th>
                            <th class="text-secondary text-end">Etsy %</th>
                            <th class="text-secondary text-end">H.con %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($gecmisAyarlar as $ayar)
                            <tr>
                                <td class="fw-semibold">
                                    {{ \Carbon\Carbon::parse($ayar->tarih)->format('d F Y') }}
                                </td>
                                <td class="text-end">{{ number_format($ayar->dolar_kuru, 2, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($ayar->altin_usd, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($ayar->altin_fiyat, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($ayar->iscilik, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($ayar->kargo, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($ayar->kutu, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format($ayar->reklam, 0, ',', '.') }}</td>
                                <td class="text-end fw-bold text-primary">{{ number_format(($ayar->komisyon_site ?? 0.05) * 100, 1) }}%</td>
                                <td class="text-end fw-bold text-primary">{{ number_format(($ayar->komisyon_trendyol ?? 0.225) * 100, 1) }}%</td>
                                <td class="text-end fw-bold text-primary">{{ number_format(($ayar->komisyon_etsy ?? 0.16) * 100, 1) }}%</td>
                                <td class="text-end fw-bold text-primary">{{ number_format(($ayar->komisyon_hipicon ?? 0.30) * 100, 1) }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="text-center text-muted py-4">
                                    Henüz geçmiş bir ayar kaydı bulunmuyor.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
