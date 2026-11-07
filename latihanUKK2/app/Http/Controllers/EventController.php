<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Room;
use App\Models\SystemNotification;
use App\Support\LessonSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $this->canViewIndex();
        $filters = ['all', 'upcoming', 'current', 'today', 'pending', 'rejected'];
        $data = $request->validate([
            'filter' => ['nullable', 'in:'.implode(',', $filters)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $filter = $data['filter'] ?? 'all';
        $search = trim($data['search'] ?? '');
        $query = Event::with(['user', 'room'])->orderByDesc('created_at')->orderByDesc('id');
        if (in_array(auth()->user()->role?->name, ['guru', 'organisasi'])) {
            $query->where('user_id', auth()->id())
                ->whereNull('deleted_by_creator_at');
        }
        $allEvents = $query->get();
        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->startOfDay();
        $threeDaysFromNow = $today->addDays(3)->endOfDay();
        $filterEvents = static function ($events, string $selected) use ($now, $today, $threeDaysFromNow) {
            return $events->filter(function (Event $event) use ($selected, $now, $today, $threeDaysFromNow) {
                return match ($selected) {
                    'upcoming' => $event->status === 'approved'
                        && $event->startAt()->betweenIncluded($now, $threeDaysFromNow),
                    'current' => $event->status === 'approved',
                    'today' => $event->status === 'approved'
                        && $event->startAt()->lt($today->addDay())
                        && $event->endAt()->gte($today),
                    'pending' => $event->status === 'pending',
                    'rejected' => $event->status === 'rejected',
                    default => true,
                };
            })->values();
        };
        $events = $filterEvents($allEvents, $filter);
        if ($search !== '') {
            $needle = mb_strtolower($search);
            $events = $events->filter(function (Event $event) use ($needle) {
                return str_contains(mb_strtolower(implode(' ', [
                    $event->title,
                    $event->room->name,
                    $event->description,
                    $event->borrower_name,
                    $event->user->name,
                ])), $needle);
            })->values();
        }

        return view('events.index', [
            'events' => $events,
            'filter' => $filter,
            'search' => $search,
            'eventCounts' => collect($filters)->mapWithKeys(fn (string $key) => [
                $key => $filterEvents($allEvents, $key)->count(),
            ]),
        ]);
    }

    public function create()
    {
        $this->canCreate();
        $isAdmin = auth()->user()->role?->name === 'admin';

        return view('events.form', [
            'event' => new Event,
            'isAdmin' => $isAdmin,
            'rooms' => Room::orderBy('name')->get(),
            'lessonPeriods' => LessonSchedule::periods(),
            'selectedStartLesson' => null,
            'selectedEndLesson' => null,
            'minimumBookingDate' => now('Asia/Jakarta')->addDay()->toDateString(),
        ]);
    }

    public function store(Request $request)
    {
        $this->canCreate();
        $isAdmin = auth()->user()->role?->name === 'admin';
        $data = $this->validated($request, $isAdmin);
        $roomIds = $data['room_ids'] ?? [$data['room_id']];

        // Periksa bentrok jadwal untuk setiap ruangan yang dipilih
        $collidingRooms = [];
        $validRooms = Room::whereIn('id', $roomIds)->get()->keyBy('id');
        foreach ($roomIds as $rId) {
            $testData = $data;
            $testData['room_id'] = $rId;
            try {
                $this->ensureNoCollision($testData);
            } catch (ValidationException $e) {
                $collidingRooms[] = $validRooms[$rId]->name ?? "Ruangan #$rId";
            }
        }

        if (!empty($collidingRooms)) {
            throw ValidationException::withMessages([
                'room_ids' => 'Jadwal bertabrakan dengan agenda lain pada ruangan: ' . implode(', ', $collidingRooms) . '.',
            ]);
        }

        $bookingGroupId = count($roomIds) > 1 ? (string) \Illuminate\Support\Str::uuid() : null;
        $hasGroupCol = \Illuminate\Support\Facades\Schema::hasColumn('events', 'booking_group_id');
        $createdEvents = [];

        foreach ($roomIds as $rId) {
            $eventData = $data;
            unset($eventData['room_ids']);
            $eventData['room_id'] = $rId;
            $eventData['user_id'] = auth()->id();
            $eventData['status'] = $isAdmin ? 'approved' : 'pending';
            if ($bookingGroupId && $hasGroupCol) {
                $eventData['booking_group_id'] = $bookingGroupId;
            }
            $createdEvents[] = Event::create($eventData);
        }

        $msg = count($createdEvents) > 1
            ? ($isAdmin
                ? count($createdEvents) . ' ruangan berhasil dibooking dan langsung disetujui.'
                : count($createdEvents) . ' ruangan berhasil diajukan dan menunggu persetujuan.')
            : ($isAdmin
                ? 'Agenda berhasil dibuat dan langsung disetujui.'
                : 'Agenda diajukan dan menunggu persetujuan.');

        $firstEvent = $createdEvents[0] ?? null;
        if ($firstEvent) {
            $firstEvent->load('room');
            ActivityLog::record(
                'Pengajuan Peminjaman',
                auth()->user()->name . " mengajukan peminjaman ruangan {$firstEvent->room->name} untuk agenda '{$firstEvent->title}'."
            );
        }

        return redirect()->route('events.index')->with('success', $msg);
    }

    public function edit(Event $event)
    {
        $this->admin();
        abort_unless($event->status !== 'rejected', 403, 'Agenda yang ditolak tidak dapat diedit.');

        return view('events.form', [
            'event' => $event,
            'isAdmin' => true,
            'rooms' => Room::orderBy('name')->get(),
            'lessonPeriods' => LessonSchedule::periods(),
            'selectedStartLesson' => LessonSchedule::lessonStartingAt(substr($event->start_time, 0, 5)),
            'selectedEndLesson' => LessonSchedule::lessonEndingAt(substr($event->end_time, 0, 5)),
            'minimumBookingDate' => now('Asia/Jakarta')->addDay()->toDateString(),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        $this->admin();
        abort_unless($event->status !== 'rejected', 403, 'Agenda yang ditolak tidak dapat diedit.');
        $data = $this->validated($request, true);
        $this->ensureNoCollision($data, $event->id);
        $oldRoomId = $event->room_id;
        if ($data['room_id'] != $oldRoomId && empty($data['move_reason'])) {
            return back()->withErrors(['move_reason' => 'Alasan pemindahan ruangan wajib diisi.'])->withInput();
        } $event->update($data);
        if ($data['room_id'] != $oldRoomId) {
            $event->load('room');
            SystemNotification::create(['user_id' => $event->user_id, 'event_id' => $event->id, 'title' => 'Ruangan agenda dipindahkan', 'message' => "Agenda {$event->title} dipindahkan ke {$event->room->name}. Alasan: {$data['move_reason']}"]);
        }

return redirect()->route('events.index')->with('success', 'Agenda berhasil diperbarui.'.($data['room_id'] != $oldRoomId ? ' Notifikasi telah dikirim ke peminjam.' : ''));
    }

    public function updateStatus(Request $request, Event $event): RedirectResponse
    {
        $this->admin();
        abort_unless($event->status === 'pending', 403, 'Hanya agenda pending yang dapat diputuskan.');

        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'required_if:status,rejected|nullable|string|max:2000',
        ], [
            'rejection_reason.required_if' => 'Keterangan wajib diisi saat menolak peminjaman ruangan.',
        ]);

        $event->update([
            'status' => $data['status'],
            'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null,
        ]);

        $event->load('room');
        SystemNotification::create([
            'user_id' => $event->user_id,
            'event_id' => $event->id,
            'title' => $data['status'] === 'approved' ? 'Agenda Disetujui' : 'Agenda Ditolak',
            'message' => $data['status'] === 'approved'
                ? "Pengajuan peminjaman ruangan '{$event->room->name}' untuk agenda '{$event->title}' telah disetujui."
                : "Pengajuan peminjaman ruangan '{$event->room->name}' ditolak. Alasan: {$event->rejection_reason}",
        ]);

        ActivityLog::record(
            $data['status'] === 'approved' ? 'Persetujuan Agenda' : 'Penolakan Agenda',
            auth()->user()->name . ($data['status'] === 'approved' ? ' menyetujui ' : ' menolak ') . "agenda '{$event->title}' di {$event->room->name}."
        );

        return redirect()->route('events.index')->with(
            'success',
            $data['status'] === 'approved' ? 'Agenda berhasil disetujui.' : 'Agenda berhasil ditolak.'
        );
    }

    public function receipt(Event $event)
    {
        abort_unless(
            in_array(auth()->user()->role?->name, ['admin', 'pic'], true) || auth()->id() === $event->user_id,
            403
        );
        abort_unless($event->status === 'approved', 404, 'Bukti peminjaman hanya tersedia untuk agenda yang telah disetujui.');

        return view('events.receipt', [
            'event' => $event->load(['room.pic', 'user']),
        ]);
    }

    public function checkout(Request $request, Event $event): RedirectResponse
    {
        abort_unless(
            auth()->user()->role?->name === 'admin'
            || auth()->user()->id === $event->user_id
            || (auth()->user()->role?->name === 'pic' && $event->room->pic_id === auth()->id()),
            403
        );
        abort_unless($event->status === 'approved', 422, 'Hanya agenda yang disetujui yang dapat dikonfirmasi pengembaliannya.');
        abort_unless(
            $event->hasStarted(),
            422,
            'Pengembalian ruangan hanya dapat dilakukan setelah jadwal agenda dimulai atau selesai.'
        );

        $data = $request->validate([
            'checkout_condition' => 'required|in:aman,rusak',
            'checkout_notes' => 'nullable|string|max:1000',
        ]);

        $event->update([
            'returned_at' => now('Asia/Jakarta'),
            'checkout_condition' => $data['checkout_condition'],
            'checkout_notes' => $data['checkout_notes'],
        ]);

        $event->load('room');
        ActivityLog::record(
            'Pengembalian Ruangan',
            auth()->user()->name . " mengonfirmasi pengembalian ruangan {$event->room->name} untuk agenda '{$event->title}' (Kondisi: " . ($data['checkout_condition'] === 'rusak' ? 'Ada Kerusakan Dilaporkan' : 'Aman & Bersih') . ")."
        );

        if ($data['checkout_condition'] === 'rusak') {
            return redirect()->route('complaints.create', ['event_id' => $event->id])->with('success', 'Pengembalian dicatat. Silakan laporkan fasilitas/barang yang rusak pada formulir berikut.');
        }

        return redirect()->back()->with('success', 'Ruangan berhasil dikonfirmasi telah selesai digunakan & dikembalikan.');
    }

    public function destroy(Event $event)
    {
        abort_unless(
            $event->canBeDeletedBy(auth()->user()),
            403,
            'Agenda hanya dapat dihapus setelah ditolak atau tiga hari setelah pelaksanaan.'
        );

        $isAdmin = auth()->user()->role?->name === 'admin';
        $event->load('room');
        $title = $event->title;
        $roomName = $event->room?->name ?? 'Ruangan';

        if (! $isAdmin && $event->status === 'approved') {
            // Pembuat agenda (guru/organisasi) menghapus riwayat agendanya yang sudah terlaksana:
            // Sembunyikan dari daftar pembuat, namun tetap disimpan untuk kalender dan rekam jejak Admin
            $event->update(['deleted_by_creator_at' => now('Asia/Jakarta')]);
            ActivityLog::record(
                'Hapus Riwayat Agenda',
                auth()->user()->name . " menghapus agenda terlaksana '{$title}' di {$roomName} dari riwayat pribadinya."
            );
        } else {
            // Admin menghapus, atau agenda berstatus ditolak
            $event->delete();
            ActivityLog::record(
                'Hapus Agenda',
                auth()->user()->name . " menghapus data agenda '{$title}' di {$roomName}."
            );
        }

        return redirect()->route('events.index')->with('success', 'Agenda berhasil dihapus.');
    }

    public function whatsappForm(Event $event)
    {
        $this->admin();
        abort_unless(in_array($event->status, ['pending', 'approved']), 404);
        $event->loadMissing(['user', 'room']);

        return view('events.whatsapp', ['event' => $event]);
    }

    public function sendWhatsApp(Request $request, Event $event): RedirectResponse
    {
        $this->admin();
        abort_unless(in_array($event->status, ['pending', 'approved']), 404);
        $event->loadMissing(['user', 'room']);

        $data = $request->validate(['message' => ['required', 'string', 'max:5000']]);
        $phone = preg_replace('/\D/', '', (string) $event->borrower_whatsapp);
        if ($phone === '') {
            return back()->withErrors(['whatsapp' => 'Nomor WhatsApp peminjam belum tersedia.']);
        }

        return redirect()->away('https://wa.me/'.$phone.'?text='.rawurlencode($data['message']));
    }

    private function admin(): void
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
    }

    private function canCreate(): void
    {
        abort_unless(in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']), 403);
    }

    private function canViewIndex(): void
    {
        abort_unless(in_array(auth()->user()->role?->name, ['admin', 'guru', 'organisasi']), 403);
    }

    public function availability(Request $request)
    {
        $isAdmin = $request->user()->role?->name === 'admin';
        $rules = [
            'room_id' => 'nullable|exists:rooms,id',
            'room_ids' => 'nullable|string',
            'date' => 'required|date_format:Y-m-d',
            'start_lesson' => 'required|in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14',
            'end_lesson' => 'required|in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14',
            'start_custom_time' => 'required_if:start_lesson,custom|nullable|date_format:H:i',
            'end_custom_time' => 'required_if:end_lesson,custom|nullable|date_format:H:i',
            'ignore' => 'nullable|integer',
        ];
        $rules['end_date'] = 'nullable|date_format:Y-m-d|after_or_equal:date';
        $data = $request->validate($rules);
        $endDate = $data['end_date'] ?? $data['date'];
        $interval = $this->resolveInterval($data, $endDate !== $data['date']);

        $rawRoomIds = $request->input('room_ids');
        $roomIds = [];
        if ($rawRoomIds) {
            $roomIds = array_filter(array_map('intval', explode(',', (string) $rawRoomIds)));
        }
        if (empty($roomIds) && !empty($data['room_id'])) {
            $roomIds = [(int) $data['room_id']];
        }

        $allEvents = [];
        $collisionRooms = [];
        $validRooms = Room::whereIn('id', $roomIds)->get()->keyBy('id');

        foreach ($roomIds as $rId) {
            $events = $this->eventsOverlappingRange(
                $rId,
                $data['date'],
                $endDate,
                $data['ignore'] ?? null
            );
            if ($this->hasCollision($events, $data['date'], $endDate, $interval)) {
                $collisionRooms[] = $validRooms[$rId]->name ?? "Ruangan #$rId";
            }
            foreach ($events as $ev) {
                $allEvents[] = $ev;
            }
        }

        return response()->json([
            'events' => $allEvents,
            'collision' => !empty($collisionRooms),
            'collision_rooms' => $collisionRooms,
        ]);
    }

    private function validated(Request $request, bool $admin = false): array
    {
        $rawRoomIds = $request->input('room_ids');
        if (is_string($rawRoomIds)) {
            $decoded = json_decode($rawRoomIds, true);
            $rawRoomIds = is_array($decoded) ? $decoded : explode(',', $rawRoomIds);
        }
        $roomIds = collect($rawRoomIds ?? [])
            ->map(fn($id) => (int) trim((string) $id))
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if (empty($roomIds) && $request->filled('room_id')) {
            $roomIds = [(int) $request->input('room_id')];
        }

        $request->merge(['room_ids' => $roomIds]);

        $rules = [
            'room_ids' => 'required|array|min:1',
            'room_ids.*' => 'exists:rooms,id',
            'room_id' => 'nullable|exists:rooms,id',
            'title' => 'required|string|max:255',
            'borrower_name' => 'required|string|max:255',
            'borrower_whatsapp' => ['required', 'string', 'regex:/^\\+?[0-9]{8,20}$/'],
            'description' => 'nullable|string',
            'date' => ['required', 'date_format:Y-m-d'],
            'start_lesson' => 'required|in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14',
            'end_lesson' => 'required|in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14',
            'start_custom_time' => 'required_if:start_lesson,custom|nullable|date_format:H:i',
            'end_custom_time' => 'required_if:end_lesson,custom|nullable|date_format:H:i',
        ];
        $rules['end_date'] = ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'];
        if (! $admin) {
            $minimumBookingDate = now('Asia/Jakarta')->addDay()->toDateString();
            $rules['date'][] = 'after_or_equal:'.$minimumBookingDate;
        }
        if ($admin) {
            $rules['move_reason'] = 'nullable|string';
        }

        $data = $request->validate($rules, [
            'room_ids.required' => 'Pilih minimal satu ruangan yang akan dipinjam.',
            'room_ids.min' => 'Pilih minimal satu ruangan yang akan dipinjam.',
            'date.after_or_equal' => 'Peminjaman ruangan hanya dapat diajukan paling lambat H-1 sebelum digunakan.',
            'start_custom_time.required_if' => 'Jam mulai bebas wajib diisi.',
            'end_custom_time.required_if' => 'Jam selesai bebas wajib diisi.',
        ]);

        $endDate = $data['end_date'] ?? $data['date'];
        $interval = $this->resolveInterval($data, $endDate !== $data['date']);
        $data['start_time'] = $interval['start_time'];
        $data['end_time'] = $interval['end_time'];
        $data['end_date'] = $endDate !== $data['date'] ? $endDate : null;
        $data['room_ids'] = $roomIds;
        $data['room_id'] = $roomIds[0] ?? null;
        unset($data['start_lesson'], $data['end_lesson'], $data['start_custom_time'], $data['end_custom_time']);

        return $data;
    }

    private function ensureNoCollision(array $data, ?int $ignore = null): void
    {
        $endDate = $data['end_date'] ?? $data['date'];
        $events = $this->eventsOverlappingRange($data['room_id'], $data['date'], $endDate, $ignore);
        $interval = [
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
        ];
        if ($this->hasCollision($events, $data['date'], $endDate, $interval)) {
            throw ValidationException::withMessages(['start_time' => 'Jadwal bertabrakan dengan agenda lain di ruangan yang sama.']);
        }
    }

    private function resolveInterval(array $data, bool $multiDay = false): array
    {
        $startTime = $data['start_lesson'] === 'custom'
            ? $data['start_custom_time']
            : LessonSchedule::periods()[(int) $data['start_lesson']]['start'];
        $endTime = $data['end_lesson'] === 'custom'
            ? $data['end_custom_time']
            : LessonSchedule::periods()[(int) $data['end_lesson']]['end'];

        if (! $multiDay && $data['start_lesson'] !== 'custom' && $data['end_lesson'] !== 'custom'
            && (int) $data['end_lesson'] < (int) $data['start_lesson']) {
            throw ValidationException::withMessages(['end_lesson' => 'Jam pelajaran selesai tidak boleh sebelum jam mulai.']);
        }

        foreach (['start_time' => $startTime, 'end_time' => $endTime] as $field => $time) {
            $lessonField = $field === 'start_time' ? 'start_lesson' : 'end_lesson';
            $isAllowed = $field === 'start_time'
                ? $this->isAllowedCustomStartTime($time)
                : $this->isAllowedCustomEndTime($time);
            if ($data[$lessonField] === 'custom' && ! $isAllowed) {
                throw ValidationException::withMessages([
                    $field === 'start_time' ? 'start_custom_time' : 'end_custom_time'
                        => $field === 'start_time'
                            ? 'Jam mulai bebas harus antara pukul 17.45 dan 23.59.'
                            : 'Jam selesai bebas harus antara pukul 17.45 dan 23.59, atau pukul 00.00 sampai 06.00 hari berikutnya.',
                ]);
            }
        }

        $interval = $this->eventIntervalMinutes($startTime, $endTime);
        if (! $multiDay && ($interval['end'] <= $interval['start'] || $interval['end'] > 30 * 60)) {
            throw ValidationException::withMessages([
                'end_lesson' => 'Jam selesai harus setelah jam mulai dan paling lambat pukul 06.00 hari berikutnya.',
            ]);
        }

        return ['start_time' => $startTime, 'end_time' => $endTime] + $interval;
    }

    private function isAllowedCustomStartTime(string $time): bool
    {
        return $this->minutesAfterMidnight($time) >= 17 * 60 + 45;
    }

    private function isAllowedCustomEndTime(string $time): bool
    {
        $minute = $this->minutesAfterMidnight($time);

        return $minute >= 17 * 60 + 45 || $minute <= 6 * 60;
    }

    private function eventIntervalMinutes(string $startTime, string $endTime): array
    {
        $start = $this->minutesAfterMidnight($startTime);
        $end = $this->minutesAfterMidnight($endTime);
        if ($start < LessonSchedule::FIRST_START_MINUTE) {
            $start += 24 * 60;
        }
        if ($end < LessonSchedule::FIRST_START_MINUTE) {
            $end += 24 * 60;
        }

        return ['start' => $start, 'end' => $end];
    }

    private function minutesAfterMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hours * 60 + $minutes;
    }

    private function eventsOverlappingRange(int|string $roomId, string $startDate, string $endDate, ?int $ignore = null)
    {
        $previousDate = CarbonImmutable::parse($startDate)->subDay()->toDateString();

        return Event::where('room_id', $roomId)
            ->whereDate('date', '<=', $endDate)
            ->where(function ($query) use ($startDate, $previousDate) {
                $query->whereDate('date', '>=', $previousDate)
                    ->orWhereDate('end_date', '>=', $startDate);
            })
            ->whereIn('status', ['pending', 'approved'])
            ->when($ignore, fn ($query, $id) => $query->whereKeyNot($id))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get(['date', 'end_date', 'start_time', 'end_time', 'title']);
    }

    private function hasCollision($events, string $startDate, string $endDate, array $interval): bool
    {
        $bookingStart = CarbonImmutable::parse($startDate.' '.$interval['start_time'], 'Asia/Jakarta')->getTimestamp() / 60;
        $bookingEndDate = CarbonImmutable::parse($endDate, 'Asia/Jakarta');
        if ($bookingEndDate->toDateString() === $startDate
            && substr($interval['end_time'], 0, 5) <= substr($interval['start_time'], 0, 5)) {
            $bookingEndDate = $bookingEndDate->addDay();
        }
        $bookingEnd = CarbonImmutable::parse(
            $bookingEndDate->toDateString().' '.$interval['end_time'],
            'Asia/Jakarta'
        )->getTimestamp() / 60;

        return $events->contains(function ($event) use ($bookingStart, $bookingEnd) {
            $eventStart = CarbonImmutable::parse(
                $event->date->toDateString().' '.$event->start_time,
                'Asia/Jakarta'
            )->getTimestamp() / 60;
            $eventEnd = $event->endAt()->getTimestamp() / 60;

            return $eventStart < $bookingEnd && $eventEnd > $bookingStart;
        });
    }
}
