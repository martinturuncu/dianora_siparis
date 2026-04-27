@extends('layouts.app')

@section('title', 'Yeni Ürün Ekle')

@section('content')
<div class="container py-5" style="max-width: 700px;">

    {{-- ÜST BAŞLIK --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-dark mb-0">
                <i class="fa-solid fa-plus-circle text-primary me-2"></i>Yeni Ürün Ekle
            </h3>
            <p class="text-muted small mb-0">Envantere yeni bir ürün ekleyin.</p>
        </div>
        <a href="{{ route('urunler.index') }}" class="btn btn-light border shadow-sm fw-semibold text-secondary">
            <i class="fa-solid fa-arrow-left me-2"></i>Listeye Dön
        </a>
    </div>

    {{-- FORM KARTI --}}
    <div class="card border-0 shadow-lg rounded-4">
        <div class="card-body p-4 p-md-5">
            <form action="{{ route('urunler.store') }}" method="POST">
                @csrf

                {{-- Hata Mesajları --}}
                @if ($errors->any())
                    <div class="alert alert-danger mb-4">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="mb-3">
                    <label class="form-label fw-bold small">Stok Kodu (SKU) <span class="text-danger">*</span></label>
                    <input type="text" name="UrunKodu" class="form-control" value="{{ old('UrunKodu') }}" placeholder="Örn: H123" required>
                    <div class="form-text text-muted small">Benzersiz bir ürün kodu giriniz.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Kategori</label>
                    <select name="KategoriId" class="form-select">
                        <option value="">Kategori Seçiniz</option>
                        @foreach($kategoriler as $kategori)
                            <option value="{{ $kategori->Id }}" @selected(old('KategoriId') == $kategori->Id)>
                                {{ $kategori->KategoriAdi }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Gram</label>
                    <div class="input-group">
                        <input type="number" step="0.01" name="Gram" class="form-control" value="{{ old('Gram') }}" placeholder="0.00">
                        <span class="input-group-text text-muted">gr</span>
                    </div>
                </div>

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-dark py-3 fw-bold shadow-sm">
                        <i class="fa-solid fa-save me-2"></i> Ürünü Kaydet
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection