@extends('layouts.app')

@section('title', 'Ürün Düzenle')

@section('content')
<div class="container py-5" style="max-width: 700px;">

    {{-- ÜST BAŞLIK --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-pen-to-square text-primary me-2"></i>Ürün Düzenle
            </h3>
            <p class="text-muted small mb-0">"{{ $urun->UrunKodu }}" kodlu ürünü güncelleyin.</p>
        </div>
        <a href="{{ route('urunler.index') }}" class="btn btn-light border shadow-sm fw-semibold text-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Listeye Dön
        </a>
    </div>

    {{-- FORM KARTI --}}
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('urunler.update', $urun->Id) }}" method="POST">
                @csrf
                @method('PUT')

                <div class="mb-3">
                    <label class="form-label fw-bold small">Stok Kodu (SKU)</label>
                    <input type="text" name="UrunKodu" class="form-control" value="{{ old('UrunKodu', $urun->UrunKodu) }}" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Kategori</label>
                    <select name="KategoriId" class="form-select">
                        <option value="">Kategori Seçilmemiş</option>
                        @foreach($kategoriler as $kategori)
                            <option value="{{ $kategori->Id }}" @selected(old('KategoriId', $urun->KategoriId) == $kategori->Id)>
                                {{ $kategori->KategoriAdi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Gram alanı tek satıra çekildi ve 'required' kaldırıldı --}}
                <div class="mb-3">
                    <label class="form-label fw-bold small">Gram</label>
                    <input type="number" step="0.01" name="Gram" class="form-control" value="{{ old('Gram', $urun->Gram) }}">
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-dark py-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-save me-2"></i> Değişiklikleri Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection