@extends('layouts.app')

@section('content')
    @php
        $startLessonValue = old('start_lesson', $event->exists ? ($selectedStartLesson ?? 'custom') : '');
        $endLessonValue = old('end_lesson', $event->exists ? ($selectedEndLesson ?? 'custom') : '');
    @endphp
    <div class="page-heading">
        <div>
            <h1>{{ $event->exists ? 'Kelola' : 'Ajukan' }} Agenda</h1>
            <p>Jadwal yang bertabrakan akan ditolak otomatis.</p>
            @unless($event->exists)
                <p class="muted">Peminjaman harus diajukan paling lambat satu hari sebelum ruangan digunakan (H-1).</p>
            @endunless
        </div>
    </div>
    <form class="card form-card" method="post" action="{{ $event->exists ? route('events.update', $event) : route('events.store') }}" id="event-form">
        @csrf
        @if($event->exists) @method('put') @endif
        <div class="form-grid">
            <div class="form-field">
                <label for="title">Judul agenda</label>
                <input id="title" name="title" value="{{ old('title', $event->title) }}" required>
            </div>
            <div class="form-field">
                <label for="borrower_name">Nama peminjam ruangan</label>
                <input id="borrower_name" name="borrower_name" value="{{ old('borrower_name', $event->borrower_name) }}" required>
            </div>
            <div class="form-field">
                <label for="borrower_whatsapp">Nomor WhatsApp peminjam</label>
                <input id="borrower_whatsapp" name="borrower_whatsapp" value="{{ old('borrower_whatsapp', $event->borrower_whatsapp) }}" inputmode="tel" pattern="\+?[0-9]{8,20}" placeholder="62812..." required>
                <p class="muted">Pesan WhatsApp agenda akan ditujukan ke nomor peminjam ini.</p>
            </div>
            @if($event->exists)
                <div class="form-field">
                    <label for="room_id">Ruangan</label>
                    <select id="room_id" name="room_id" required>
                        <option value="">Pilih ruangan</option>
                        @foreach($rooms as $room)
                            <option value="{{ $room->id }}" @selected(old('room_id', $event->room_id) == $room->id)>{{ $room->name }} — {{ $room->location }}</option>
                        @endforeach
                    </select>
                </div>
            @else
                <div class="form-field form-wide multi-room-dropdown-wrapper" id="multi-room-container">
                    <div class="multi-room-header-row">
                        <label class="multi-room-title">
                            Pilih Ruangan <span class="multi-room-badge-pill">Bisa pilih lebih dari 1 ruangan</span>
                        </label>
                        <span id="selected-room-count" class="selected-count-badge">0 dipilih</span>
                    </div>
                    <p class="muted" style="margin: 2px 0 8px; font-size: 13px;">Pilih satu atau beberapa ruangan sekaligus untuk kegiatan ini.</p>

                    <!-- Trigger Box Ringkas & Rapi -->
                    <div class="multi-room-trigger" id="multi-room-trigger" tabindex="0" role="button" aria-haspopup="true" aria-expanded="false">
                        <div class="trigger-left">
                            <svg class="trigger-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 20.5V5.8a1.8 1.8 0 0 1 1.3-1.7l11-3A1.8 1.8 0 0 1 18.5 2.8v17.7M2.5 20.5h19M8 8v.01M8 12v.01M8 16v.01M12 7v.01M12 11v.01M12 15v.01M15.5 12.5h.01"/></svg>
                            <span id="trigger-placeholder-text" class="trigger-text">Klik untuk cari & pilih ruangan...</span>
                        </div>
                        <div class="trigger-right">
                            <span class="trigger-action-hint">Pilih Ruangan</span>
                            <svg class="trigger-arrow" id="trigger-arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="6 9 12 15 18 9"></polyline></svg>
                        </div>
                    </div>

                    <!-- Selected Chips / Ruangan yang Dipilih -->
                    <div class="selected-chips-wrap" id="selected-chips-wrap" style="display: none;">
                        <span class="chips-label">Ruangan Terpilih:</span>
                        <div class="selected-chips-list" id="selected-chips-list"></div>
                    </div>

                    <!-- Popover Dropdown Panel (Muncul saat diklik) -->
                    <div class="room-popover-panel" id="room-popover-panel" style="display: none;">
                        <div class="popover-search-bar">
                            <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                            <input type="text" id="room-search-input" placeholder="Cari nama ruangan atau lokasi (misal: Lab, Gedung A)..." autocomplete="off">
                            <button type="button" id="btn-clear-search" class="btn-clear-search" title="Bersihkan pencarian" style="display: none;">×</button>
                        </div>

                        <div class="popover-action-bar">
                            <span id="search-results-info" class="search-results-info">Total {{ count($rooms) }} ruangan</span>
                            <div class="popover-actions-btns">
                                <button type="button" class="btn-popover-action" id="btn-select-all-rooms">Pilih Semua</button>
                                <button type="button" class="btn-popover-action" id="btn-clear-rooms">Batal Pilih</button>
                            </div>
                        </div>

                        <div class="rooms-popover-list" id="rooms-popover-list">
                            @foreach($rooms as $room)
                                <label class="room-item-row" for="room-chk-{{ $room->id }}" data-name="{{ strtolower($room->name) }}" data-loc="{{ strtolower($room->location ?? '') }}">
                                    <input type="checkbox" id="room-chk-{{ $room->id }}" name="room_ids[]" value="{{ $room->id }}" class="room-chk-input" data-name="{{ $room->name }}" data-loc="{{ $room->location ?: 'SMKN 1 Bantul' }}" data-cap="{{ $room->capacity ? $room->capacity . ' orang' : '' }}" @checked(in_array($room->id, (array) old('room_ids', [])))>
                                    <div class="room-item-details">
                                        <div class="room-item-name">{{ $room->name }}</div>
                                        <div class="room-item-meta">
                                            <span>{{ $room->location ?: 'SMKN 1 Bantul' }}</span>
                                            @if($room->capacity)
                                                <span class="room-meta-divider">·</span>
                                                <span class="room-item-cap"><svg style="width: 12px; height: 12px; vertical-align: -1px; margin-right: 2px;" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>{{ $room->capacity }} orang</span>
                                            @endif
                                        </div>
                                    </div>
                                </label>
                            @endforeach
                            <div id="no-rooms-match" class="no-rooms-match" style="display: none;">
                                Tidak ada ruangan yang cocok dengan pencarian Anda.
                            </div>
                        </div>

                        <div class="popover-footer">
                            <span class="popover-footer-hint">Centang ruangan yang diinginkan</span>
                            <button type="button" class="btn-popover-done" id="btn-popover-done">Tutup & Terapkan</button>
                        </div>
                    </div>
                    @error('room_ids')<p class="error" style="margin-top: 8px;">{{ $message }}</p>@enderror
                </div>
            @endif
            <div class="booking-fields booking-dates form-wide">
                <div class="form-field">
                    <label for="date">Tanggal</label>
                    <input id="date" type="date" name="date" value="{{ old('date', $event->date?->format('Y-m-d')) }}" @unless($event->exists) min="{{ $minimumBookingDate }}" @endunless required>
                    @error('date')<p class="error">{{ $message }}</p>@enderror
                </div>
                <div class="form-field">
                    <label for="end_date">Tanggal selesai</label>
                    <input id="end_date" type="date" name="end_date" value="{{ old('end_date', $event->end_date?->format('Y-m-d') ?? $event->date?->format('Y-m-d')) }}" min="{{ old('date', $event->date?->format('Y-m-d')) }}" required>
                    <p class="muted">{{ $isAdmin ? 'Agenda yang dibuat admin langsung disetujui.' : 'Pengajuan peminjaman lebih dari satu hari tetap menunggu persetujuan admin.' }} Ruangan dianggap terpakai terus sampai tanggal dan jam selesai.</p>
                    @error('end_date')<p class="error">{{ $message }}</p>@enderror
                </div>
            </div>
            <div class="booking-fields booking-times form-wide">
                <div class="form-field">
                    <label for="start_lesson">Jam mulai</label>
                    <select id="start_lesson" name="start_lesson" required>
                        <option value="">Pilih jam mulai</option>
                        @foreach($lessonPeriods as $lesson => $period)
                            <option value="{{ $lesson }}" @selected($startLessonValue == $lesson)>
                                Jam ke-{{ $lesson }} ({{ $period['start'] }}–{{ $period['end'] }})
                            </option>
                        @endforeach
                        <option value="custom" @selected($startLessonValue === 'custom')>Jam bebas (setelah jam ke-14)</option>
                    </select>
                    <div id="start-custom-time-field" @if($startLessonValue !== 'custom') hidden @endif>
                        <label for="start_custom_time">Jam mulai bebas</label>
                        <input id="start_custom_time" name="start_custom_time" type="time" value="{{ old('start_custom_time', $event->exists && $selectedStartLesson === null ? substr($event->start_time, 0, 5) : '') }}">
                        @error('start_custom_time')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
                <div class="form-field">
                    <label for="end_lesson">Jam selesai</label>
                    <select id="end_lesson" name="end_lesson" required>
                        <option value="">Pilih jam selesai</option>
                        @foreach($lessonPeriods as $lesson => $period)
                            <option value="{{ $lesson }}" @selected($endLessonValue == $lesson)>
                                Jam ke-{{ $lesson }} ({{ $period['start'] }}–{{ $period['end'] }})
                            </option>
                        @endforeach
                        <option value="custom" @selected($endLessonValue === 'custom')>Jam bebas</option>
                    </select>
                    <div id="end-custom-time-field" @if($endLessonValue !== 'custom') hidden @endif>
                        <label for="end_custom_time">Jam selesai bebas</label>
                        <input id="end_custom_time" name="end_custom_time" type="time" value="{{ old('end_custom_time', $event->exists && $selectedEndLesson === null ? substr($event->end_time, 0, 5) : '') }}">
                        @error('end_custom_time')<p class="error">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>
            @error('start_lesson')<p class="error form-wide">{{ $message }}</p>@enderror
            @error('end_lesson')<p class="error form-wide">{{ $message }}</p>@enderror
            <p class="muted form-wide form-note">Satu jam pelajaran berlangsung 45 menit. Jam ke-1 dimulai pukul 07.15 dan jam ke-14 berakhir pukul 17.45. Jam mulai bebas diisi pukul 17.45–23.59, dan jam selesai bebas dapat diisi sampai pukul 06.00 hari berikutnya.</p>
            <p id="availability-message" class="muted form-wide form-availability">Pilih ruangan, tanggal, dan jam pelajaran untuk memeriksa ketersediaan.</p>

            <div class="form-field form-wide">
                <label for="description">Deskripsi</label>
                <textarea id="description" name="description">{{ old('description', $event->description) }}</textarea>
            </div>
            @if($event->exists)
                <div class="form-field form-wide">
                    <label for="move_reason">Alasan pemindahan ruang <small>(wajib jika ruangan diganti)</small></label>
                    <textarea id="move_reason" name="move_reason">{{ old('move_reason', $event->move_reason) }}</textarea>
                </div>
            @endif
            <div class="actions form-wide">
                <button id="submit-button" type="submit">Simpan</button>
                <a class="btn" href="{{ route('events.index') }}">Batal</a>
            </div>
        </div>
    </form>

    <style>
        .multi-room-dropdown-wrapper {
            position: relative;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .multi-room-header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
        }
        .multi-room-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
            margin: 0;
        }
        .multi-room-badge-pill {
            display: inline-block;
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 11px;
            font-weight: 700;
            padding: 2px 8px;
            border-radius: 12px;
        }
        .selected-count-badge {
            background: #0b192e;
            color: #fff;
            font-size: 11.5px;
            font-weight: 700;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.2px;
        }

        /* Trigger Box */
        .multi-room-trigger {
            background: #fff;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            padding: 10px 14px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s ease;
            user-select: none;
            outline: none;
        }
        .multi-room-trigger:hover,
        .multi-room-trigger:focus,
        .multi-room-trigger.is-open {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.15);
        }
        .trigger-left {
            display: flex;
            align-items: center;
            gap: 10px;
            overflow: hidden;
        }
        .trigger-icon {
            width: 20px;
            height: 20px;
            color: #f59e0b;
            flex-shrink: 0;
        }
        .trigger-text {
            font-size: 13.5px;
            font-weight: 600;
            color: #1e293b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .trigger-right {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-shrink: 0;
        }
        .trigger-action-hint {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }
        .trigger-arrow {
            width: 16px;
            height: 16px;
            color: #64748b;
            transition: transform 0.2s ease;
        }
        .multi-room-trigger.is-open .trigger-arrow {
            transform: rotate(180deg);
        }

        /* Selected Chips */
        .selected-chips-wrap {
            margin-top: 10px;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 6px;
            padding: 8px 10px;
            background: #fff;
            border: 1px dashed #cbd5e1;
            border-radius: 8px;
        }
        .chips-label {
            font-size: 11.5px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            margin-right: 4px;
        }
        .selected-chips-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }
        .room-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            padding: 3px 8px 3px 10px;
            border-radius: 14px;
            font-size: 12px;
            font-weight: 600;
            animation: chipFadeIn 0.15s ease-out;
        }
        @keyframes chipFadeIn {
            from { opacity: 0; transform: scale(0.92); }
            to { opacity: 1; transform: scale(1); }
        }
        .room-chip-cap {
            background: #fef3c7;
            color: #b45309;
            font-size: 10px;
            padding: 1px 5px;
            border-radius: 8px;
            border: 1px solid #fde68a;
        }
        .room-chip-remove {
            border: none;
            background: rgba(146, 64, 14, 0.12);
            color: #92400e;
            width: 16px;
            height: 16px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            transition: background 0.15s;
        }
        .room-chip-remove:hover {
            background: #ef4444;
            color: #fff;
        }

        /* Popover Dropdown Panel */
        .room-popover-panel {
            position: absolute;
            top: calc(100% + 4px);
            left: 0;
            right: 0;
            z-index: 999;
            background: #ffffff;
            border: 1.5px solid #cbd5e1;
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.16), 0 4px 10px rgba(15, 23, 42, 0.08);
            padding: 12px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .popover-search-bar {
            position: relative;
            display: flex;
            align-items: center;
        }
        .popover-search-bar .search-icon {
            position: absolute;
            left: 10px;
            width: 16px;
            height: 16px;
            color: #94a3b8;
            pointer-events: none;
        }
        .popover-search-bar input {
            width: 100%;
            padding: 9px 32px 9px 34px !important;
            font-size: 13px !important;
            border: 1.5px solid #e2e8f0 !important;
            border-radius: 8px !important;
            outline: none !important;
            background: #f8fafc;
            transition: all 0.2s;
        }
        .popover-search-bar input:focus {
            border-color: #f59e0b !important;
            background: #fff;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.15);
        }
        .btn-clear-search {
            position: absolute;
            right: 8px;
            border: none;
            background: #e2e8f0;
            color: #64748b;
            width: 18px;
            height: 18px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            cursor: pointer;
            padding: 0;
        }
        .btn-clear-search:hover {
            background: #cbd5e1;
            color: #0f172a;
        }

        .popover-action-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 2px 4px;
        }
        .search-results-info {
            font-size: 11.5px;
            color: #64748b;
            font-weight: 600;
        }
        .popover-actions-btns {
            display: flex;
            gap: 6px;
        }
        .btn-popover-action {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #334155;
            font-size: 11.5px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.15s;
        }
        .btn-popover-action:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Scrollable list */
        .rooms-popover-list {
            max-height: 230px;
            overflow-y: auto;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 4px;
            display: flex;
            flex-direction: column;
            gap: 4px;
            background: #ffffff;
        }
        .rooms-popover-list::-webkit-scrollbar {
            width: 6px;
        }
        .rooms-popover-list::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 4px;
        }

        .room-item-row {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.15s, border-color 0.15s;
            border: 1px solid transparent;
            user-select: none;
        }
        .room-item-row:hover {
            background: #f8fafc;
            border-color: #e2e8f0;
        }
        .room-item-row:has(.room-chk-input:checked) {
            background: #fffdf5;
            border-color: #fde68a;
        }
        .room-chk-input {
            accent-color: #f59e0b;
            width: 17px;
            height: 17px;
            cursor: pointer;
            flex-shrink: 0;
            margin: 0;
        }
        .room-item-details {
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex-grow: 1;
        }
        .room-item-name {
            font-size: 13px;
            font-weight: 700;
            color: #1e293b;
        }
        .room-item-meta {
            font-size: 11.5px;
            color: #64748b;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .room-meta-divider {
            color: #cbd5e1;
        }
        .room-item-cap {
            color: #0369a1;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
        }
        .no-rooms-match {
            padding: 24px 12px;
            text-align: center;
            font-size: 12.5px;
            color: #94a3b8;
            font-style: italic;
        }

        .popover-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 4px;
            border-top: 1px solid #f1f5f9;
        }
        .popover-footer-hint {
            font-size: 11.5px;
            color: #94a3b8;
        }
        .btn-popover-done {
            background: #f59e0b;
            color: #fff;
            border: none;
            padding: 6px 14px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
        }
        .btn-popover-done:hover {
            background: #d97706;
        }
    </style>

    <script>
        const roomField = document.getElementById('room_id');
        const dateField = document.getElementById('date');
        const endDateField = document.getElementById('end_date');
        const startLessonField = document.getElementById('start_lesson');
        const endLessonField = document.getElementById('end_lesson');
        const startCustomTimeField = document.getElementById('start_custom_time');
        const endCustomTimeField = document.getElementById('end_custom_time');
        const startCustomTimeContainer = document.getElementById('start-custom-time-field');
        const endCustomTimeContainer = document.getElementById('end-custom-time-field');
        const availabilityMessage = document.getElementById('availability-message');
        const submitButton = document.getElementById('submit-button');
        const minimumBookingDate = '{{ $minimumBookingDate }}';

        function getSelectedRoomIds() {
            if (roomField) {
                return roomField.value ? [roomField.value] : [];
            }
            const checked = document.querySelectorAll('.room-chk-input:checked');
            return Array.from(checked).map(chk => chk.value);
        }

        function updateSelectedChips() {
            const checkedInputs = Array.from(document.querySelectorAll('.room-chk-input:checked'));
            const chipsWrap = document.getElementById('selected-chips-wrap');
            const chipsList = document.getElementById('selected-chips-list');
            const triggerText = document.getElementById('trigger-placeholder-text');
            const countBadge = document.getElementById('selected-room-count');

            if (countBadge) {
                countBadge.textContent = checkedInputs.length + ' dipilih';
            }

            if (!chipsWrap || !chipsList || !triggerText) return;

            if (checkedInputs.length === 0) {
                chipsWrap.style.display = 'none';
                chipsList.innerHTML = '';
                triggerText.textContent = 'Klik untuk cari & pilih ruangan...';
                triggerText.style.color = '#64748b';
            } else {
                chipsWrap.style.display = 'flex';
                triggerText.textContent = checkedInputs.length + ' ruangan dipilih (Klik untuk ubah)';
                triggerText.style.color = '#0f172a';

                chipsList.innerHTML = checkedInputs.map(input => {
                    const id = input.value;
                    const name = input.getAttribute('data-name') || ('Ruangan #' + id);
                    const cap = input.getAttribute('data-cap');
                    return `
                        <span class="room-chip" data-id="${id}">
                            <span>${name}</span>
                            ${cap ? `<span class="room-chip-cap">${cap}</span>` : ''}
                            <button type="button" class="room-chip-remove" data-id="${id}" title="Hapus ${name}">×</button>
                        </span>
                    `;
                }).join('');

                // Pasang event listener tombol silang (x) pada chip
                chipsList.querySelectorAll('.room-chip-remove').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.stopPropagation();
                        const rId = btn.getAttribute('data-id');
                        const chk = document.getElementById('room-chk-' + rId);
                        if (chk) {
                            chk.checked = false;
                        }
                        updateSelectedChips();
                        checkAvailability();
                    });
                });
            }
        }

        // Toggle Popover Dropdown
        const triggerBox = document.getElementById('multi-room-trigger');
        const popoverPanel = document.getElementById('room-popover-panel');
        const searchInput = document.getElementById('room-search-input');
        const btnClearSearch = document.getElementById('btn-clear-search');
        const btnDone = document.getElementById('btn-popover-done');

        function openPopover() {
            if (!popoverPanel) return;
            popoverPanel.style.display = 'flex';
            triggerBox?.classList.add('is-open');
            triggerBox?.setAttribute('aria-expanded', 'true');
            if (searchInput) {
                setTimeout(() => searchInput.focus(), 60);
            }
        }

        function closePopover() {
            if (!popoverPanel) return;
            popoverPanel.style.display = 'none';
            triggerBox?.classList.remove('is-open');
            triggerBox?.setAttribute('aria-expanded', 'false');
        }

        if (triggerBox && popoverPanel) {
            triggerBox.addEventListener('click', (e) => {
                e.stopPropagation();
                if (popoverPanel.style.display === 'none') {
                    openPopover();
                } else {
                    closePopover();
                }
            });

            btnDone?.addEventListener('click', (e) => {
                e.stopPropagation();
                closePopover();
            });

            // Tutup jika klik di luar
            document.addEventListener('click', (e) => {
                const container = document.getElementById('multi-room-container');
                if (container && !container.contains(e.target)) {
                    closePopover();
                }
            });

            // Tutup jika tekan tombol Escape
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') closePopover();
            });
        }

        // Fitur Pencarian Real-Time (Live Filter)
        if (searchInput) {
            const rows = document.querySelectorAll('.room-item-row');
            const noMatch = document.getElementById('no-rooms-match');
            const resultsInfo = document.getElementById('search-results-info');

            searchInput.addEventListener('input', () => {
                const q = searchInput.value.trim().toLowerCase();
                let visibleCount = 0;

                if (btnClearSearch) {
                    btnClearSearch.style.display = q ? 'inline-flex' : 'none';
                }

                rows.forEach(row => {
                    const name = row.getAttribute('data-name') || '';
                    const loc = row.getAttribute('data-loc') || '';
                    if (!q || name.includes(q) || loc.includes(q)) {
                        row.style.display = 'flex';
                        visibleCount++;
                    } else {
                        row.style.display = 'none';
                    }
                });

                if (noMatch) {
                    noMatch.style.display = visibleCount === 0 ? 'block' : 'none';
                }
                if (resultsInfo) {
                    resultsInfo.textContent = q
                        ? `Menampilkan ${visibleCount} dari ${rows.length} ruangan`
                        : `Total ${rows.length} ruangan`;
                }
            });

            btnClearSearch?.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
                searchInput.focus();
            });
        }

        // Pintasan Pilih Semua & Batal Pilih
        const btnSelectAll = document.getElementById('btn-select-all-rooms');
        const btnClear = document.getElementById('btn-clear-rooms');
        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', () => {
                // Centang semua ruangan yang sedang tampil (sesuai filter pencarian)
                document.querySelectorAll('.room-item-row').forEach(row => {
                    if (row.style.display !== 'none') {
                        const chk = row.querySelector('.room-chk-input');
                        if (chk) chk.checked = true;
                    }
                });
                updateSelectedChips();
                checkAvailability();
            });
        }
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                document.querySelectorAll('.room-chk-input').forEach(chk => chk.checked = false);
                updateSelectedChips();
                checkAvailability();
            });
        }

        document.querySelectorAll('.room-chk-input').forEach(chk => {
            chk.addEventListener('change', () => {
                updateSelectedChips();
                checkAvailability();
            });
        });

        function updateEndLessonOptions() {
            const isCustomStart = startLessonField.value === 'custom';
            const isMultiDay = endDateField && endDateField.value && endDateField.value !== dateField.value;

            Array.from(endLessonField.options).forEach(option => {
                if (!option.value) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                if (option.value === 'custom') {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const lessonNumber = parseInt(option.value, 10);
                const startLessonNumber = parseInt(startLessonField.value, 10);
                const isBeforeStart = !isCustomStart && !isNaN(startLessonNumber) && lessonNumber < startLessonNumber;

                option.hidden = !isMultiDay && isBeforeStart;
                option.disabled = !isMultiDay && isBeforeStart;
            });

            if (isCustomStart) {
                endLessonField.value = 'custom';
            } else if (!isMultiDay && endLessonField.value !== 'custom' && parseInt(endLessonField.value, 10) < parseInt(startLessonField.value, 10)) {
                endLessonField.value = startLessonField.value;
            }

            startCustomTimeContainer.hidden = !isCustomStart;
            endCustomTimeContainer.hidden = endLessonField.value !== 'custom';
            startCustomTimeField.required = isCustomStart;
            endCustomTimeField.required = endLessonField.value === 'custom';
        }

        async function checkAvailability() {
            const roomIds = getSelectedRoomIds();
            const date = dateField.value;
            const endDate = endDateField?.value || date;
            const startLesson = startLessonField.value;
            const endLesson = endLessonField.value;
            const startCustomTime = startCustomTimeField.value;
            const endCustomTime = endCustomTimeField.value;

            if (roomIds.length === 0 || !date || !endDate || !startLesson || !endLesson) {
                availabilityMessage.textContent = roomIds.length === 0
                    ? 'Pilih minimal satu ruangan untuk memeriksa ketersediaan.'
                    : 'Pilih ruangan, tanggal, dan jam untuk memeriksa ketersediaan.';
                availabilityMessage.className = 'muted';
                submitButton.disabled = false;
                return;
            }

            if (endDate < date) {
                availabilityMessage.textContent = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
                availabilityMessage.className = 'error';
                submitButton.disabled = true;
                return;
            }

            if ((startLesson === 'custom' && !startCustomTime) || (endLesson === 'custom' && !endCustomTime)) {
                availabilityMessage.textContent = 'Isi jam bebas untuk memeriksa ketersediaan.';
                availabilityMessage.className = 'muted';
                submitButton.disabled = false;
                return;
            }

            if (minimumBookingDate && date < minimumBookingDate) {
                availabilityMessage.textContent = 'Peminjaman ruangan hanya dapat diajukan paling lambat H-1 sebelum digunakan.';
                availabilityMessage.className = 'error';
                submitButton.disabled = true;
                return;
            }

            const params = new URLSearchParams({
                room_ids: roomIds.join(','),
                date,
                start_lesson: startLesson,
                end_lesson: endLesson,
                start_custom_time: startCustomTime,
                end_custom_time: endCustomTime,
                ...(endDate !== date ? { end_date: endDate } : {}),
                @if($event->exists)
                    ignore: '{{ $event->id }}',
                @endif
            });
            const response = await fetch('{{ route('events.availability') }}?' + params);
            if (!response.ok) {
                availabilityMessage.textContent = 'Ketersediaan jadwal tidak dapat diperiksa. Silakan coba lagi.';
                availabilityMessage.className = 'error';
                submitButton.disabled = true;
                return;
            }

            const data = await response.json();
            if (data.collision) {
                const collMsg = data.collision_rooms && data.collision_rooms.length
                    ? 'Jadwal bertabrakan pada ruangan: ' + data.collision_rooms.join(', ') + '. Pilih jam atau ruangan lain.'
                    : 'Jadwal ini sudah terpakai pada salah satu ruangan. Pilih ruangan atau jam lain.';
                availabilityMessage.textContent = collMsg;
                availabilityMessage.className = 'error';
                submitButton.disabled = true;
            } else {
                availabilityMessage.textContent = roomIds.length > 1
                    ? 'Semua ' + roomIds.length + ' ruangan yang dipilih tersedia!'
                    : (data.events.length
                        ? 'Tersedia. Slot terpesan: ' + data.events.map(item => {
                            const startTime = item.start_time.slice(0, 5);
                            const endTime = item.end_time.slice(0, 5);

                            return startTime + '–' + endTime + (endTime < startTime ? ' (+1 hari)' : '');
                        }).join(', ')
                        : 'Ruangan tersedia.');
                availabilityMessage.className = 'success';
                submitButton.disabled = false;
            }
        }

        startLessonField.addEventListener('change', () => {
            updateEndLessonOptions();
            checkAvailability();
        });
        endLessonField.addEventListener('change', () => {
            updateEndLessonOptions();
            checkAvailability();
        });
        startCustomTimeField.addEventListener('change', checkAvailability);
        endCustomTimeField.addEventListener('change', checkAvailability);
        if (roomField) {
            roomField.addEventListener('change', checkAvailability);
        }

        function datesChanged() {
            if (endDateField) {
                endDateField.min = dateField.value;
                if (endDateField.value < dateField.value) {
                    endDateField.value = dateField.value;
                }
            }
            updateEndLessonOptions();
            checkAvailability();
        }
        dateField.addEventListener('change', datesChanged);
        endDateField?.addEventListener('change', datesChanged);

        document.getElementById('event-form').addEventListener('submit', event => {
            const selected = getSelectedRoomIds();
            if (selected.length === 0) {
                alert('Pilih minimal satu ruangan yang akan dipinjam.');
                event.preventDefault();
                return;
            }
            const confirmMsg = selected.length > 1
                ? 'Yakin ingin mengajukan peminjaman untuk ' + selected.length + ' ruangan sekaligus?'
                : '{{ $event->exists ? 'Yakin ingin menyimpan perubahan agenda ini?' : 'Yakin ingin menyimpan pengajuan agenda ini?' }}';
            if (!confirm(confirmMsg)) {
                event.preventDefault();
            }
        });

        // Inisialisasi awal
        updateSelectedChips();
        updateEndLessonOptions();
        checkAvailability();
    </script>
@endsection
