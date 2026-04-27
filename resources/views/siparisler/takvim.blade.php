@extends('layouts.app')

@section('title', 'Satış Takvimi')

@section('content')
@php
    $aylar = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran', 
        7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık'
    ];
    
    $prevMonth = $allTime ? null : $start->copy()->subMonth();
    $nextMonth = $allTime ? null : $start->copy()->addMonth();
    
    // First day of the month (1=Mon, 7=Sun)
    $firstDayOfWeek = $allTime ? 1 : $start->dayOfWeekIso; 
    $daysInMonth = $allTime ? 0 : $start->daysInMonth;
@endphp

<div class="container-fluid px-4 pt-2 pb-4">
    
    {{-- HEADER --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 fw-bold text-dark mb-1">
                <i class="fa-solid fa-calendar-days text-primary me-2"></i>Satış Takvimi
            </h1>
            <p class="text-muted small mb-0">Gün bazlı satış ve kâr dağılımı.</p>
        </div>
        
        <div class="d-flex align-items-center gap-2">
            {{-- ALL TIME BUTTON --}}
            <a href="{{ route('istatistikler.takvim', ['all_time' => 1]) }}" class="btn btn-sm {{ $allTime ? 'btn-warning active' : 'btn-outline-warning' }} rounded-pill px-3 fw-bold shadow-sm">
                <i class="fa-solid fa-infinity me-1"></i> Tüm Zamanlar
            </a>

            {{-- VIEW TOGGLE --}}
            <div class="btn-group shadow-sm me-2" role="group">
                <button type="button" class="btn btn-sm btn-white border px-3 fw-bold active" id="btnTakvim" onclick="setView('takvim')">
                    <i class="fa-solid fa-table-cells-large me-1"></i> Takvim
                </button>
                <button type="button" class="btn btn-sm btn-white border px-3 fw-bold" id="btnListe" onclick="setView('liste')">
                    <i class="fa-solid fa-list me-1"></i> Liste
                </button>
            </div>

            @if(!$allTime)
            <form action="{{ route('istatistikler.takvim') }}" method="GET" class="d-flex gap-2">
                <select name="ay" class="form-select form-select-sm rounded-pill px-3 fw-medium shadow-sm" style="width: 130px;" onchange="this.form.submit()">
                    @foreach($aylar as $num => $name)
                        <option value="{{ $num }}" {{ $ay == $num ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
                <select name="yil" class="form-select form-select-sm rounded-pill px-3 fw-medium shadow-sm" style="width: 100px;" onchange="this.form.submit()">
                    @for($i = date('Y'); $i >= 2024; $i--)
                        <option value="{{ $i }}" {{ $yil == $i ? 'selected' : '' }}>{{ $i }}</option>
                    @endfor
                </select>
            </form>
            
            <div class="btn-group shadow-sm ms-2">
                <a href="{{ route('istatistikler.takvim', ['ay' => $prevMonth->month, 'yil' => $prevMonth->year]) }}" class="btn btn-sm btn-light border px-3">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
                <a href="{{ route('istatistikler.takvim', ['ay' => $nextMonth->month, 'yil' => $nextMonth->year]) }}" class="btn btn-sm btn-light border px-3">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
            @else
            <a href="{{ route('istatistikler.takvim') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-bold shadow-sm">
                <i class="fa-solid fa-calendar-day me-1"></i> Aylık Görünüme Dön
            </a>
            @endif

            <a href="{{ route('istatistikler') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-medium ms-2">
               İstatistiklere Dön
            </a>
        </div>
    </div>

    {{-- CALENDAR GRID --}}
    @if(!$allTime)
    <div id="viewTakvim">
        <div class="card border-0 shadow-sm rounded-5 overflow-hidden">
            <div class="card-header bg-white py-3 border-0 border-bottom d-none d-lg-block">
                <div class="row text-center fw-bold text-uppercase small text-muted">
                    <div class="col">Pazartesi</div>
                    <div class="col">Salı</div>
                    <div class="col">Çarşamba</div>
                    <div class="col">Perşembe</div>
                    <div class="col">Cuma</div>
                    <div class="col">Cumartesi</div>
                    <div class="col text-danger">Pazar</div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="row g-0 row-cols-1 row-cols-md-7 calendar-grid">
                    {{-- Empty days before start of month --}}
                    @for($i = 1; $i < $firstDayOfWeek; $i++)
                        <div class="col d-none d-md-block bg-light bg-opacity-50 border-end border-bottom" style="min-height: 120px;"></div>
                    @endfor

                    {{-- Actual days --}}
                    @foreach($takvim as $date => $data)
                        @php 
                            $isToday = $date == date('Y-m-d');
                            $isWeekend = date('N', strtotime($date)) >= 6;
                        @endphp
                        <div class="col border-end border-bottom calendar-day {{ $isToday ? 'bg-primary bg-opacity-10' : '' }} {{ $isWeekend ? 'bg-light bg-opacity-25' : '' }}" style="min-height: 120px;">
                            <div class="p-3 h-100 d-flex flex-column justify-content-between">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <span class="fw-bold {{ $isToday ? 'text-primary fs-5' : 'text-secondary' }}">
                                        {{ $data['gun'] }}
                                    </span>
                                    @if($data['adet'] > 0)
                                        <span class="badge bg-primary rounded-pill px-2 py-1 x-small shadow-sm">
                                            {{ $data['adet'] }} Adet
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="text-end">
                                    @if($data['kar'] > 0)
                                        <div class="fw-bold text-success font-monospace small" title="Günlük Kâr">
                                            +{{ number_format($data['kar'], 0, ',', '.') }} ₺
                                        </div>
                                    @elseif($data['kar'] < 0)
                                        <div class="fw-bold text-danger font-monospace small" title="Günlük Zarar">
                                            {{ number_format($data['kar'], 0, ',', '.') }} ₺
                                        </div>
                                    @else
                                        <div class="text-muted opacity-25 x-small">-</div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach

                    {{-- Empty days after end of month --}}
                    @php $remaining = (7 - (($firstDayOfWeek - 1 + $daysInMonth) % 7)) % 7; @endphp
                    @for($i = 0; $i < $remaining; $i++)
                        <div class="col d-none d-md-block bg-light bg-opacity-50 border-end border-bottom" style="min-height: 120px;"></div>
                    @endfor
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- LIST VIEW --}}
    <div id="viewListe" style="display: none;">
        <div class="card border-0 shadow-sm rounded-5 overflow-hidden">
            <div class="table-responsive" style="max-height: 700px;">
                <table class="table table-hover align-middle mb-0" id="salesTable">
                    <thead class="bg-light text-uppercase small text-muted sticky-top" style="z-index: 10;">
                        <tr>
                            <th class="ps-4 cursor-pointer" onclick="sortTable(0)">Tarih <i class="fa-solid fa-sort ms-1 opacity-50"></i></th>
                            <th class="text-center cursor-pointer" onclick="sortTable(1)">Satış Adedi <i class="fa-solid fa-sort ms-1 opacity-50"></i></th>
                            <th class="text-end pe-4 cursor-pointer" onclick="sortTable(2)">Günlük Net Kâr <i class="fa-solid fa-sort ms-1 opacity-50"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($takvim as $date => $data)
                            @php 
                                $isToday = $date == date('Y-m-d');
                                $isWeekend = date('N', strtotime($date)) >= 6;
                                $dayName = \Carbon\Carbon::parse($date)->translatedFormat('l');
                                $fullDateLabel = $data['gun'] . ' ' . $aylar[$data['ay']] . ' ' . $data['yil'];
                            @endphp
                            <tr class="{{ $isToday ? 'bg-primary bg-opacity-10' : '' }} {{ $isWeekend && !$isToday ? 'bg-light bg-opacity-25' : '' }}" 
                                data-date="{{ $date }}" 
                                data-adet="{{ $data['adet'] }}" 
                                data-kar="{{ $data['kar'] }}">
                                <td class="ps-4">
                                    <div class="fw-bold text-dark">{{ $fullDateLabel }}</div>
                                    <div class="text-muted x-small text-uppercase">{{ $dayName }}</div>
                                </td>
                                <td class="text-center">
                                    @if($data['adet'] > 0)
                                        <span class="badge bg-primary rounded-pill px-3 py-2 fw-medium">
                                            {{ $data['adet'] }} Adet
                                        </span>
                                    @else
                                        <span class="text-muted opacity-50">-</span>
                                    @endif
                                </td>
                                <td class="text-end pe-4">
                                    @if($data['kar'] > 0)
                                        <div class="fw-bold text-success fs-5 font-monospace">
                                            +{{ number_format($data['kar'], 2, ',', '.') }} ₺
                                        </div>
                                    @elseif($data['kar'] < 0)
                                        <div class="fw-bold text-danger fs-5 font-monospace">
                                            {{ number_format($data['kar'], 2, ',', '.') }} ₺
                                        </div>
                                    @else
                                        <div class="text-muted opacity-50 font-monospace">-</div>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- LEGEND --}}
    <div class="mt-4 d-flex gap-4 justify-content-center small text-muted">
        <div><span class="badge bg-primary rounded-pill me-1">&nbsp;</span> Satılan Adet</div>
        <div><span class="fw-bold text-success me-1">+0 ₺</span> Günlük Net Kâr</div>
        <div><span class="badge bg-light border me-1">&nbsp;</span> Hafta Sonu</div>
    </div>

