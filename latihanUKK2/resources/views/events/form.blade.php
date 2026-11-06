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
                <div class="form-field form-wide multi-room-wrapper">
                    <div class="multi-room-top">
                        <div>
                            <label class="multi-room-title">Pilih Ruangan <span class="multi-room-badge-pill">Bisa pilih lebih dari 1 ruangan</span></label>
                            <p class="muted" style="margin: 4px 0 0; font-size: 13px;">Centang satu atau beberapa ruangan sekaligus untuk kegiatan ini.</p>
                        </div>
                        <div class="multi-room-actions">
                            <span id="selected-room-count" class="selected-count-badge">0 ruangan dipilih</span>
                            <button type="button" class="btn-select-shortcut" id="btn-select-all-rooms">Pilih Semua</button>
                            <button type="button" class="btn-select-shortcut" id="btn-clear-rooms">Batal Pilih</button>
                        </div>
                    </div>
                    <div class="rooms-selection-grid">
                        @foreach($rooms as $room)
                            <label class="room-pick-card" for="room-chk-{{ $room->id }}">
                                <input type="checkbox" id="room-chk-{{ $room->id }}" name="room_ids[]" value="{{ $room->id }}" class="room-chk-input" @checked(in_array($room->id, (array) old('room_ids', [])))>
                                <span class="room-pick-info">
                                    <span class="room-pick-name">{{ $room->name }}</span>
                                    <span class="room-pick-meta">{{ $room->location ?: 'SMKN 1 Bantul' }} · Kapasitas {{ $room->capacity ?? '-' }}</span>
                                </span>
                            </label>
                        @endforeach
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
        .multi-room-wrapper {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
        }
        .multi-room-top {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 14px;
        }
        .multi-room-title {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            display: flex;
            align-items: center;
            gap: 8px;
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
        .multi-room-actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
        }
        .selected-count-badge {
            background: #0b192e;
            color: #fff;
            font-size: 11.5px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .btn-select-shortcut {
            background: #fff;
            border: 1px solid #cbd5e1;
            color: #475569;
            font-size: 12px;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-select-shortcut:hover {
            background: #f1f5f9;
            color: #0f172a;
        }
        .rooms-selection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: 10px;
            max-height: 280px;
            overflow-y: auto;
            padding: 2px;
        }
        .room-pick-card {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            padding: 10px 12px;
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 0.15s, background-color 0.15s, box-shadow 0.15s;
            user-select: none;
        }
        .room-pick-card:hover {
            border-color: #cbd5e1;
            background: #fcfdfe;
        }
        .room-pick-card:has(.room-chk-input:checked) {
            border-color: #f59e0b;
            background: #fffdf5;
            box-shadow: 0 1px 3px rgba(245, 158, 11, 0.12);
        }
        .room-chk-input {
            margin-top: 3px;
            accent-color: #f59e0b;
            width: 17px;
            height: 17px;
            cursor: pointer;
        }
        .room-pick-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .room-pick-name {
            font-size: 13.5px;
            font-weight: 700;
            color: #0f172a;
        }
        .room-pick-meta {
            font-size: 11.5px;
            color: #64748b;
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
        const minimumBookingDate = @json($event->exists ? null : $minimumBookingDate);

        function getSelectedRoomIds() {
            @if($event->exists)
                return roomField && roomField.value ? [roomField.value] : [];
            @else
                const checked = document.querySelectorAll('.room-chk-input:checked');
                return Array.from(checked).map(chk => chk.value);
            @endif
        }

        function updateEndLessonOptions() {
            const isCustomStart = startLessonField.value === 'custom';
            const isMultiDay = endDateField && endDateField.value > dateField.value;
            [...endLessonField.options].forEach(option => {
                option.disabled = isCustomStart
                    ? option.value !== '' && option.value !== 'custom'
                    : !isMultiDay && option.value !== '' && option.value !== 'custom' && Number(option.value) < Number(startLessonField.value);
            });

            if (isCustomStart && endLessonField.value !== 'custom') {
                endLessonField.value = 'custom';
            } else if (!isMultiDay && !isCustomStart && Number(endLessonField.value) < Number(startLessonField.value)) {
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

            const countBadge = document.getElementById('selected-room-count');
            if (countBadge) {
                countBadge.textContent = roomIds.length + ' ruangan dipilih';
            }

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

        const btnSelectAll = document.getElementById('btn-select-all-rooms');
        const btnClear = document.getElementById('btn-clear-rooms');
        if (btnSelectAll) {
            btnSelectAll.addEventListener('click', () => {
                document.querySelectorAll('.room-chk-input').forEach(chk => chk.checked = true);
                checkAvailability();
            });
        }
        if (btnClear) {
            btnClear.addEventListener('click', () => {
                document.querySelectorAll('.room-chk-input').forEach(chk => chk.checked = false);
                checkAvailability();
            });
        }
        document.querySelectorAll('.room-chk-input').forEach(chk => {
            chk.addEventListener('change', checkAvailability);
        });

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
        updateEndLessonOptions();
        checkAvailability();
    </script>
@endsection
