@extends('layouts.app')

@section('content')
<div class="row users-heading">
    <div>
        <h1>Pengguna &amp; Peran</h1>
        <p class="muted">Kelola pengguna berdasarkan kelompok perannya.</p>
    </div>
    <div style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
        <a class="btn secondary" href="{{ route('users.import') }}">📥 Import dari Dokumen</a>
        <a class="btn" href="{{ route('users.create') }}">+ Tambah Pengguna</a>
    </div>
</div>

<form method="get" action="{{ route('users.index') }}" class="card user-search">
    <label for="user-search">Cari pengguna</label>
    <div class="user-search-controls">
        <input id="user-search" type="search" name="search" value="{{ $search }}" placeholder="Nama, email, WhatsApp, atau peran">
        <button type="submit">Cari</button>
        @if($search !== '')
            <a class="btn secondary" href="{{ route('users.index') }}">Hapus pencarian</a>
        @endif
    </div>
</form>

@forelse($roleGroups as $group)    <section class="card user-group">
        <header class="user-group-header">
            <div>
                <h2>{{ $group['label'] }}</h2>
                <p class="muted">Daftar akun dengan peran {{ strtolower($group['label']) }}.</p>
            </div>
            <span class="badge approved">{{ $group['users']->count() }} pengguna</span>
        </header>
        <div class="table-card">
            <table>
                <thead>
                    <tr><th>Nama</th><th>Email</th><th>WhatsApp</th><th>Aksi</th></tr>
                </thead>
                <tbody>
                    @foreach($group['users'] as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->whatsapp ?: '—' }}</td>
                            <td>
                                <div class="user-actions">
                                    <a class="btn warn" href="{{ route('users.edit', $user) }}">Ubah</a>
                                    @php
                                        $userRooms = $user->roomsAsPic ?? collect();
                                    @endphp
                                    <form method="post" action="{{ route('users.destroy', $user) }}"
                                          class="js-delete-user-form"
                                          data-user-name="{{ $user->name }}"
                                          data-user-role="{{ $user->role?->name }}"
                                          data-rooms='@json($userRooms->map(fn($r) => ["id" => $r->id, "name" => $r->name]))'>
                                        @csrf
                                        @method('delete')
                                        <button type="submit" class="danger">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
@empty
    <div class="card empty-state">{{ $search !== '' ? 'Tidak ada pengguna yang cocok dengan pencarian.' : 'Belum ada pengguna.' }}</div>
@endforelse

{{-- Modal Pilih PIC Pengganti (muncul saat hapus PIC yang masih punya ruangan) --}}
<div class="rapi-modal-backdrop" id="picReplaceModal" role="dialog" aria-modal="true" aria-labelledby="picReplaceModalTitle">
    <div class="rapi-modal-card">
        <div class="rapi-modal-header">
            <div class="rapi-modal-icon rapi-modal-icon--danger">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/></svg>
            </div>
            <div class="rapi-modal-title-wrap">
                <h3 class="rapi-modal-title" id="picReplaceModalTitle">PIC Masih Memiliki Ruangan</h3>
                <p class="rapi-modal-subtitle" id="picReplaceModalSubtitle"></p>
            </div>
            <button type="button" class="rapi-modal-close" id="picReplaceCloseBtn" aria-label="Tutup">&times;</button>
        </div>

        <div class="rapi-modal-notice rapi-modal-notice--warning">
            <div>
                <strong>Perlu tindakan:</strong> PIC ini bertanggung jawab atas beberapa ruangan. Pilih PIC pengganti untuk mengambil alih ruangan tersebut sebelum akun dihapus.
            </div>
        </div>

        <div style="font-size: 12.5px; font-weight: 700; color: var(--muted); margin-top: 10px; text-transform: uppercase; letter-spacing: .04em;">Ruangan yang akan dialihkan:</div>
        <div id="picReplaceRoomList" class="rapi-booking-list"></div>

        <form method="post" id="picReplaceForm" action="">
            @csrf
            @method('delete')
            <input type="hidden" name="replacement_pic_id" id="replacementPicId">

            <label for="picReplaceSelect" style="margin-top: 14px;">Pilih PIC Pengganti</label>
            <select id="picReplaceSelect" required>
                <option value="">-- Pilih PIC pengganti --</option>
                @foreach($allPics as $pic)
                    <option value="{{ $pic->id }}">{{ $pic->name }} ({{ $pic->email }})</option>
                @endforeach
            </select>

            <div class="rapi-modal-actions" style="margin-top: 16px;">
                <button type="button" class="btn secondary" id="picReplaceCancelBtn">Batal</button>
                <button type="submit" class="btn danger">Alihkan Ruangan &amp; Hapus Pengguna</button>
            </div>
        </form>
    </div>
