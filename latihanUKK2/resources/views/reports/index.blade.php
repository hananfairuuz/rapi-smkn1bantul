@extends('layouts.app')

@section('title', 'Laporan Jadwal · RAPI-Sekolah')
@section('topbar', 'Laporan Jadwal')

@section('content')
<style>
    .report-toolbar { display:flex; flex-wrap:wrap; align-items:end; gap:12px; margin:20px 0; }
    .report-toolbar label { display:grid; gap:6px; font-weight:600; }
    .report-toolbar input { padding:10px; border:1px solid var(--line); border-radius:7px; font:inherit; }
    .report-actions { display:flex; flex-wrap:wrap; gap:8px; }
    .download-menu { position:relative; }
    .download-menu[open] { z-index:6; }
    .download-menu summary { list-style:none; cursor:pointer; }
    .download-menu summary::-webkit-details-marker { display:none; }
    .download-options { position:absolute; z-index:5; top:calc(100% + 8px); left:0; display:grid; width:max-content; min-width:min(220px, calc(100vw - 32px)); max-width:calc(100vw - 32px); gap:8px; padding:12px; border:1px solid var(--line); border-radius:9px; background:var(--surface); box-shadow:var(--shadow); }
    .download-options p { margin:0; font-size:.85rem; }
    .download-options .btn { text-align:center; }
    .report-filter-help { flex-basis:100%; margin:0; }
    .report-filter-result { padding:12px 16px; border-left:4px solid var(--accent); border-radius:6px; background:var(--accent-soft); color:var(--accent-hover); }
    .report-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin:20px 0; }
    .report-summary .card { margin:0; }
    .report-table-wrap { overflow-x:auto; margin-bottom:28px; }
    .report-table { width:100%; border-collapse:collapse; background:white; }
    .report-table th, .report-table td { padding:10px; border:1px solid var(--line); text-align:left; vertical-align:top; }
    .report-table th { background:#f7f4ee; }
    .report-section h2 { margin-bottom:8px; }
    @media print {
        @page { size:landscape; margin:12mm; }
        body, .app-shell, .main-area, .page-content { display:block !important; width:auto !important; margin:0 !important; padding:0 !important; background:#fff !important; }
        .sidebar, .topbar, .report-toolbar, .download-menu, .no-print, .alert { display:none !important; }
        .report-document { color:#111827; font-size:10pt; }
        .report-table { font-size:9pt; }
        .report-table th, .report-table td { padding:6px; }
        .report-section { break-inside:avoid; }
    }
</style>
<div class="report-document">
    <div class="page-heading">
        <div>
            <h1>Laporan Jadwal Ruangan</h1>
            <p class="muted">Rekap jadwal penggunaan ruangan berdasarkan periode.</p>
        </div>
    </div>

    <form class="report-toolbar no-print" method="get" action="{{ route('reports.index') }}">
        <label>Periode laporan
            <select id="report-period-type" name="period_type">
                <option value="month" @selected($period_type === 'month')>Bulanan</option>
                <option value="week" @selected($period_type === 'week')>Mingguan</option>
                <option value="day" @selected($period_type === 'day')>Harian</option>
            </select>
        </label>
        <label id="report-month-field">Bulan
            <input type="month" name="selected_month" value="{{ sprintf('%04d-%02d', $year, $month) }}">
        </label>
        <label id="report-date-field">Pilih tanggal
            <input type="date" name="date" value="{{ request('date', $startDate->toDateString()) }}">
        </label>
        <button class="btn" type="submit">Tampilkan Laporan</button>
        <p class="report-filter-help muted">Laporan mingguan dimulai hari Senin. Pilih bulan untuk laporan bulanan atau tanggal untuk laporan mingguan/harian.</p>
        <details class="download-menu">
            <summary class="btn secondary">Unduh Laporan</summary>
            <div class="download-options">
                <a class="btn" href="{{ route('reports.docx', $period_type === 'month' ? ['period_type' => $period_type, 'selected_month' => sprintf('%04d-%02d', $year, $month)] : ['period_type' => $period_type, 'date' => $startDate->toDateString()]) }}">Unduh DOCX</a>
                <button class="btn secondary" type="button" onclick="window.print()">Simpan PDF / Cetak</button>
                <p class="muted">Pilih “Simpan sebagai PDF” pada dialog cetak untuk mengunduh PDF.</p>
            </div>
        </details>
    </form>

    @if(request()->hasAny(['period_type', 'selected_month', 'date', 'month', 'year']))
        <p class="report-filter-result no-print" role="status">Laporan periode <strong>{{ $period }}</strong> ditampilkan: {{ $events->count() }} jadwal.</p>
    @endif

    @include('reports.content')
</div>
<script>
    const reportPeriodType = document.getElementById('report-period-type');
    const reportMonthField = document.getElementById('report-month-field');
    const reportDateField = document.getElementById('report-date-field');
    const reportMonthInput = reportMonthField.querySelector('input');
    const reportDateInput = reportDateField.querySelector('input');

    function updateReportPeriodFields() {
        const isMonthly = reportPeriodType.value === 'month';
        reportMonthField.hidden = !isMonthly;
        reportDateField.hidden = isMonthly;
        reportMonthInput.disabled = !isMonthly;
        reportDateInput.disabled = isMonthly;
    }

    reportPeriodType.addEventListener('change', updateReportPeriodFields);
    updateReportPeriodFields();
</script>
@endsection
