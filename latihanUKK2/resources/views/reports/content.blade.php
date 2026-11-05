<header>
    <h1>{{ $period_type === 'month' ? 'Laporan Bulanan RAPI-Sekolah' : 'Laporan Jadwal RAPI-Sekolah' }}</h1>
    <p><strong>Periode:</strong> {{ $period }}</p>
    <p class="muted">Rekap jadwal penggunaan ruangan</p>
</header>

<section class="report-summary">
    <div class="summary-item"><strong>Total Jadwal</strong><br>{{ $events->count() }}</div>
    <div class="summary-item"><strong>Jadwal Disetujui</strong><br>{{ $events->where('status', 'approved')->count() }}</div>
</section>

<section class="report-section">
    <h2>Jadwal Ruangan</h2>
    <div class="report-table-wrap">
        <table class="report-table">
            <thead>
                <tr><th>No.</th><th>Tanggal</th><th>Waktu</th><th>Ruangan</th><th>Kegiatan</th><th>Peminjam</th><th>Status</th></tr>
            </thead>
            <tbody>
                @forelse($events as $event)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $event->date->format('d/m/Y') }}</td>
                        <td>{{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }}{{ $event->endsNextDay() ? ' (+1 hari)' : '' }}</td>
                        <td>{{ $event->room->name }}</td>
                        <td>{{ $event->title }}</td>
                        <td>{{ $event->borrower_name ?: $event->user->name }}</td>
                        <td>{{ ['approved' => 'Disetujui', 'pending' => 'Menunggu', 'rejected' => 'Ditolak'][$event->status] ?? $event->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7">Tidak ada jadwal pada periode ini.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</section>