</div>

<script>
    (() => {
        const modal = document.getElementById('picReplaceModal');
        const subtitleEl = document.getElementById('picReplaceModalSubtitle');
        const roomListEl = document.getElementById('picReplaceRoomList');
        const form = document.getElementById('picReplaceForm');
        const selectEl = document.getElementById('picReplaceSelect');
        const replacementInput = document.getElementById('replacementPicId');
        const closeBtn = document.getElementById('picReplaceCloseBtn');
        const cancelBtn = document.getElementById('picReplaceCancelBtn');

        function closeModal(resetSelection = true) {
            modal.classList.remove('is-open');
            if (resetSelection) {
                selectEl.value = '';
                replacementInput.value = '';
            }
        }

        closeBtn?.addEventListener('click', closeModal);
        cancelBtn?.addEventListener('click', closeModal);
        modal?.addEventListener('click', e => { if (e.target === modal) closeModal(); });
        document.addEventListener('keydown', e => { if (e.key === 'Escape' && modal?.classList.contains('is-open')) closeModal(); });

        // Sync select → hidden input
        selectEl.addEventListener('change', () => { replacementInput.value = selectEl.value; });

        document.querySelectorAll('.js-delete-user-form').forEach(deleteForm => {
            let confirmed = false;
            deleteForm.addEventListener('submit', async (e) => {
                if (confirmed) return;
                e.preventDefault();

                const userName = deleteForm.dataset.userName || 'pengguna ini';
                const userRole = deleteForm.dataset.userRole || '';
                let rooms = [];
                try { rooms = JSON.parse(deleteForm.dataset.rooms || '[]'); } catch (_) {}

                // Jika PIC dan punya ruangan → tampilkan modal pilih pengganti
                if (userRole === 'pic' && rooms.length > 0) {
                    subtitleEl.textContent = `"${userName}" bertanggung jawab atas ${rooms.length} ruangan berikut:`;
                    roomListEl.innerHTML = '';
                    rooms.forEach(r => {
                        const item = document.createElement('div');
                        item.className = 'rapi-booking-item';
                        item.innerHTML = `<strong>${r.name}</strong>`;
                        roomListEl.appendChild(item);
                    });

                    // Hapus opsi milik user yang akan dihapus dari dropdown
                    const userId = deleteForm.action.split('/').pop();
                    Array.from(selectEl.options).forEach(opt => {
                        opt.disabled = (opt.value && opt.value === userId);
                    });

                    form.action = deleteForm.action;
                    form.onsubmit = (ev) => {
                        if (!selectEl.value) {
                            ev.preventDefault();
                            selectEl.focus();
                            return;
                        }
                        replacementInput.value = selectEl.value;
                        confirmed = true;
                        closeModal(false);
                        // submit via fetch tidak perlu — form langsung submit
                    };

                    modal.classList.add('is-open');
                    return;
                }

                // User biasa (bukan PIC) atau PIC tanpa ruangan → konfirmasi biasa
                const ok = await window.rapiDialog.confirm({
                    title: 'Hapus Pengguna?',
                    message: `Apakah Anda yakin ingin menghapus akun "${userName}"?`,
                    variant: 'danger',
                    confirmText: 'Ya, Hapus',
                    cancelText: 'Batal',
                });
                if (ok) {
                    confirmed = true;
                    deleteForm.submit();
                }
            });
        });
    })();
</script>
@endsection
