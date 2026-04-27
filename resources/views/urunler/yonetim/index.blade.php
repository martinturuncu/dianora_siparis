@extends('layouts.app')

@section('title', 'Ürün Yönetimi')

@section('head')
    {{-- DataTables ve Özel Stil Dosyaları --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <style>
        /* Genel Kart ve Tablo Stili */
        .table-card {
            border-radius: 3rem;
            border: none;
            box-shadow: 0 4px 24px rgba(0, 0, 0, 0.04);
            background: var(--bg-card);
            overflow: hidden;
        }

        /* Tablo Başlıkları */
        table.dataTable thead th {
            background-color: var(--bg-body);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            border-bottom: 1px solid var(--border-color) !important;
            padding: 16px 24px;
        }

        /* Tablo Hücreleri */
        table.dataTable tbody td {
            padding: 16px 24px;
            vertical-align: middle;
            color: var(--text-main);
            font-size: 0.95rem;
            border-bottom: 1px solid var(--border-color);
        }

        /* Satır Hover Efekti */
        table.dataTable tbody tr {
            transition: all 0.2s ease;
            background-color: transparent !important;
        }
        table.dataTable tbody tr:hover {
            background-color: var(--bg-body) !important;
            transform: translateY(-1px);
        }

        /* Arama ve Sayfalama Kontrolleri */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 50px;
            padding: 8px 16px;
            border: 1px solid var(--input-border);
            background-color: var(--input-bg);
            color: var(--text-main);
            font-size: 0.9rem;
            outline: none;
            transition: all 0.2s;
            box-shadow: none;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #4f46e5;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        /* Özel Kategori Etiketi */
        .badge-kategori {
            background-color: rgba(79, 70, 229, 0.1);
            color: #4f46e5;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.85rem;
            display: inline-block;
        }
        
        body.dark-mode .badge-kategori {
            background-color: rgba(99, 102, 241, 0.2);
            color: #818cf8;
        }

        /* Buton Grubu Düzenlemesi */
        .action-btn-group .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            height: 38px;
        }
        
        /* DataTables Info & Pagination in Dark Mode */
        .dataTables_info, .dataTables_paginate {
            color: var(--text-muted) !important;
        }
        .page-link {
            background-color: var(--bg-card) !important;
            border-color: var(--border-color) !important;
            color: var(--text-main) !important;
        }
        .page-item.active .page-link {
            background-color: #4f46e5 !important;
            border-color: #4f46e5 !important;
            color: #fff !important;
        }

        /* Toplu Import Textarea */
        #bulkImportModal textarea {
            background-color: #fff !important;
            border: 1px solid var(--border-color) !important;
            transition: all 0.2s;
        }
        #bulkImportModal textarea:focus {
            border-color: #4f46e5 !important;
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
            outline: none;
        }
    </style>
@endsection

