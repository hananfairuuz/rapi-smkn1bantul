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
                <div style="margin: 8px 0 4px; display: flex; flex-wrap: wrap; gap: 6px;">
                    @if(!empty($room->is_in_use))
                        <span class="badge" style="background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; font-size: 11px; padding: 3px 8px;">
                            🔴 Sedang Digunakan ({{ substr($room->current_event->start_time, 0, 5) }}–{{ substr($room->current_event->end_time, 0, 5) }} • {{ $room->current_event->borrower_name ?: $room->current_event->title }})
                        </span>
                    @else
                        <span class="badge" style="background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; font-size: 11px; padding: 3px 8px;">
                            🟢 Tersedia Sekarang
                        </span>
                    @endif
                    @if(!empty($room->has_active_bookings))
                        <span class="badge" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a; font-size: 11px; padding: 3px 8px;">
                            🔒 {{ $room->active_bookings_count }} Pembokingan Aktif
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
                    @php
                        $bookingPayload = collect($room->active_bookings ?? [])->map(fn ($ev) => [
                            'title' => $ev->title,
                            'borrower' => $ev->borrower_name ?: ($ev->user?->name ?? 'Peminjam'),
                            'date' => $ev->date?->format('d/m/Y') . ($ev->end_date && !$ev->end_date->isSameDay($ev->date) ? ' – ' . $ev->end_date->format('d/m/Y') : ''),
                            'time' => substr($ev->start_time, 0, 5) . '–' . substr($ev->end_time, 0, 5),
                            'status' => $ev->status === 'approved' ? 'Disetujui' : 'Menunggu Persetujuan',
                        ])->values();
                    @endphp
                    <div class="actions" style="margin-top: 14px; padding-top: 10px; border-top: 1px solid #f1f5f9;">
                        <a class="btn warn" href="{{ route('rooms.edit', $room) }}">Edit</a>
                        @if(!empty($room->has_active_bookings))
                            <button
                                type="button"
                                class="btn danger js-delete-room-blocked"
                                style="opacity: 0.9;"
                                data-room-id="{{ $room->id }}"
                                data-room-name="{{ $room->name }}"
                                data-destroy-url="{{ route('rooms.destroy', $room) }}"
                                data-bookings='@json($bookingPayload)'
                                title="Ruangan ini sedang dipinjam atau dibooking dan memerlukan pergantian ruangan sebelum dihapus"
                            >
                                🔒 Hapus
                            </button>
                        @else
                            <form method="post" action="{{ route('rooms.destroy', $room) }}" class="inline js-delete-room-form" data-room-name="{{ $room->name }}">
                                @csrf
                                @method('delete')
                                <button type="submit" class="danger">Hapus</button>
                            </form>
                        @endif
                    </div>
                @endif
            </article>
        @empty
            <p>Belum ada ruangan yang cocok.</p>
        @endforelse
    </div>

    @if($canManage)
        @php
            $replacementOptions = ($allRooms ?? $rooms)->map(fn ($r) => [
                'id' => $r->id,
                'name' => $r->name,
                'location' => $r->location,
                'capacity' => $r->capacity,
            ])->values();
        @endphp

        <!-- Modal Peringatan Ruangan Terkunci & Opsi Pergantian Ruangan -->
        <div class="rapi-modal-backdrop" id="roomReplaceDeleteModal" role="dialog" aria-modal="true" aria-labelledby="roomReplaceModalTitle">
            <div class="rapi-modal-card rapi-modal-card--wide">
                <div class="rapi-modal-header">
                    <div class="rapi-modal-icon rapi-modal-icon--danger">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    </div>
                    <div class="rapi-modal-title-wrap">
                        <h3 class="rapi-modal-title" id="roomReplaceModalTitle">Ruangan Tidak Dapat Dihapus</h3>
                        <p class="rapi-modal-subtitle" id="roomReplaceModalSubtitle"></p>
                    </div>
                    <button type="button" class="rapi-modal-close" id="roomReplaceCloseBtn" aria-label="Tutup">&times;</button>
                </div>

                <div class="rapi-modal-notice rapi-modal-notice--danger">
                    <div>
                        <strong>Pemberitahuan:</strong> Ruangan ini tidak dapat dihapus langsung karena sedang dipakai atau masih dalam pembokingan aktif. Jika ingin menghapus ruangan ini, <strong>harus ada pergantian ruangan terlebih dahulu</strong> untuk yang meminjam ruangan tersebut.
                    </div>
                </div>

                <div style="font-size: 12.5px; font-weight: 700; color: var(--muted); margin-top: 10px; text-transform: uppercase; letter-spacing: .04em;">
                    Daftar Peminjam Aktif / Dalam Pembokingan:
                </div>
                <div class="rapi-booking-list" id="roomActiveBookingsList"></div>

                <form method="post" id="roomReplaceDeleteForm" action="">
                    @csrf
                    @method('delete')

                    <label for="replacement_room_id" style="margin-top: 8px;">Pilih Ruangan Pengganti untuk Peminjam di Atas</label>
                    <select id="replacement_room_id" name="replacement_room_id" required></select>

                    <label for="replace_move_reason">Alasan Pergantian Ruangan (akan dikirim ke notifikasi peminjam)</label>
                    <textarea id="replace_move_reason" name="move_reason" rows="2" style="min-height: 70px;" required placeholder="Contoh: Ruangan sedang dalam perbaikan / dinonaktifkan, peminjam dipindahkan ke ruangan pengganti."></textarea>

                    <div class="rapi-modal-actions">
                        <button type="button" class="btn secondary" id="roomReplaceCancelBtn">Batal</button>
                        <button type="submit" class="btn danger">Pindahkan Peminjam &amp; Hapus Ruangan</button>
                    </div>
                </form>
            </div>
        </div>

        <script>
            (() => {
                const allRoomOptions = @json($replacementOptions);
                const modal = document.getElementById('roomReplaceDeleteModal');
                const subtitleEl = document.getElementById('roomReplaceModalSubtitle');
                const listEl = document.getElementById('roomActiveBookingsList');
                const formEl = document.getElementById('roomReplaceDeleteForm');
                const selectEl = document.getElementById('replacement_room_id');
                const reasonEl = document.getElementById('replace_move_reason');
                const closeBtn = document.getElementById('roomReplaceCloseBtn');
                const cancelBtn = document.getElementById('roomReplaceCancelBtn');

                function closeModal() {
                    modal.classList.remove('is-open');
                }

                // Konfirmasi CSS elegan untuk ruangan yang aman dihapus (tidak sedang dibooking)
                document.querySelectorAll('.js-delete-room-form').forEach(form => {
                    let confirmed = false;
                    form.addEventListener('submit', async (e) => {
                        if (confirmed) return;
                        e.preventDefault();
                        const roomName = form.dataset.roomName || 'ruangan ini';
                        const ok = await window.rapiDialog.confirm({
                            title: 'Hapus Ruangan?',
                            message: `Apakah Anda yakin ingin menghapus ruangan "${roomName}"? Tindakan ini akan menghapus data ruangan secara permanen.`,
                            variant: 'danger',
                            confirmText: 'Ya, Hapus Ruangan',
                            cancelText: 'Batal',
                        });
                        if (ok) {
                            confirmed = true;
                            form.submit();
                        }
                    });
                });

                // Modal blokir hapus + form pergantian ruangan untuk ruangan yang sedang dibooking/dipakai
                document.querySelectorAll('.js-delete-room-blocked').forEach(btn => {
                    btn.addEventListener('click', () => {
                        const roomId = Number(btn.dataset.roomId);
                        const roomName = btn.dataset.roomName || 'Ruangan';
                        const destroyUrl = btn.dataset.destroyUrl;
                        let bookings = [];
                        try {
                            bookings = JSON.parse(btn.dataset.bookings || '[]');
                        } catch (_) {}

                        subtitleEl.textContent = `Ruangan "${roomName}" sedang digunakan atau memiliki ${bookings.length} jadwal peminjaman aktif.`;
                        formEl.action = destroyUrl;
                        reasonEl.value = '';

                        listEl.innerHTML = '';
                        bookings.forEach(item => {
                            const row = document.createElement('div');
                            row.className = 'rapi-booking-item';
                            const title = document.createElement('strong');
                            title.textContent = `${item.title} (${item.status})`;
                            const meta = document.createElement('span');
                            meta.textContent = `Peminjam: ${item.borrower} • ${item.date} (${item.time})`;
                            row.appendChild(title);
                            row.appendChild(meta);
                            listEl.appendChild(row);
                        });

                        selectEl.innerHTML = '';
                        const placeholder = document.createElement('option');
                        placeholder.value = '';
                        placeholder.textContent = '-- Pilih ruangan pengganti --';
                        selectEl.appendChild(placeholder);

                        allRoomOptions
                            .filter(opt => opt.id !== roomId)
                            .forEach(opt => {
                                const optionEl = document.createElement('option');
                                optionEl.value = opt.id;
                                const cap = opt.capacity ? ` (${opt.capacity} org)` : '';
                                const loc = opt.location ? ` — ${opt.location}` : '';
                                optionEl.textContent = `${opt.name}${cap}${loc}`;
                                selectEl.appendChild(optionEl);
                            });

                        modal.classList.add('is-open');
                    });
                });

                closeBtn?.addEventListener('click', closeModal);
                cancelBtn?.addEventListener('click', closeModal);
                modal?.addEventListener('click', (e) => {
                    if (e.target === modal) closeModal();
                });
                document.addEventListener('keydown', (e) => {
                    if (e.key === 'Escape' && modal?.classList.contains('is-open')) {
                        closeModal();
                    }
                });
            })();
        </script>
    @endif
@endsection
