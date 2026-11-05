@extends('layouts.app')

@section('content')
    @php($isAdmin = auth()->user()->role?->name === 'admin')
    <h1>{{ $complaint->exists ? 'Perbarui Status Pengaduan' : ($isAdmin ? 'Catat Barang Rusak' : 'Buat Pengaduan') }}</h1>
    <form class="card" method="post" enctype="multipart/form-data" action="{{ $complaint->exists ? route('complaints.update', $complaint) : route('complaints.store') }}">
        @csrf
        @if($complaint->exists)
            @method('put')
            <p><strong>Ruangan:</strong> {{ $complaint->room->name }}</p>
            <p><strong>PIC ruangan:</strong> {{ $complaint->room->pic?->name ?? 'Belum ditetapkan' }}</p>
            <p><strong>Fasilitas rusak:</strong> {{ $complaint->facility_name }}</p>
            <p><strong>Penjelasan pelapor:</strong><br>{{ $complaint->description }}</p>
            @if($complaint->photo_path)
                <p><a href="{{ route('complaints.photo', $complaint) }}" target="_blank">Lihat foto bukti</a></p>
            @endif
            <label for="status_repair">Status perbaikan</label>
            <select id="status_repair" name="status_repair" required>
                <option value="Belum Diperbaiki" @selected(old('status_repair', $complaint->status_repair) === 'Belum Diperbaiki')>Belum Diperbaiki</option>
                <option value="Menunggu Spare Part" @selected(old('status_repair', $complaint->status_repair) === 'Menunggu Spare Part')>Menunggu Spare Part</option>
                <option value="Sudah Diperbaiki" @selected(old('status_repair', $complaint->status_repair) === 'Sudah Diperbaiki')>Sudah Diperbaiki</option>
            </select>
            <label id="repair-notes-label" for="repair_notes">Catatan PIC (opsional)</label>
            <p id="repair-notes-help" class="muted" role="status" aria-live="polite"></p>
            <textarea id="repair_notes" name="repair_notes" placeholder="Jelaskan tindakan perbaikan yang dilakukan.">{{ old('repair_notes', $complaint->repair_notes) }}</textarea>
            @error('repair_notes')<p class="error">{{ $message }}</p>@enderror
        @else
            @if($isAdmin)
                @php($selectedEventId = old('event_id', $event?->id))
                <label for="event_id">Agenda terkait <small>(opsional)</small></label>
                <select id="event_id" name="event_id">
                    <option value="">Tanpa agenda</option>
                    @foreach($events as $availableEvent)
                        <option value="{{ $availableEvent->id }}"
                            data-room-id="{{ $availableEvent->room_id }}"
                            data-room-name="{{ $availableEvent->room->name }}"
                            data-pic-name="{{ $availableEvent->room->pic?->name ?? 'Belum ditetapkan' }}"
                            data-agenda-title="{{ $availableEvent->title }}"
                            data-agenda-date="{{ $availableEvent->date->format('d M Y') }}"
                            @selected($selectedEventId == $availableEvent->id)>
                            {{ $availableEvent->title }} · {{ $availableEvent->date->format('d M Y') }} · {{ $availableEvent->room->name }}
                        </option>
                    @endforeach
                </select>
                @if($events->isEmpty())
                    <p class="muted">Belum ada agenda. Ruangan tetap dapat dipilih di bawah.</p>
                @endif
                <label for="room_id">Ruangan</label>
                <select id="room_id" name="room_id" required>
                    <option value="">Pilih ruangan</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" data-pic-name="{{ $room->pic?->name ?? '' }}" @selected(old('room_id', $event?->room_id) == $room->id)>
                            {{ $room->name }} — PIC: {{ $room->pic?->name ?? 'Belum ditetapkan' }}
                        </option>
                    @endforeach
                </select>
                <p id="event-details" class="muted" role="status" aria-live="polite">
                    @if($event)
                        Agenda: {{ $event->title }} · {{ $event->date->format('d M Y') }} · Ruangan: {{ $event->room->name }} · PIC: {{ $event->room->pic?->name ?? 'Belum ditetapkan' }}
                    @else
                        Pilih ruangan; agenda dapat dipilih jika pengaduan berkaitan dengan pemakaian tertentu.
                    @endif
                </p>
            @elseif($event)
                <input type="hidden" name="event_id" value="{{ $event->id }}">
                <input id="room_id" type="hidden" name="room_id" value="{{ $event->room_id }}">
                <p><strong>Agenda:</strong> {{ $event->title }} · {{ $event->date->format('d M Y') }}</p>
                <p><strong>Ruangan:</strong> {{ $event->room->name }} · PIC: {{ $event->room->pic?->name ?? 'Belum ditetapkan' }}</p>
            @else
                <label for="room_id">Ruangan</label>
                <select id="room_id" name="room_id" required>
                    <option value="">Pilih ruangan</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}" data-pic-name="{{ $room->pic?->name ?? '' }}" @selected(old('room_id') == $room->id)>
                            {{ $room->name }} — PIC: {{ $room->pic?->name ?? 'Belum ditetapkan' }}
                        </option>
                    @endforeach
                </select>
                <p id="room-pic-info" class="muted" role="status" aria-live="polite">Pilih ruangan untuk melihat PIC yang menangani pengaduan.</p>
            @endif

            <label for="facility_name">Fasilitas rusak</label>
            <input id="facility_name" name="facility_name" value="{{ old('facility_name') }}" required>
            <label for="description">Penjelasan kerusakan</label>
            <textarea id="description" name="description" required>{{ old('description') }}</textarea>
            <label for="photo">Foto bukti (JPG/PNG, maks. 5MB) @if(auth()->user()->role?->name !== 'admin')<small>(wajib)</small>@else<small>(opsional)</small>@endif</label>
            <input id="photo" type="file" name="photo" accept="image/*" hidden>
            <input id="camera-photo" type="file" accept="image/*" capture="environment" hidden>
            <div class="actions">
                <button id="choose-photo" class="secondary" type="button">Pilih file</button>
                <button id="take-photo" class="secondary" type="button">Buka kamera</button>
            </div>
            <p class="muted">Pilih file membuka galeri atau berkas. Buka kamera menggunakan kamera perangkat.</p>
            <p id="photo-message" class="muted" role="status" aria-live="polite"></p>
            <video id="camera-preview" autoplay playsinline muted hidden style="width:100%;max-width:480px;border-radius:8px"></video>
            <div id="camera-actions" hidden>
                <button id="capture-photo" class="secondary" type="button">Ambil foto</button>
                <button id="close-camera" class="secondary" type="button">Tutup kamera</button>
            </div>
            <img id="photo-preview" alt="Pratinjau foto bukti" hidden style="width:100%;max-width:480px;border-radius:8px">
            <div id="photo-actions" hidden>
                <button id="remove-photo" class="danger" type="button">Hapus foto</button>
            </div>
            @error('photo')<p class="error">{{ $message }}</p>@enderror
        @endif
        <div class="actions">
            <button type="submit">Simpan</button>
            <a class="btn" href="{{ route('complaints.index') }}">Batal</a>
        </div>
    </form>

    @if(! $complaint->exists)
        <script>
            const roomSelect = document.getElementById('room_id');
            const roomPicInfo = document.getElementById('room-pic-info');
            const eventSelect = document.getElementById('event_id');
            const eventDetails = document.getElementById('event-details');
            const photoInput = document.getElementById('photo');
            const cameraPhotoInput = document.getElementById('camera-photo');
            const choosePhotoButton = document.getElementById('choose-photo');
            const takePhotoButton = document.getElementById('take-photo');
            const capturePhotoButton = document.getElementById('capture-photo');
            const closeCameraButton = document.getElementById('close-camera');
            const cameraActions = document.getElementById('camera-actions');
            const cameraPreview = document.getElementById('camera-preview');
            const complaintForm = photoInput.form;
            const photoPreview = document.getElementById('photo-preview');
            const photoActions = document.getElementById('photo-actions');
            const removePhotoButton = document.getElementById('remove-photo');
            const photoMessage = document.getElementById('photo-message');
            const photoRequired = {{ auth()->user()->role?->name !== 'admin' ? 'true' : 'false' }};
            photoInput.required = photoRequired;
            cameraPhotoInput.required = photoRequired;
            let photoPreviewUrl = null;
            let cameraStream = null;

            function updateRoomPicInfo() {
                if (!roomSelect || !roomPicInfo) {
                    return;
                }

                const selectedOption = roomSelect.selectedOptions[0];
                if (!selectedOption || !selectedOption.value) {
                    roomPicInfo.textContent = 'Pilih ruangan untuk melihat PIC yang menangani pengaduan.';
                    return;
                }

                roomPicInfo.textContent = selectedOption.dataset.picName
                    ? `Pengaduan ruangan ini akan ditangani oleh PIC: ${selectedOption.dataset.picName}.`
                    : 'Ruangan ini belum memiliki PIC. Silakan hubungi Admin.';
            }

            function showPhotoPreview(file) {
                if (photoPreviewUrl) {
                    URL.revokeObjectURL(photoPreviewUrl);
                }

                photoPreviewUrl = URL.createObjectURL(file);
                photoPreview.src = photoPreviewUrl;
                photoPreview.hidden = false;
            }

            function useSelectedPhoto(file) {
                if (file.size > 5 * 1024 * 1024) {
                    photoInput.value = '';
                    cameraPhotoInput.value = '';
                    photoPreview.hidden = true;
                    photoActions.hidden = true;
                    if (photoPreviewUrl) {
                        URL.revokeObjectURL(photoPreviewUrl);
                        photoPreviewUrl = null;
                    }
                    photoMessage.textContent = 'Ukuran foto melebihi batas maksimal 5MB.';
                    photoMessage.className = 'error';
                    return;
                }

                const transfer = new DataTransfer();
                transfer.items.add(file);
                photoInput.files = transfer.files;
                showPhotoPreview(file);
                photoActions.hidden = false;
                photoMessage.textContent = 'Foto siap dilampirkan.';
                photoMessage.className = 'success';
            }

            function removeSelectedPhoto() {
                photoInput.value = '';
                cameraPhotoInput.value = '';
                photoPreview.hidden = true;
                photoPreview.removeAttribute('src');
                photoActions.hidden = true;
                if (photoPreviewUrl) {
                    URL.revokeObjectURL(photoPreviewUrl);
                    photoPreviewUrl = null;
                }
                photoMessage.textContent = 'Foto dihapus. Pilih file atau buka kamera untuk menambahkan foto.';
                photoMessage.className = 'muted';
            }

            function stopCamera() {
                cameraStream?.getTracks().forEach(track => track.stop());
                cameraStream = null;
                cameraPreview.srcObject = null;
                cameraPreview.hidden = true;
                cameraActions.hidden = true;
                takePhotoButton.disabled = false;
            }

            function isMobileDevice() {
                return navigator.userAgentData?.mobile
                    || /Android|iPhone|iPad|iPod/i.test(navigator.userAgent);
            }

            async function openCamera() {
                if (isMobileDevice()) {
                    cameraPhotoInput.click();
                    return;
                }

                if (!navigator.mediaDevices?.getUserMedia) {
                    photoMessage.textContent = 'Kamera browser tidak tersedia. Buka halaman melalui HTTPS dan pastikan perangkat memiliki kamera.';
                    photoMessage.className = 'error';
                    return;
                }

                takePhotoButton.disabled = true;
                photoMessage.textContent = 'Meminta izin kamera...';
                photoMessage.className = 'muted';

                try {
                    cameraStream = await navigator.mediaDevices.getUserMedia({
                        audio: false,
                        video: true
                    });
                    cameraPreview.srcObject = cameraStream;
                    await cameraPreview.play();
                    cameraPreview.hidden = false;
                    cameraActions.hidden = false;
                    takePhotoButton.disabled = false;
                    photoMessage.textContent = 'Kamera aktif. Ambil foto saat siap.';
                    photoMessage.className = 'muted';
                } catch (error) {
                    stopCamera();
                    photoMessage.textContent = error.name === 'NotAllowedError'
                        ? 'Izin kamera ditolak. Izinkan akses kamera di browser lalu coba lagi.'
                        : error.name === 'NotFoundError'
                            ? 'Kamera tidak ditemukan. Pastikan kamera terhubung.'
                            : 'Kamera tidak dapat dibuka. Periksa izin kamera dan coba lagi.';
                    photoMessage.className = 'error';
                } finally {
                    takePhotoButton.disabled = false;
                }
            }

            function capturePhoto() {
                if (!cameraPreview.videoWidth || !cameraPreview.videoHeight) {
                    photoMessage.textContent = 'Kamera belum siap. Tunggu sebentar lalu coba lagi.';
                    photoMessage.className = 'error';
                    return;
                }

                const scale = Math.min(1, 1600 / Math.max(cameraPreview.videoWidth, cameraPreview.videoHeight));
                const canvas = document.createElement('canvas');
                canvas.width = Math.round(cameraPreview.videoWidth * scale);
                canvas.height = Math.round(cameraPreview.videoHeight * scale);
                canvas.getContext('2d').drawImage(cameraPreview, 0, 0, canvas.width, canvas.height);
                canvas.toBlob(blob => {
                    if (!blob) {
                        photoMessage.textContent = 'Foto gagal diproses. Silakan coba lagi.';
                        photoMessage.className = 'error';
                        return;
                    }

                    useSelectedPhoto(new File([blob], 'foto-pengaduan.jpg', { type: 'image/jpeg' }));
                    stopCamera();
                }, 'image/jpeg', 0.85);
            }

            if (roomSelect) {
                if (roomSelect.tagName === 'SELECT') {
                    roomSelect.addEventListener('change', updateRoomPicInfo);
                    updateRoomPicInfo();
                }
            }

            function updateSelectedEvent() {
                if (!eventSelect || !eventDetails) {
                    return;
                }

                const selectedOption = eventSelect.selectedOptions[0];
                if (!selectedOption || !selectedOption.value) {
                    roomSelect.disabled = false;
                    eventDetails.textContent = 'Pilih ruangan; agenda dapat dipilih jika pengaduan berkaitan dengan pemakaian tertentu.';
                    return;
                }

                roomSelect.value = selectedOption.dataset.roomId;
                roomSelect.disabled = true;
                eventDetails.textContent = `Agenda: ${selectedOption.dataset.agendaTitle} · ${selectedOption.dataset.agendaDate} · Ruangan: ${selectedOption.dataset.roomName} · PIC: ${selectedOption.dataset.picName}`;
            }

            if (eventSelect) {
                eventSelect.addEventListener('change', updateSelectedEvent);
                updateSelectedEvent();
            }

            choosePhotoButton.addEventListener('click', () => photoInput.click());
            takePhotoButton.addEventListener('click', openCamera);
            capturePhotoButton.addEventListener('click', capturePhoto);
            closeCameraButton.addEventListener('click', stopCamera);
            removePhotoButton.addEventListener('click', removeSelectedPhoto);
            photoInput.addEventListener('change', () => {
                if (photoInput.files.length) {
                    stopCamera();
                    useSelectedPhoto(photoInput.files[0]);
                }
            });
            cameraPhotoInput.addEventListener('change', () => {
                if (cameraPhotoInput.files.length) {
                    stopCamera();
                    useSelectedPhoto(cameraPhotoInput.files[0]);
                }
            });
            window.addEventListener('pagehide', stopCamera);
            complaintForm.addEventListener('submit', event => {
                if (photoRequired && !photoInput.files.length) {
                    event.preventDefault();
                    photoMessage.textContent = 'Pilih foto sebelum mengirim pengaduan.';
                    photoMessage.className = 'error';
                    choosePhotoButton.focus();
                }
            });
        </script>
    @endif
    @if($complaint->exists)
        <script>
            const repairStatus = document.getElementById('status_repair');
            const repairNotes = document.getElementById('repair_notes');
            const repairNotesLabel = document.getElementById('repair-notes-label');
            const repairNotesHelp = document.getElementById('repair-notes-help');

            function updateRepairNotesRequirement() {
                const isRepaired = repairStatus.value === 'Sudah Diperbaiki';
                repairNotes.required = isRepaired;
                repairNotesLabel.textContent = isRepaired
                    ? 'Keterangan Perbaikan (wajib)'
                    : 'Catatan PIC (opsional)';
                repairNotesHelp.textContent = isRepaired
                    ? 'Jelaskan perbaikan yang telah dilakukan pada barang/fasilitas ini.'
                    : 'Tambahkan catatan jika diperlukan.';
            }

            repairStatus.addEventListener('change', updateRepairNotesRequirement);
            updateRepairNotesRequirement();
        </script>
    @endif
@endsection