</div>

<style>
    .calendar-grid .col {
        transition: all 0.2s ease;
        position: relative;
    }
    .calendar-grid .calendar-day:hover {
        background-color: var(--active-item-bg);
        z-index: 5;
        box-shadow: 0 0 20px rgba(0,0,0,0.05);
        cursor: default;
    }
    body.dark-mode .calendar-grid .calendar-day:hover {
        box-shadow: 0 0 20px rgba(0,0,0,0.3);
    }
    .x-small { font-size: 0.7rem; }
    .letter-spacing-1 { letter-spacing: 1px; }

    @media (max-width: 767.98px) {
        .calendar-grid .col {
            min-height: auto !important;
            border-right: none !important;
        }
        .calendar-day {
            padding: 10px 0;
        }
    }
</style>
@endsection

@push('scripts')
<script>
    function setView(view) {
        const takvimView = document.getElementById('viewTakvim');
        const listeView = document.getElementById('viewListe');
        const btnTakvim = document.getElementById('btnTakvim');
        const btnListe = document.getElementById('btnListe');

        if (!listeView) return;

        if (view === 'takvim' && takvimView) {
            takvimView.style.display = 'block';
            listeView.style.display = 'none';
            if(btnTakvim) {
                btnTakvim.classList.add('active', 'btn-primary');
                btnTakvim.classList.remove('btn-white');
            }
            if(btnListe) {
                btnListe.classList.remove('active', 'btn-primary');
                btnListe.classList.add('btn-white');
            }
            localStorage.setItem('salesView', 'takvim');
        } else {
            if(takvimView) takvimView.style.display = 'none';
            listeView.style.display = 'block';
            if(btnListe) {
                btnListe.classList.add('active', 'btn-primary');
                btnListe.classList.remove('btn-white');
            }
            if(btnTakvim) {
                btnTakvim.classList.remove('active', 'btn-primary');
                btnTakvim.classList.add('btn-white');
            }
            localStorage.setItem('salesView', 'liste');
        }
    }

    function sortTable(columnIndex) {
        const table = document.getElementById('salesTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = table.dataset.sortCol === String(columnIndex) && table.dataset.sortOrder === 'asc';
        const newOrder = isAsc ? 'desc' : 'asc';
        
        table.dataset.sortCol = columnIndex;
        table.dataset.sortOrder = newOrder;

        rows.sort((a, b) => {
            let valA, valB;
            if (columnIndex === 0) { // Date
                valA = a.dataset.date;
                valB = b.dataset.date;
            } else if (columnIndex === 1) { // Adet
                valA = parseFloat(a.dataset.adet);
                valB = parseFloat(b.dataset.adet);
            } else if (columnIndex === 2) { // Kar
                valA = parseFloat(a.dataset.kar);
                valB = parseFloat(b.dataset.kar);
            }
            
            if (valA < valB) return newOrder === 'asc' ? -1 : 1;
            if (valA > valB) return newOrder === 'asc' ? 1 : -1;
            return 0;
        });

        rows.forEach(row => tbody.appendChild(row));
        
        // Update icons
        const headers = table.querySelectorAll('th i');
        headers.forEach((icon, idx) => {
            icon.className = 'fa-solid fa-sort ms-1 opacity-25';
            if (idx === columnIndex) {
                icon.className = `fa-solid fa-sort-${newOrder === 'asc' ? 'up' : 'down'} ms-1 text-primary`;
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('salesView') || 'liste';
        setView(savedView);
    });
</script>
@endpush
