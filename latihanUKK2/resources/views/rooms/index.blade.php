@extends('layouts.app')

@section('content')
    @php
        $canManage = $canManage ?? (auth()->user()->role?->name === 'admin');
    @endphp

    <div class="row">
        <div>
            <h1>Kelola Ruangan</h1>
            <p class="muted">{{ auth()->user()->role?->name === 'pic' ? 'Ruangan yang menjadi tanggung jawab Anda.' : 'Daftar fasilitas ruangan dan kapasitas sekolah.' }}</p>
        </div>
        @if($canManage)
            <a class="btn" href="{{ route('rooms.create') }}">+ Tambah Ruangan</a>
        @endif
    </div>

    <form method="get" action="{{ route('rooms.index') }}" class="card actions" style="margin-bottom: 14px;">
        <label for="room-search">Cari nama ruangan</label>
        <input id="room-search" type="search" name="search" value="{{ $search }}" placeholder="Contoh: Laboratorium">
        <input type="hidden" name="status" value="{{ $statusFilter ?? 'all' }}">
        <button type="submit">Cari</button>
        @if($search !== '')
            <a class="btn secondary" href="{{ route('rooms.index', ['status' => $statusFilter ?? 'all']) }}">Hapus pencarian</a>
        @endif
    </form>

    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a class="btn {{ ($statusFilter ?? 'all') === 'all' ? '' : 'secondary' }}" href="{{ route('rooms.index', ['search' => $search, 'status' => 'all']) }}">
            Semua Status
        </a>
        <a class="btn {{ ($statusFilter ?? '') === 'available' ? '' : 'secondary' }}" href="{{ route('rooms.index', ['search' => $search, 'status' => 'available']) }}">
            🟢 Tersedia Sekarang
        </a>
        <a class="btn {{ ($statusFilter ?? '') === 'in_use' ? '' : 'secondary' }}" href="{{ route('rooms.index', ['search' => $search, 'status' => 'in_use']) }}">
            🔴 Sedang Digunakan
        </a>
    </div>

    <div class="grid">
        @forelse($rooms as $room)
            <article class="card">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px;">
                    <h3 style="margin: 0;">{{ $room->name }}</h3>
                    <span style="display: inline-block; background: #e0f2fe; color: #0369a1; font-size: 12px; font-weight: 700; padding: 2px 8px; border-radius: 12px; white-space: nowrap;">
                        {{ $room->capacity ? $room->capacity . ' orang' : 'Kapasitas -' }}
                    </span>
                </div>
                <div style="margin: 8px 0 4px;">
                    @if(!empty($room->is_in_use))
                        <span class="badge" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 11px; padding: 3px 8px;">
                            🔴 Sedang Digunakan ({{ substr($room->current_event->start_time, 0, 5) }}–{{ substr($room->current_event->end_time, 0, 5) }} • {{ $room->current_event->borrower_name ?: $room->current_event->title }})
                        </span>
                    @else
                        <span class="badge" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; font-size: 11px; padding: 3px 8px;">
                            🟢 Tersedia Sekarang
                        </span>
                    @endif
                </div>
                <p style="margin: 6px 0 0; color: #475569; font-size: 13.5px;">
                    <svg style="width: 14px; height: 14px; vertical-align: -2px; margin-right: 4px; display: inline-block;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                    {{ $room->location ?: 'Lokasi belum diisi' }}
                </p>
                @if($room->description)
                    <p class="muted" style="margin: 8px 0; font-size: 13px;">{{ $room->description }}</p>
                @endif
                <p style="margin: 8px 0 0; font-size: 13px;">PIC: <strong>{{ $room->pic?->name ?? 'Belum ditetapkan' }}</strong></p>
                @if($canManage)
                    <div class="actions" style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                        <a class="btn warn" href="{{ route('rooms.edit', $room) }}">Edit</a>
                        <form method="post" action="{{ route('rooms.destroy', $room) }}" class="inline">
                            @csrf
                            @method('delete')
                            <button class="danger" onclick="return confirm('Hapus ruangan ini?')">Hapus</button>
                        </form>
                    </div>
                @endif
            </article>
        @empty
            <p>Belum ada ruangan yang cocok.</p>
        @endforelse
    </div>
@endsection
