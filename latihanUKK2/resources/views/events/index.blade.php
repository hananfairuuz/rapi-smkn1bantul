@extends('layouts.app')

@section('title', 'Jadwal Ruangan · RAPI-Sekolah')
@section('topbar', 'Manajemen Jadwal Ruangan')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Manajemen Agenda</h1>
            <p>Agenda pending dan disetujui ikut diperiksa untuk mencegah jadwal bentrok.</p>
            <p class="muted">Agenda yang disetujui otomatis ditandai terlaksana setelah waktu agenda berakhir.</p>
            <p class="muted">Admin dapat menulis pesan WhatsApp sendiri untuk pemohon agenda.</p>
        </div>
        @if(in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']))
            <a class="btn" href="{{ route('events.create') }}">+ Ajukan Agenda</a>
        @endif
    </div>

    <form class="event-filters" method="get" action="{{ route('events.index') }}">
        <label for="agenda-filter">Kategori agenda
            <select id="agenda-filter" name="filter">
                <option value="all" @selected($filter === 'all')>Semua ({{ $eventCounts['all'] }})</option>
                <option value="upcoming" @selected($filter === 'upcoming')>Agenda mendatang ({{ $eventCounts['upcoming'] }})</option>
                <option value="current" @selected($filter === 'current')>Agenda terkini · disetujui ({{ $eventCounts['current'] }})</option>
                <option value="today" @selected($filter === 'today')>Agenda hari ini ({{ $eventCounts['today'] }})</option>
                <option value="pending" @selected($filter === 'pending')>Pending · Belum disetujui ({{ $eventCounts['pending'] }})</option>
                <option value="rejected" @selected($filter === 'rejected')>Agenda ditolak ({{ $eventCounts['rejected'] }})</option>
            </select>
        </label>
        <label for="agenda-search">Cari agenda
            <input id="agenda-search" type="search" name="search" value="{{ $search }}" placeholder="Judul, ruangan, atau peminjam">
        </label>
        <button class="btn" type="submit">Cari</button>
        @if($filter !== 'all' || $search !== '')
            <a class="btn secondary" href="{{ route('events.index') }}">Hapus filter</a>
        @endif
    </form>

    <div class="agenda-list">
        @forelse($events as $event)
            <article class="card agenda-card">
                <div class="agenda-card-header">
                    <div>
                        <h3>{{ $event->title }}</h3>
                        <p class="agenda-detail">{{ $event->date->format('d M Y') }}@if($event->isMultiDay()) – {{ $event->end_date->format('d M Y') }}@endif · {{ substr($event->start_time, 0, 5) }}–{{ substr($event->end_time, 0, 5) }}{{ $event->endsNextDay() && ! $event->isMultiDay() ? ' (+1 hari)' : '' }} · <strong>{{ $event->room->name }}</strong></p>
                        <p class="muted">Pemohon: {{ $event->user->name }}</p>
                        <p class="muted">Peminjam ruangan: {{ $event->borrower_name ?: $event->user->name }}</p>
                        @if(auth()->user()->role?->name === 'admin')
                            <p class="muted submission-time">Diajukan: {{ $event->created_at->timezone('Asia/Jakarta')->format('d/m/Y, H:i') }} WIB</p>
                        @endif
                    </div>
                    <span class="badge {{ $event->status === 'approved' ? 'approved' : ($event->status === 'rejected' ? 'rejected' : 'pending') }}">{{ $event->status === 'approved' ? 'DISETUJUI' : ($event->status === 'rejected' ? 'DITOLAK' : 'PENDING') }}</span>
                </div>
                @if($event->status === 'approved')
                    <p class="muted"><strong>Pelaksanaan:</strong> {{ $event->executionStatus() }}</p>
                @endif

                @if($event->description)<p class="agenda-description">{{ $event->description }}</p>@endif
                @if($event->move_reason)<p class="error">Ruangan dipindahkan: {{ $event->move_reason }}</p>@endif
                @if($event->status === 'rejected' && $event->rejection_reason)
                    <p class="error"><strong>Keterangan penolakan:</strong> {{ $event->rejection_reason }}</p>
                @endif

                @if(auth()->user()->role?->name === 'admin')
                    <div class="actions agenda-admin-actions">
                        @if($event->status === 'pending')
                            <div class="agenda-review-actions">
                                <form method="post" class="agenda-review-approve" action="{{ route('events.status.update', $event) }}">
                                    @csrf
                                    @method('patch')
                                    <input type="hidden" name="status" value="approved">
                                    <button type="submit">Setujui</button>
                                </form>
                                <form method="post" class="agenda-review-reject" action="{{ route('events.status.update', $event) }}">
                                    @csrf
                                    @method('patch')
                                    <input type="hidden" name="status" value="rejected">
                                    <label for="rejection-reason-{{ $event->id }}">Keterangan penolakan</label>
                                    <div class="agenda-reject-controls">
                                        <input id="rejection-reason-{{ $event->id }}" name="rejection_reason" placeholder="Tulis alasan penolakan" required maxlength="2000">
                                        <button class="danger" type="submit">Tolak</button>
                                    </div>
                                </form>
                            </div>
                        @endif
                        <div class="agenda-utility-actions">
                            @if($event->status !== 'rejected')
                                <a class="btn warn" href="{{ route('events.edit', $event) }}">Kelola</a>
                            @else
                                <span class="muted">Agenda ditolak dan tidak dapat diedit atau dipindahkan.</span>
                            @endif
                            @if(in_array($event->status, ['pending', 'approved']))
                                @if($event->borrower_whatsapp)
                                <a class="btn" href="{{ route('events.whatsapp.form', $event) }}">WhatsApp</a>
                                @else
                                <span class="muted">Isi nomor peminjam di Kelola untuk WhatsApp.</span>
                                @endif
                            @endif
                            @if($event->canBeDeletedBy(auth()->user()))
                                <form method="post" class="agenda-delete-form" action="{{ route('events.destroy', $event) }}">
                                    @csrf
                                    @method('delete')
                                    <button class="danger" onclick="return confirm('Hapus agenda ini?')">Hapus</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
                @if(in_array(auth()->user()->role?->name, ['guru', 'organisasi']) && $event->canBeDeletedBy(auth()->user()))
                    <div class="actions agenda-utility-actions">
                        <form method="post" class="agenda-delete-form" action="{{ route('events.destroy', $event) }}">
                            @csrf
                            @method('delete')
                            <button class="danger" onclick="return confirm('Hapus agenda ini?')">Hapus</button>
                        </form>
                    </div>
                @endif
                @if($event->status === 'approved' && in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']) && (auth()->user()->role?->name === 'admin' || $event->user_id === auth()->id()))
                    <div class="actions agenda-complaint-action">
                        <a class="btn secondary" href="{{ route('complaints.create', ['event_id' => $event->id]) }}">Catat Barang Rusak</a>
                    </div>
                @endif
            </article>
        @empty
            <div class="card empty-state">Belum ada agenda ruangan.</div>
        @endforelse
    </div>
@endsection
