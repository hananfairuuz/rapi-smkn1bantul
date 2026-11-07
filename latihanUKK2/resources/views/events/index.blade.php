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
                    <div style="display: flex; gap: 6px; flex-wrap: wrap; align-items: center; justify-content: flex-end;">
                        <span class="badge {{ $event->status === 'approved' ? 'approved' : ($event->status === 'rejected' ? 'rejected' : 'pending') }}">{{ $event->status === 'approved' ? 'DISETUJUI' : ($event->status === 'rejected' ? 'DITOLAK' : 'PENDING') }}</span>
                        @if($event->deleted_by_creator_at)
                            <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 11px;" title="Peminjam telah menghapus agenda ini dari daftar riwayat pribadinya">
                                Riwayat Dihapus Pemohon
                            </span>
                        @endif
                    </div>
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
                @if($event->status === 'approved')
                    <div class="actions agenda-complaint-action" style="display:flex; flex-wrap:wrap; align-items:center; gap:8px;">
                        <a class="btn secondary" target="_blank" href="{{ route('events.receipt', $event) }}" title="Lihat dan cetak surat izin resmi">
                            <svg style="width:14px;height:14px; vertical-align:-2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                            Surat Izin Ruangan
                        </a>

                        @if($event->returned_at)
                            <span class="badge approved" style="background:#dcfce7; color:#15803d; border:1px solid #86efac; font-size:11.5px; padding:6px 10px;">
                                ✓ Dikembalikan ({{ $event->returned_at->format('d/m/Y H:i') }})
                            </span>
                        @elseif($event->canBeCheckedOut() && (auth()->user()->role?->name === 'admin' || $event->user_id === auth()->id() || (auth()->user()->role?->name === 'pic' && $event->room->pic_id === auth()->id())))
                            <details style="display:inline-block; position:relative;">
                                <summary class="btn" style="background:#0284c7; border-color:#0369a1; color:#fff; cursor:pointer;">
                                    Serah Terima / Pengembalian
                                </summary>
                                <div style="position:absolute; right:0; z-index:10; background:#fff; border:1px solid var(--line); border-radius:8px; box-shadow:0 8px 24px rgba(0,0,0,0.15); padding:16px; width:300px; margin-top:8px;">
                                    <h4 style="margin:0 0 8px; font-size:13.5px;">Konfirmasi Pengembalian Ruangan</h4>
                                    <form method="post" action="{{ route('events.checkout', $event) }}">
                                        @csrf
                                        <label style="font-size:12px; margin:6px 0 2px;">Kondisi Ruangan &amp; Fasilitas:</label>
                                        <select name="checkout_condition" required style="font-size:12px; padding:6px;">
                                            <option value="aman">🟢 Aman, Bersih, &amp; Lengkap</option>
                                            <option value="rusak">🔴 Ada Fasilitas Rusak / Masalah</option>
                                        </select>
                                        <label style="font-size:12px; margin:8px 0 2px;">Catatan (Opsional):</label>
                                        <textarea name="checkout_notes" rows="2" style="font-size:12px; padding:6px; min-height:50px;" placeholder="Lampu & AC telah dimatikan, kunci dikembalikan..."></textarea>
                                        <div style="margin-top:10px; display:flex; justify-content:flex-end; gap:6px;">
                                            <button type="submit" class="btn" style="font-size:12px; padding:6px 12px;">Konfirmasi</button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        @endif

                        @if(in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']) && (auth()->user()->role?->name === 'admin' || $event->user_id === auth()->id()))
                            <a class="btn secondary" href="{{ route('complaints.create', ['event_id' => $event->id]) }}">Catat Barang Rusak</a>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <div class="card empty-state">Belum ada agenda ruangan.</div>
        @endforelse
    </div>
@endsection
