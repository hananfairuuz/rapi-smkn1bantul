<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\ActivityLog;
use App\Models\ApiToken;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\ComplaintPhotoStorage;
use App\Services\IndonesiaHolidayCalendar;
use App\Support\LessonSchedule;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class MobileApiController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);
        $user = User::with('role')->whereRaw('LOWER(email) = ?', [strtolower(trim($credentials['email']))])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak cocok.']);
        }
        if (! $user->role) {
            throw ValidationException::withMessages(['email' => 'Akun ini belum memiliki peran. Hubungi Admin.']);
        }
        if ($user->role->name === 'admin') {
            throw ValidationException::withMessages(['email' => 'Akun Admin hanya dapat masuk melalui versi desktop.']);
        }

        $plainTextToken = Str::random(80);
        ApiToken::create([
            'user_id' => $user->id,
            'name' => 'rapi-mobile',
            'token' => hash('sha256', $plainTextToken),
            'expires_at' => now()->addDays(30),
        ]);

        return response()->json([
            'token' => $plainTextToken,
            'user' => $this->userData($user),
        ]);
    }

    public function logout(Request $request)
    {
        ApiToken::where('token', hash('sha256', $request->bearerToken()))->delete();

        return response()->json(['message' => 'Berhasil keluar.']);
    }

    public function user(Request $request)
    {
        if ($request->user()->role?->name === 'admin') {
            abort(403, 'Akun Admin hanya dapat masuk melalui versi desktop.');
        }

        return response()->json(['user' => $this->userData($request->user()->loadMissing('role'))]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $request->merge([
            'name' => trim((string) $request->input('name', '')),
            'email' => strtolower(trim((string) $request->input('email', ''))),
        ]);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'whatsapp' => ['required', 'string', 'regex:/^\+?[0-9]{8,20}$/'],
        ]);
        if ($user->email !== $data['email']) {
            $user->google_id = null;
        }
        $user->fill($data)->save();

        return response()->json(['message' => 'Profil berhasil diperbarui.', 'user' => $this->userData($user->loadMissing('role'))]);
    }

    public function sendPasswordResetCode(Request $request)
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower(trim($data['email']));
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            throw ValidationException::withMessages(['email' => 'Email tidak ditemukan atau belum terdaftar.']);
        }

        $code = (string) random_int(100000, 999999);
        DB::table('password_reset_codes')->updateOrInsert(
            ['email' => $email],
            [
                'code' => Hash::make($code),
                'attempts' => 0,
                'verified_at' => null,
                'expires_at' => now()->addMinutes(10),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        try {
            Mail::mailer('smtp')->to($user->email)->send(new PasswordResetCodeMail($code));
        } catch (Throwable $exception) {
            report($exception);
            DB::table('password_reset_codes')->where('email', $email)->delete();

            return response()->json([
                'message' => 'Email reset tidak dapat dikirim. Periksa konfigurasi email server lalu coba lagi.',
            ], 503);
        }

        return response()->json(['message' => 'Kode verifikasi telah dikirim. Kode berlaku selama 10 menit.']);
    }

    public function verifyPasswordResetCode(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'code' => ['required', 'digits:6'],
        ]);
        $email = strtolower(trim($data['email']));
        $resetCode = $this->activePasswordResetCode($email);

        if (! $resetCode || ! User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            DB::table('password_reset_codes')->where('email', $email)->delete();
            throw ValidationException::withMessages(['code' => 'Kode tidak valid atau sudah kedaluwarsa. Minta kode baru.']);
        }

        if (! Hash::check($data['code'], $resetCode->code)) {
            DB::table('password_reset_codes')->where('email', $email)->increment('attempts');
            throw ValidationException::withMessages(['code' => 'Kode verifikasi salah.']);
        }

        DB::table('password_reset_codes')->where('email', $email)->update([
            'verified_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Email berhasil diverifikasi. Silakan buat kata sandi baru.']);
    }

    public function resetPassword(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        $email = strtolower(trim($data['email']));
        $resetCode = $this->activePasswordResetCode($email);

        if (! $resetCode || ! User::whereRaw('LOWER(email) = ?', [$email])->exists()) {
            DB::table('password_reset_codes')->where('email', $email)->delete();
            throw ValidationException::withMessages(['email' => 'Verifikasi email terlebih dahulu atau minta kode reset baru.']);
        }
        if (! $resetCode->verified_at) {
            throw ValidationException::withMessages(['email' => 'Verifikasi email terlebih dahulu atau minta kode reset baru.']);
        }

        $user = User::whereRaw('LOWER(email) = ?', [$email])->firstOrFail();
        $user->update(['password' => $data['password']]);
        ApiToken::where('user_id', $user->id)->delete();
        DB::table('password_reset_codes')->where('email', $email)->delete();

        return response()->json(['message' => 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.']);
    }

    public function dashboard(Request $request)
    {
        $now = now('Asia/Jakarta');
        $todayEvents = $this->eventsOnDate($now->toDateString());
        $todayEventModels = (clone $todayEvents)->get();
        $ongoingEvents = $todayEventModels->filter(function (Event $event) use ($now) {
            $start = CarbonImmutable::parse(
                $event->date->toDateString().' '.$event->start_time,
                'Asia/Jakarta'
            );

            return $start->lte($now) && $event->endAt()->gt($now);
        })->count();

        return response()->json([
            'stats' => [
                'total_rooms' => Room::count(),
                'rooms_in_use' => (clone $todayEvents)->whereIn('status', ['pending', 'approved'])->distinct('room_id')->count('room_id'),
                'today_events' => $todayEvents->count(),
                'ongoing_events' => $ongoingEvents,
                'pending_complaints' => Complaint::where('status_repair', 'Belum Diperbaiki')->count(),
            ],
            'recent_events' => Event::with(['room:id,name', 'user:id,name'])
                ->orderByDesc('date')->orderByDesc('start_time')->take(5)->get(),
            'notifications' => SystemNotification::where('user_id', $request->user()->id)
                ->whereNull('read_at')->latest()->get(['id', 'event_id', 'title', 'message', 'created_at']),
        ]);
    }

    public function calendar(Request $request, IndonesiaHolidayCalendar $holidayCalendar)
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $month = isset($data['month'])
            ? CarbonImmutable::createFromFormat('Y-m', $data['month'])->startOfMonth()
            : today()->startOfMonth();

        $search = trim($data['search'] ?? '');
        $targetDate = null;

        if ($search !== '') {
            $searchLower = strtolower($search);
            $baseYear = (int) $month->year;

            // 1. Periksa libur nasional
            foreach ([$baseYear, $baseYear + 1, $baseYear - 1] as $yr) {
                try {
                    $yrHolidays = $holidayCalendar->holidaysForYear($yr);
                } catch (\Throwable) {
                    $yrHolidays = $holidayCalendar->getBuiltinHolidaysForYear($yr);
                }
                foreach ($yrHolidays as $dateStr => $holidayTitle) {
                    if (str_contains(strtolower($holidayTitle), $searchLower)) {
                        $targetDate = $dateStr;
                        break 2;
                    }
                }
            }

            // 2. Periksa agenda ruangan
            $matchedEvent = Event::with(['room', 'user.role'])
                ->where('status', 'approved')
                ->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhereHas('room', function ($rq) use ($search) {
                            $rq->where('name', 'like', "%{$search}%")
                                ->orWhere('location', 'like', "%{$search}%");
                        });
                })
                ->orderBy('date')
                ->first();

            if ($matchedEvent !== null && ($targetDate === null || $matchedEvent->date->toDateString() < $targetDate)) {
                $targetDate = $matchedEvent->date->toDateString();
            }

            if ($targetDate !== null) {
                $month = CarbonImmutable::parse($targetDate)->startOfMonth();
            }
        }

        $holidays = [];
        try {
            $holidays = $holidayCalendar->holidaysForYear((int) $month->year);
        } catch (\Throwable $e) {
            $holidays = $holidayCalendar->getBuiltinHolidaysForYear((int) $month->year);
        }

        $eventsQuery = Event::with(['room:id,name,location', 'user.role'])
            ->where('status', 'approved')
            ->whereDate('date', '<=', $month->endOfMonth())
            ->where(function ($query) use ($month) {
                $monthStart = $month->startOfMonth();
                $query->whereDate('end_date', '>=', $monthStart)
                    ->orWhere(function ($query) use ($monthStart) {
                        $query->whereNull('end_date')
                            ->whereDate('date', '>=', $monthStart);
                    })
                    ->orWhere(function ($query) use ($monthStart) {
                        $query->whereNull('end_date')
                            ->whereColumn('end_time', '<', 'start_time')
                            ->whereDate('date', '>=', $monthStart->copy()->subDay());
                    });
            });

        $events = $eventsQuery->orderBy('date')->orderBy('start_time')->get()
            ->each(fn (Event $event) => $event->setAttribute(
                'organizer_role',
                $event->user?->role?->name
            ));

        return response()->json([
            'month' => $month->format('Y-m'),
            'target_date' => $targetDate,
            'holidays' => $holidays,
            'events' => $events,
        ]);
    }

    public function rooms(Request $request)
    {
        $query = Room::with('pic:id,name')->orderBy('name');
        if ($request->user()->role->name === 'pic') {
            $query->where('pic_id', $request->user()->id);
        }

        $rooms = $query->get(['id', 'name', 'location', 'capacity', 'description', 'pic_id']);

        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->toDateString();
        $activeEvents = Event::with('user:id,name')
            ->where('status', 'approved')
            ->whereDate('date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->get()
            ->filter(fn (Event $ev) => $now->betweenIncluded($ev->startAt(), $ev->endAt()));

        $activeByRoom = $activeEvents->keyBy('room_id');

        $rooms->each(function (Room $room) use ($activeByRoom) {
            $current = $activeByRoom->get($room->id);
            $room->setAttribute('is_in_use', $current !== null);
            if ($current) {
                $room->setAttribute('current_event', [
                    'id' => $current->id,
                    'title' => $current->title,
                    'start_time' => substr($current->start_time, 0, 5),
                    'end_time' => substr($current->end_time, 0, 5),
                    'borrower_name' => $current->borrower_name ?: $current->user?->name,
                ]);
            } else {
                $room->setAttribute('current_event', null);
            }
        });

        return response()->json([
            'rooms' => $rooms,
        ]);
    }

    public function createRoom(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role->name === 'admin', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'description' => ['nullable', 'string'],
            'pic_id' => ['nullable', 'exists:users,id'],
        ], [
            'name.required' => 'Nama ruangan wajib diisi.',
            'capacity.integer' => 'Kapasitas ruangan harus berupa angka bulat.',
            'capacity.min' => 'Kapasitas ruangan minimal 1 orang.',
        ]);

        $room = Room::create($data)->load('pic:id,name');

        return response()->json([
            'status' => 'success',
            'message' => 'Ruangan berhasil ditambahkan.',
            'room' => $room,
        ], 201);
    }

    public function updateRoom(Request $request, Room $room)
    {
        $user = $request->user();
        abort_unless($user->role->name === 'admin', 403);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'description' => ['nullable', 'string'],
            'pic_id' => ['nullable', 'exists:users,id'],
        ], [
            'name.required' => 'Nama ruangan wajib diisi.',
            'capacity.integer' => 'Kapasitas ruangan harus berupa angka bulat.',
            'capacity.min' => 'Kapasitas ruangan minimal 1 orang.',
        ]);

        $room->update($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Ruangan berhasil diperbarui.',
            'room' => $room->load('pic:id,name'),
        ]);
    }

    public function deleteRoom(Request $request, Room $room)
    {
        $user = $request->user();
        abort_unless($user->role->name === 'admin', 403);

        $room->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Ruangan berhasil dihapus.',
        ]);
    }

    public function lessonPeriods()
    {
        return response()->json(['periods' => LessonSchedule::periods()]);
    }

    public function damagedGoodsReport(Request $request)
    {
        $user = $request->user();
        abort_unless($user->role->name === 'pic', 403);
        $data = $request->validate([
            'period_type' => ['nullable', 'in:month,week,day'],
            'date' => ['nullable', 'date_format:Y-m-d'],
            'month' => ['nullable', 'date_format:Y-m'],
            'selected_month' => ['nullable', 'date_format:Y-m'],
        ]);
        $type = $data['period_type'] ?? 'month';
        $selectedDate = isset($data['date'])
            ? CarbonImmutable::parse($data['date'], 'Asia/Jakarta')
            : (isset($data['selected_month']) || isset($data['month'])
                ? CarbonImmutable::createFromFormat('Y-m', $data['selected_month'] ?? $data['month'], 'Asia/Jakarta')
                : CarbonImmutable::now('Asia/Jakarta'));
        if ($type === 'month') {
            $start = $selectedDate->startOfMonth();
            $end = $start->addMonth();
            $period = $start->locale('id')->translatedFormat('F Y');
        } elseif ($type === 'week') {
            $start = $selectedDate->startOfWeek(CarbonImmutable::MONDAY);
            $end = $start->addWeek();
            $period = $start->format('d/m/Y').' – '.$end->subDay()->format('d/m/Y');
        } else {
            $start = $selectedDate->startOfDay();
            $end = $start->addDay();
            $period = $start->locale('id')->translatedFormat('l, d F Y');
        }
        $complaints = Complaint::with(['room:id,name', 'reporter:id,name'])
            ->whereHas('room', fn ($rooms) => $rooms->where('pic_id', $user->id))
            ->where('created_at', '>=', $start->utc())
            ->where('created_at', '<', $end->utc())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'period_type' => $type,
            'period' => $period,
            'month' => $start->format('Y-m'),
            'start_date' => $start->toDateString(),
            'end_date' => $end->subDay()->toDateString(),
            'total' => $complaints->count(),
            'by_approval' => $complaints->countBy('approval_status'),
            'by_repair' => $complaints->countBy('status_repair'),
            'complaints' => $complaints,
        ]);
    }

    public function events(Request $request)
    {
        $role = $request->user()->role->name;
        abort_unless(in_array($role, ['guru', 'organisasi']), 403);

        $query = Event::with(['room:id,name,location', 'user:id,name'])->orderByDesc('date')->orderByDesc('start_time');
        if (in_array($role, ['guru', 'organisasi'])) {
            $query->where('user_id', $request->user()->id)
                ->whereNull('deleted_by_creator_at');
        }
        $events = $query->get();
        $events->each(function (Event $event) use ($request) {
            $event->setAttribute('can_delete', $event->canBeDeletedBy($request->user()));
            $event->setAttribute('can_checkout', $event->canBeCheckedOut());
            $event->setAttribute('has_started', $event->hasStarted());
            $event->setAttribute('returned_at_formatted', $event->returned_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'));
            if ($event->status === 'approved') {
                $event->setAttribute('execution_status', $event->executionStatus());
            }
        });

        return response()->json(['events' => $events]);
    }

    public function createEvent(Request $request)
    {
        $user = $request->user();
        $role = $user->role->name;
        abort_unless(in_array($role, ['guru', 'organisasi', 'admin']), 403);
        $isAdmin = $role === 'admin';

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

        $dateRules = ['required', 'date_format:Y-m-d'];
        if (! $isAdmin) {
            $dateRules[] = 'after_or_equal:'.now('Asia/Jakarta')->addDay()->toDateString();
        }

        $data = $request->validate([
            'room_ids' => ['required', 'array', 'min:1'],
            'room_ids.*' => ['exists:rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_whatsapp' => ['required', 'string', 'regex:/^\+?[0-9]{8,20}$/'],
            'description' => ['nullable', 'string'],
            'date' => $dateRules,
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'start_lesson' => ['required', 'in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14'],
            'end_lesson' => ['required', 'in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14'],
            'start_custom_time' => ['required_if:start_lesson,custom', 'nullable', 'date_format:H:i'],
            'end_custom_time' => ['required_if:end_lesson,custom', 'nullable', 'date_format:H:i'],
        ], [
            'room_ids.required' => 'Pilih minimal satu ruangan yang akan dipinjam.',
            'room_ids.min' => 'Pilih minimal satu ruangan yang akan dipinjam.',
            'date.after_or_equal' => 'Peminjaman ruangan hanya dapat diajukan paling lambat H-1 sebelum digunakan.',
        ]);
        $endDate = $data['end_date'] ?? $data['date'];
        $multiDay = $endDate !== $data['date'];

        $periods = LessonSchedule::periods();
        $startTime = $data['start_lesson'] === 'custom' ? $data['start_custom_time'] : $periods[(int) $data['start_lesson']]['start'];
        $endTime = $data['end_lesson'] === 'custom' ? $data['end_custom_time'] : $periods[(int) $data['end_lesson']]['end'];
        if (! $multiDay && $data['start_lesson'] !== 'custom' && $data['end_lesson'] !== 'custom'
            && (int) $data['end_lesson'] < (int) $data['start_lesson']) {
            throw ValidationException::withMessages(['end_lesson' => 'Jam pelajaran selesai tidak boleh sebelum jam mulai.']);
        }

        $startMinute = $this->minutesAfterMidnight($startTime);
        $endMinute = $this->minutesAfterMidnight($endTime);
        if (($data['start_lesson'] === 'custom' && $startMinute < 17 * 60 + 45)
            || ($data['end_lesson'] === 'custom' && $endMinute < 17 * 60 + 45 && $endMinute > 6 * 60)) {
            throw ValidationException::withMessages(['start_custom_time' => 'Jam bebas hanya dapat digunakan mulai pukul 17.45 sampai 06.00 hari berikutnya.']);
        }
        $start = $startMinute < LessonSchedule::FIRST_START_MINUTE ? $startMinute + 1440 : $startMinute;
        $end = $endMinute < LessonSchedule::FIRST_START_MINUTE ? $endMinute + 1440 : $endMinute;
        if (! $multiDay && ($end <= $start || $end > 30 * 60)) {
            throw ValidationException::withMessages(['end_lesson' => 'Jam selesai harus setelah jam mulai dan paling lambat pukul 06.00 hari berikutnya.']);
        }

        $validRooms = Room::whereIn('id', $roomIds)->get()->keyBy('id');
        $collidingRooms = [];
        foreach ($roomIds as $rId) {
            try {
                $this->ensureNoEventCollision($rId, $data['date'], $endDate, $startTime, $endTime);
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
            $eventPayload = [
                ...$data,
                'room_id' => $rId,
                'end_date' => $endDate !== $data['date'] ? $endDate : null,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'user_id' => $user->id,
                'status' => $isAdmin ? 'approved' : 'pending',
            ];
            unset($eventPayload['room_ids']);
            if ($bookingGroupId && $hasGroupCol) {
                $eventPayload['booking_group_id'] = $bookingGroupId;
            }
            $created = Event::create($eventPayload)->load(['room:id,name', 'user:id,name']);
            $createdEvents[] = $created;
        }

        $message = count($createdEvents) > 1
            ? ($isAdmin
                ? count($createdEvents) . ' ruangan berhasil dibooking dan langsung disetujui.'
                : count($createdEvents) . ' ruangan berhasil diajukan dan menunggu persetujuan.')
            : ($isAdmin
                ? 'Agenda berhasil dibuat dan langsung disetujui.'
                : 'Agenda diajukan dan menunggu persetujuan.');

        return response()->json([
            'message' => $message,
            'event' => $createdEvents[0],
            'events' => $createdEvents,
        ], 201);
    }

    public function deleteEvent(Request $request, Event $event)
    {
        abort_unless(
            $event->canBeDeletedBy($request->user()),
            403,
            'Agenda hanya dapat dihapus setelah ditolak atau tiga hari setelah pelaksanaan.'
        );

        $event->load('room');
        $title = $event->title;
        $roomName = $event->room?->name ?? 'Ruangan';

        if ($event->status === 'approved') {
            $event->update(['deleted_by_creator_at' => now('Asia/Jakarta')]);
            ActivityLog::record(
                'Hapus Riwayat Agenda',
                $request->user()->name . " menghapus agenda terlaksana '{$title}' di {$roomName} dari riwayat pribadinya."
            );
        } else {
            $event->delete();
            ActivityLog::record(
                'Hapus Agenda',
                $request->user()->name . " menghapus agenda '{$title}' di {$roomName}."
            );
        }

        return response()->json(['message' => 'Agenda berhasil dihapus.']);
    }

    public function checkoutEvent(Request $request, Event $event)
    {
        $user = $request->user();
        abort_unless(
            $user->role?->name === 'admin'
            || $user->id === $event->user_id
            || ($user->role?->name === 'pic' && $event->room?->pic_id === $user->id),
            403,
            'Anda tidak memiliki akses untuk menyelesaikan agenda ini.'
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
            'checkout_notes' => $data['checkout_notes'] ?? null,
        ]);

        $event->load('room');
        ActivityLog::record(
            'Pengembalian Ruangan',
            $user->name . " mengonfirmasi pengembalian ruangan {$event->room?->name} untuk agenda '{$event->title}' via mobile (Kondisi: " . ($data['checkout_condition'] === 'rusak' ? 'Ada Kerusakan Dilaporkan' : 'Aman & Bersih') . ")."
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Ruangan berhasil dikonfirmasi telah selesai digunakan & dikembalikan.',
            'event' => $event->fresh(['room:id,name,location', 'user:id,name']),
        ]);
    }

    public function eventReceipt(Request $request, Event $event)
    {
        $user = $request->user();
        abort_unless(
            in_array($user->role?->name, ['admin', 'pic'], true) || $user->id === $event->user_id,
            403
        );
        abort_unless($event->status === 'approved', 404, 'Bukti peminjaman hanya tersedia untuk agenda yang telah disetujui.');

        $event->load(['room.pic', 'user']);

        return response()->json([
            'status' => 'success',
            'event' => $event,
            'receipt' => [
                'verification_code' => 'RAPI-' . str_pad((string) $event->id, 5, '0', STR_PAD_LEFT),
                'title' => $event->title,
                'room_name' => $event->room?->name,
                'room_location' => $event->room?->location,
                'pic_name' => $event->room?->pic?->name ?? 'Pengelola Sarpras',
                'applicant_name' => $event->user?->name,
                'applicant_email' => $event->user?->email,
                'borrower_name' => $event->borrower_name,
                'borrower_whatsapp' => $event->borrower_whatsapp,
                'description' => $event->description,
                'date' => $event->date->format('Y-m-d'),
                'date_formatted' => $event->date->locale('id')->translatedFormat('l, d F Y'),
                'end_date' => $event->end_date?->format('Y-m-d'),
                'end_date_formatted' => $event->end_date?->locale('id')->translatedFormat('l, d F Y'),
                'start_time' => substr($event->start_time, 0, 5),
                'end_time' => substr($event->end_time, 0, 5),
                'approved_at' => $event->updated_at->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'returned_at' => $event->returned_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i'),
                'checkout_condition' => $event->checkout_condition,
                'checkout_notes' => $event->checkout_notes,
            ],
        ]);
    }

    public function complaints(Request $request)
    {
        $user = $request->user();
        $role = $user->role->name;
        $query = Complaint::with(['room:id,name,location,pic_id', 'room.pic:id,name', 'reporter:id,name', 'event:id,title,date']);
        if ($role === 'pic') {
            $query->whereHas('room', fn ($rooms) => $rooms->where('pic_id', $user->id));
        } elseif (in_array($role, ['guru', 'organisasi'])) {
            $query->where('reporter_id', $user->id);
        }

        return response()->json(['complaints' => $query->orderByDesc('created_at')->get()]);
    }

    public function complaintPhoto(Request $request, Complaint $complaint)
    {
        $this->authorizeComplaintAccess($request, $complaint);
        abort_unless($complaint->photo_path && Storage::disk('public')->exists($complaint->photo_path), 404);

        return Storage::disk('public')->response($complaint->photo_path);
    }

    public function complaintRepairPhoto(Request $request, Complaint $complaint)
    {
        $this->authorizeComplaintAccess($request, $complaint);
        abort_unless($complaint->repair_photo_path && Storage::disk('public')->exists($complaint->repair_photo_path), 404);

        return Storage::disk('public')->response($complaint->repair_photo_path);
    }

    public function reviewComplaint(Request $request, Complaint $complaint)
    {
        $this->authorizePicComplaint($request, $complaint);
        abort_unless($complaint->approval_status === 'pending', 403, 'Pengaduan ini sudah diputuskan.');
        $data = $request->validate(['approval_status' => ['required', Rule::in(['approved', 'rejected'])]]);
        $complaint->update(['approval_status' => $data['approval_status']]);

        return response()->json([
            'message' => $data['approval_status'] === 'approved' ? 'Pengaduan disetujui.' : 'Pengaduan ditolak.',
            'complaint' => $complaint->load(['room:id,name', 'reporter:id,name', 'event:id,title']),
        ]);
    }

    public function updateComplaintRepair(Request $request, Complaint $complaint)
    {
        $this->authorizePicComplaint($request, $complaint);
        abort_unless($complaint->approval_status === 'approved', 403, 'Pengaduan harus disetujui PIC sebelum status perbaikan diperbarui.');
        $data = $request->validate([
            'status_repair' => ['required', Rule::in(['Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki'])],
            'repair_notes' => ['nullable', 'string'],
            'repair_photo' => [
                Rule::requiredIf(fn () => $request->status_repair === 'Sudah Diperbaiki' && ! $complaint->repair_photo_path),
                'nullable',
                'image',
                'max:10240',
            ],
        ]);

        if ($request->hasFile('repair_photo')) {
            if ($complaint->repair_photo_path && Storage::disk('public')->exists($complaint->repair_photo_path)) {
                Storage::disk('public')->delete($complaint->repair_photo_path);
            }
            $data['repair_photo_path'] = app(ComplaintPhotoStorage::class)->store($request->file('repair_photo'), 'repair_');
        }
        unset($data['repair_photo']);

        $complaint->update($data);

        return response()->json([
            'message' => 'Status pengaduan diperbarui.',
            'complaint' => $complaint->load(['room:id,name', 'reporter:id,name', 'event:id,title']),
        ]);
    }

    public function notifications(Request $request)
    {
        $userId = $request->user()->id;
        $notifications = SystemNotification::where('user_id', $userId)
            ->latest()
            ->take(30)
            ->get(['id', 'event_id', 'title', 'message', 'read_at', 'created_at']);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => SystemNotification::where('user_id', $userId)->whereNull('read_at')->count(),
        ]);
    }

    public function markAllNotificationsRead(Request $request)
    {
        SystemNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now('Asia/Jakarta')]);

        return response()->json(['message' => 'Semua notifikasi ditandai sudah dibaca.']);
    }

    public function markNotificationRead(Request $request, SystemNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now('Asia/Jakarta')]);

        return response()->json(['message' => 'Notifikasi ditandai sudah dibaca.']);
    }

    public function createComplaint(Request $request)
    {
        $user = $request->user();
        abort_unless(in_array($user->role->name, ['guru', 'organisasi']), 403);

        $data = $request->validate([
            'room_id' => ['nullable', 'exists:rooms,id'],
            'event_id' => ['nullable', 'exists:events,id'],
            'facility_name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $event = isset($data['event_id']) ? Event::findOrFail($data['event_id']) : null;
        if ($event) {
            abort_unless($event->user_id === $user->id && $event->status === 'approved', 403, 'Pengaduan hanya dapat dibuat untuk agenda milik Anda yang telah disetujui.');
        }
        if ($event) {
            $data['room_id'] = $event->room_id;
        } elseif (empty($data['room_id'])) {
            throw ValidationException::withMessages(['room_id' => 'Ruangan wajib dipilih.']);
        }

        if ($request->hasFile('photo')) {
            $data['photo_path'] = app(ComplaintPhotoStorage::class)->store($request->file('photo'));
        }
        unset($data['photo']);
        $complaint = Complaint::create([
            ...$data,
            'reporter_id' => $user->id,
            'approval_status' => 'pending',
        ])->load(['room:id,name', 'reporter:id,name']);

        return response()->json(['message' => 'Pengaduan kerusakan berhasil dicatat dan menunggu persetujuan PIC.', 'complaint' => $complaint], 201);
    }

    private function userData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'whatsapp' => $user->whatsapp,
            'role' => $user->role?->name,
        ];
    }

    private function activePasswordResetCode(string $email): ?object
    {
        $resetCode = DB::table('password_reset_codes')->where('email', $email)->first();
        if (! $resetCode || now()->greaterThan($resetCode->expires_at) || $resetCode->attempts >= 5) {
            return null;
        }

        return $resetCode;
    }

    private function authorizeComplaintAccess(Request $request, Complaint $complaint): void
    {
        $user = $request->user();
        $role = $user->role->name;
        $allowed = match ($role) {
            'pic' => $complaint->room()->where('pic_id', $user->id)->exists(),
            'guru', 'organisasi' => $complaint->reporter_id === $user->id,
            default => false,
        };
        abort_unless($allowed, 403);
    }

    private function authorizePicComplaint(Request $request, Complaint $complaint): void
    {
        $user = $request->user();
        abort_unless($user->role->name === 'pic' && $complaint->room()->where('pic_id', $user->id)->exists(), 403);
    }

    private function minutesAfterMidnight(string $time): int
    {
        [$hours, $minutes] = array_map('intval', explode(':', substr($time, 0, 5)));

        return $hours * 60 + $minutes;
    }

    private function eventsOnDate(string $date)
    {
        $previousDate = CarbonImmutable::parse($date)->subDay()->toDateString();

        return Event::whereDate('date', '<=', $date)
            ->where(function ($query) use ($date, $previousDate) {
                $query->whereDate('end_date', '>=', $date)
                    ->orWhere(function ($query) use ($date) {
                        $query->whereNull('end_date')->whereDate('date', '>=', $date);
                    })
                    ->orWhere(function ($query) use ($previousDate) {
                        $query->whereNull('end_date')
                            ->whereColumn('end_time', '<', 'start_time')
                            ->whereDate('date', $previousDate);
                    });
            });
    }

    private function ensureNoEventCollision(
        int|string $roomId,
        string $date,
        string $endDate,
        string $startTime,
        string $endTime
    ): void
    {
        $bookingStart = CarbonImmutable::parse($date.' '.$startTime, 'Asia/Jakarta')->getTimestamp() / 60;
        $bookingEndDate = CarbonImmutable::parse($endDate, 'Asia/Jakarta');
        if ($endDate === $date && substr($endTime, 0, 5) <= substr($startTime, 0, 5)) {
            $bookingEndDate = $bookingEndDate->addDay();
        }
        $bookingEnd = CarbonImmutable::parse(
            $bookingEndDate->toDateString().' '.$endTime,
            'Asia/Jakarta'
        )->getTimestamp() / 60;
        $previousDate = CarbonImmutable::parse($date, 'Asia/Jakarta')->subDay()->toDateString();
        $events = Event::where('room_id', $roomId)
            ->whereDate('date', '<=', $endDate)
            ->where(function ($query) use ($date, $previousDate) {
                $query->whereDate('date', '>=', $previousDate)
                    ->orWhereDate('end_date', '>=', $date);
            })
            ->whereIn('status', ['pending', 'approved'])
            ->get(['date', 'end_date', 'start_time', 'end_time']);

        foreach ($events as $event) {
            $eventStart = CarbonImmutable::parse(
                $event->date->toDateString().' '.$event->start_time,
                'Asia/Jakarta'
            )->getTimestamp() / 60;
            $eventEnd = $event->endAt()->getTimestamp() / 60;
            if ($eventStart < $bookingEnd && $eventEnd > $bookingStart) {
                throw ValidationException::withMessages(['start_lesson' => 'Jadwal bertabrakan dengan agenda lain di ruangan yang sama.']);
            }
        }
    }
}
