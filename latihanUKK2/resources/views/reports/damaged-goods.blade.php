@extends('layouts.app')

@section('title', 'Laporan Barang Rusak · RAPI-Sekolah')
@section('topbar', 'Laporan Barang Rusak')

@section('content')
<style>
    .report-toolbar { display:flex; flex-wrap:wrap; align-items:end; gap:12px; margin:20px 0; }
    .report-toolbar label { display:grid; gap:6px; font-weight:600; }
    .report-toolbar input, .report-toolbar select { padding:10px; border:1px solid var(--line); border-radius:7px; font:inherit; }
    .report-filter-help { flex-basis:100%; margin:0; }
    .report-filter-result { padding:12px 16px; border-left:4px solid var(--accent); border-radius:6px; background:var(--accent-soft); color:var(--accent-hover); }
    .report-summary { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:12px; margin:20px 0; }
    .report-table-wrap { overflow-x:auto; margin-bottom:28px; }
    .report-table { width:100%; border-collapse:collapse; background:white; }
    .report-table th, .report-table td { padding:10px; border:1px solid var(--line); text-align:left; vertical-align:top; }
    .report-table th { background:#f7f4ee; }
    @media print {
        @page { size:landscape; margin:12mm; }
        body, .app-shell, .main-area, .page-content { display:block !important; width:auto !important; margin:0 !important; padding:0 !important; background:#fff !important; }
        .sidebar, .topbar, .report-toolbar, .no-print, .alert { display:none !important; }
        .report-document { color:#111827; font-size:10pt; }
        .report-table { font-size:9pt; }
        .report-table th, .report-table td { padding:6px; }
    }
</style>
<div class="report-document">
    <div class="page-heading">
        <div>
            <h1>Laporan Barang Rusak</h1>
            <p class="muted">Rekap pengaduan barang/fasilitas rusak pada ruangan yang menjadi tanggung jawab Anda.</p>
        </div>
    </div>

    <form class="report-toolbar no-print" method="get" action="{{ route('reports.damaged-goods') }}">
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
        <a class="btn" href="{{ route('reports.damaged-goods.docx', $period_type === 'month' ? ['period_type' => $period_type, 'selected_month' => sprintf('%04d-%02d', $year, $month)] : ['period_type' => $period_type, 'date' => $startDate->toDateString()]) }}">Unduh DOCX</a>
        <button class="btn secondary" type="button" onclick="window.print()">Simpan PDF / Cetak</button>
    </form>

    @if(request()->hasAny(['period_type', 'selected_month', 'date', 'month', 'year']))
        <p class="report-filter-result no-print" role="status">Laporan periode <strong>{{ $period }}</strong> ditampilkan: {{ $complaints->count() }} pengaduan.</p>
    @endif

    <header>
        <h1>Laporan Barang Rusak RAPI-Sekolah</h1>
        <p><strong>Periode:</strong> {{ $period }}</p>
    </header>
    <section class="report-summary">
        <div class="summary-item"><strong>Total Pengaduan</strong><br>{{ $complaints->count() }}</div>
        <div class="summary-item"><strong>Belum Diperbaiki</strong><br>{{ $complaints->where('status_repair', 'Belum Diperbaiki')->count() }}</div>
    </section>
    <section>
        <h2>Pengaduan Barang/Fasilitas Rusak</h2>
        <div class="report-table-wrap">
            <table class="report-table">
                <thead>
                    <tr><th>No.</th><th>Tanggal Laporan</th><th>Ruangan</th><th>Barang/Fasilitas</th><th>Pelapor</th><th>Status Perbaikan</th><th>Catatan</th></tr>
                </thead>
                <tbody>
                    @forelse($complaints as $complaint)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $complaint->created_at->timezone('Asia/Jakarta')->format('d/m/Y') }}</td>
                            <td>{{ $complaint->room->name }}</td>
                            <td>{{ $complaint->facility_name }}<br>{{ $complaint->description }}</td>
                            <td>{{ $complaint->reporter->name }}</td>
                            <td>{{ $complaint->status_repair }}</td>
                            <td>{{ $complaint->repair_notes ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7">Tidak ada pengaduan pada periode ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
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