@section('content')
<div class="container-fluid pt-2 pb-5" style="min-height: 100vh;">
    <div class="container px-lg-5">

        {{-- BAŞLIK VE ÖZET ALANI --}}
        <div class="row align-items-center mb-4">
            <div class="col-md-7">
                <h2 class="fw-bold text-dark mb-1 d-flex align-items-center">
                    <span class="bg-white shadow-sm rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                        <i class="fa-solid fa-gem text-primary" style="font-size: 1.2rem;"></i>
                    </span>
                    Ürün Yönetimi
                </h2>
                <p class="text-muted ms-5 ps-3 mb-0">Envanterinizdeki tüm ürünleri buradan takip edebilir ve düzenleyebilirsiniz.</p>
            </div>
            <div class="col-md-5 text-md-end mt-3 mt-md-0">
                <div class="d-flex justify-content-md-end align-items-center gap-2">
                    {{-- YENİ ÜRÜN EKLE BUTONLARI --}}
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-dark rounded-pill px-4 py-2 shadow-sm fw-bold" data-bs-toggle="modal" data-bs-target="#bulkImportModal">
                            <i class="fa-solid fa-file-import me-2"></i>Toplu Ürün Ekle
                        </button>
                        <a href="{{ route('urunler.create') }}" class="btn btn-dark rounded-pill px-4 py-2 shadow-sm fw-bold">
                            <i class="fa-solid fa-plus me-2"></i>Yeni Ürün Ekle
                        </a>
                    </div>

                    {{-- TOPLAM SAYAÇ --}}
                    <div class="d-inline-flex align-items-center bg-white px-4 py-2 rounded-pill shadow-sm border border-light">
                        <i class="fa-solid fa-layer-group text-muted me-2"></i>
                        <span class="text-muted small me-2">Toplam:</span>
                        <span class="fw-bold text-dark fs-5" id="toplam-urun-sayisi">...</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- BİLDİRİMLER --}}
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 rounded-4 mb-4 bg-white" role="alert">
                <div class="d-flex align-items-center text-success">
                    <i class="fa-solid fa-circle-check fs-4 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-0">İşlem Başarılı!</h6>
                        <p class="mb-0 small text-muted">{{ session('success') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @if(session('hata'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 rounded-4 mb-4 bg-white" role="alert">
                <div class="d-flex align-items-center text-danger">
                    <i class="fa-solid fa-circle-xmark fs-4 me-3"></i>
                    <div>
                        <h6 class="fw-bold mb-0">Hata Oluştu!</h6>
                        <p class="mb-0 small text-muted text-break">{{ session('hata') }}</p>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        {{-- KATEGORİ FİLTRESİ --}}
        <div class="mb-4">
            <div class="d-flex align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <label class="text-muted small fw-medium mb-0">Kategori:</label>
                    <select id="kategori-filtre" class="form-select form-select-sm rounded-pill border-light shadow-sm" style="width: auto; min-width: 200px;">
                        <option value="">Tümü</option>
                    </select>
                </div>

                {{-- ÖZEL ARAMA BARI --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="input-group input-group-sm shadow-sm rounded-pill overflow-hidden border">
                        <span class="input-group-text bg-white border-0 ps-3">
                            <i class="fa-solid fa-magnifying-glass text-muted small"></i>
                        </span>
                        <input type="text" id="custom-search-input" class="form-control border-0 ps-2" placeholder="Ürün kodu veya kategori ara..." style="min-width: 300px; height: 38px;">
                        <button class="btn btn-white border-0 text-muted px-3" type="button" id="clear-search" style="display:none;">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- TABLO KARTI --}}
        <div class="card table-card">
            <div class="card-body p-0">
                <table id="urunler-tablosu" class="table table-borderless w-100 mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">ID</th>
                            <th>Ürün Bilgisi</th>
                            <th>Kategori</th>
                            <th>Gramaj</th>
                            <th class="text-end pe-4">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Loading State --}}
                        <tr id="loading-row">
                            <td colspan="5" class="text-center py-5">
                                <div class="d-flex flex-column align-items-center">
                                    <div class="spinner-border text-primary mb-3" role="status"></div>
                                    <span class="text-muted fw-medium">Veriler yükleniyor...</span>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('modals')
{{-- TOPLU ÜRÜN EKLE MODAL --}}
<div class="modal fade" id="bulkImportModal" tabindex="-1" aria-labelledby="bulkImportModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0 pt-4 px-4">
                <h5 class="modal-title fw-bold" id="bulkImportModalLabel">Excel'den Toplu Ürün Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('urunler.bulkStore') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-4 mb-4">
                        <h6 class="fw-bold mb-2">Talimatlar:</h6>
                        <ol class="small text-muted mb-0 ps-3">
                            <li>Excel'den satırları ve sütunları kopyalayın.</li>
                            <li>Aşağıdaki metin alanına yapıştırın.</li>
                            <li><strong>Sütun Sırası:</strong> Ürün Kodu | Gramaj | Kategori ID</li>
                            <li>Aynı kodlu ürün varsa gramajı ve kategorisi güncellenir.</li>
                        </ol>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold small text-muted">Excel Verilerini Buraya Yapıştırın:</label>
                            <textarea name="data" class="form-control rounded-4 p-3 border" rows="12" 
                                placeholder="H001	2.50	1&#10;H002	5.10	2" required 
                                style="font-family: monospace; font-size: 0.9rem; background-color: #fff !important;"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold small text-muted">Kategori ID Listesi:</label>
                            <div class="bg-light rounded-4 p-3 overflow-auto" style="max-height: 290px;">
                                <table class="table table-sm table-borderless mb-0 small">
                                    <thead>
                                        <tr class="border-bottom">
                                            <th class="ps-0">ID</th>
                                            <th>Kategori</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($kategoriler as $kat)
                                            <tr>
                                                <td class="ps-0 fw-bold text-primary">{{ $kat->Id }}</td>
                                                <td class="text-muted">{{ $kat->KategoriAdi }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-top-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4 py-2 fw-semibold" data-bs-dismiss="modal">Vazgeç</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4 py-2 fw-bold shadow-sm">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>Verileri İşle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endpush

@push('scripts')
    {{-- Gerekli JS Kütüphaneleri --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
    $(document).ready(function() {
        // Kategorileri yükle
        $.ajax({
            url: '/kategoriler',
            method: 'GET',
            dataType: 'html',
            success: function(html) {
                // Kategorileri parse et (Blade'den çektiğimiz sayfa içinden)
                // Alternatif olarak API endpoint oluşturulabilir
            }
        });

        // Kategorileri veritabanından çek (Controller'dan gelen değişkeni kullanıyoruz)
        @foreach($kategoriler as $kat)
            $('#kategori-filtre').append('<option value="{{ $kat->Id }}">{{ $kat->KategoriAdi }}</option>');
        @endforeach

        var table = $('#urunler-tablosu').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '/api/urunler',
                data: function(d) {
                    d.kategori = $('#kategori-filtre').val();
                    d.t = new Date().getTime(); // Cache busting
                },
                error: function(xhr, error, thrown) {
                    console.error('DataTables Error:', error, thrown);
                    $('#loading-row td').html('<div class="text-danger py-4"><i class="fa-solid fa-triangle-exclamation me-2"></i>Veriler yüklenirken bir sorun oluştu.</div>');
                }
            },
            columns: [
                { 
                    data: 'Id',
                    name: 'u.Id',
                    render: function(data) {
                        return '<span class="text-muted small fw-bold">#' + data + '</span>';
                    },
                    className: 'ps-4 text-center',
                    width: '60px'
                },
                { 
                    data: 'UrunKodu',
                    name: 'u.UrunKodu',
                    render: function(data) {
                        return `
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-3 p-2 me-3 text-secondary d-flex align-items-center justify-content-center" style="width:40px; height:40px;">
                                    <i class="fa-solid fa-box-open"></i>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">${data}</div>
                                </div>
                            </div>
                        `;
                    }
                },
                { 
                    data: 'KategoriAdi', 
                    name: 'k.KategoriAdi',
                    defaultContent: '',
                    render: function(data) {
                        return data 
                            ? `<span class="badge-kategori"><i class="fa-solid fa-tag me-1 opacity-50"></i>${data}</span>` 
                            : '<span class="text-muted small fst-italic">- Kategori Yok -</span>';
                    }
                },
                { 
                    data: 'Gram',
                    name: 'u.Gram',
                    render: function(data) {
                         return `<span class="fw-bold text-dark fs-6">${data}</span>`;
                    }
                },
                { 
                    data: 'action', 
                    orderable: false, 
                    searchable: false,
                    className: 'text-end pe-4',
                    render: function(data, type, row) {
                        var editUrl = '{{ route("urunler.edit", ":id") }}'.replace(':id', row.Id);
                        var deleteButton = `<button onclick="urunSil(${row.Id})" class="btn btn-light text-danger border border-2 shadow-sm px-3"><i class="fa-solid fa-trash"></i></button>`;
                        var editButton = `<a href="${editUrl}" class="btn btn-light text-primary border border-2 fw-semibold px-3 shadow-sm me-1"><i class="fa-solid fa-pen-to-square"></i> Düzenle</a>`;
                        
                        return `<div class="action-btn-group justify-content-end">${editButton}${deleteButton}</div>`;
                    }
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/tr.json',
                search: "",
                searchPlaceholder: "Ürün kodu veya kategori ara...",
                lengthMenu: "_MENU_",
                info: "Toplam _TOTAL_ kayıttan _START_ - _END_ arası",
                emptyTable: "Gösterilecek ürün bulunamadı.",
                paginate: {
                    first: '<i class="fa-solid fa-angles-left"></i>',
                    last: '<i class="fa-solid fa-angles-right"></i>',
                    previous: 'Önceki',
                    next: 'Sonraki'
                }
            },
            dom: '<"d-flex justify-content-end align-items-center p-4"<"text-muted small"l>><rt<"d-flex justify-content-between align-items-center p-4 border-top"ibp>',
            order: [[1, 'asc']], // Alfabetik sıralama (UrunKodu)
            pageLength: 10,
            initComplete: function() {
                $('.dataTables_length select').addClass('form-select-sm border-0 bg-light mx-2').css('width', 'auto');
            },
            drawCallback: function(settings) {
                $('#toplam-urun-sayisi').text(settings.fnRecordsDisplay());
            },

        });

        // Kategori filtresi değişince tabloyu yenile
        $('#kategori-filtre').on('change', function() {
            table.ajax.reload();
        });

        // Özel Arama Girişi
        var searchTimer;
        $('#custom-search-input').on('keyup input', function() {
            var val = $(this).val();
            
            // Temizleme butonunu göster/gizle
            if (val.length > 0) {
                $('#clear-search').show();
            } else {
                $('#clear-search').hide();
            }

            // Yazma bitince (debounce) arama yap
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function() {
                table.search(val).draw();
            }, 300);
        });

        // Arama Temizleme Butonu
        $('#clear-search').on('click', function() {
            $('#custom-search-input').val('').trigger('input').focus();
        });
    });

    function urunSil(id) {
        if (confirm('Bu ürünü silmek istediğinize emin misiniz?')) {
            $.ajax({
                url: '/urunler/' + id,
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        // DataTable'ı yenile
                        $('#urunler-tablosu').DataTable().ajax.reload(null, false);
                        
                        // Başarı mesajı göster
                        alert(response.message);
                    } else {
                        alert('Hata: ' + response.message);
                    }
                },
                error: function(xhr) {
                    alert('Bir hata oluştu!');
                    console.error(xhr);
                }
            });
        }
    }
    </script>
@endpush