<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PasswordResetCodeMail;
use App\Models\ApiToken;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\SystemNotification;
use App\Models\User;
use App\Services\ComplaintPhotoStorage;
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
                ->whereNull('read_at')->latest()->get(['id', 'title', 'message', 'created_at']),
        ]);
    }

    public function calendar(Request $request)
    {
        $data = $request->validate(['month' => ['nullable', 'date_format:Y-m']]);
        $month = isset($data['month'])
            ? CarbonImmutable::createFromFormat('Y-m', $data['month'])->startOfMonth()
            : today()->startOfMonth();

        return response()->json([
            'month' => $month->format('Y-m'),
            'events' => Event::with(['room:id,name,location', 'user.role'])
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
                })
                ->orderBy('date')->orderBy('start_time')
                ->get()
                ->each(fn (Event $event) => $event->setAttribute(
                    'organizer_role',
                    $event->user?->role?->name
                )),
        ]);
    }

    public function rooms(Request $request)
    {
        $query = Room::with('pic:id,name')->orderBy('name');
        if ($request->user()->role->name === 'pic') {
            $query->where('pic_id', $request->user()->id);
        }

        return response()->json(['rooms' => $query->get(['id', 'name', 'location', 'description', 'pic_id'])]);
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
            $query->where('user_id', $request->user()->id);
        }
        $events = $query->get();
        $events->each(function (Event $event) use ($request) {
            $event->setAttribute('can_delete', $event->canBeDeletedBy($request->user()));
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
        abort_unless(in_array($role, ['guru', 'organisasi']), 403);
        $data = $request->validate([
            'room_id' => ['required', 'exists:rooms,id'],
            'title' => ['required', 'string', 'max:255'],
            'borrower_name' => ['required', 'string', 'max:255'],
            'borrower_whatsapp' => ['required', 'string', 'regex:/^\+?[0-9]{8,20}$/'],
            'description' => ['nullable', 'string'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.now('Asia/Jakarta')->addDay()->toDateString()],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date'],
            'start_lesson' => ['required', 'in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14'],
            'end_lesson' => ['required', 'in:custom,1,2,3,4,5,6,7,8,9,10,11,12,13,14'],
            'start_custom_time' => ['required_if:start_lesson,custom', 'nullable', 'date_format:H:i'],
            'end_custom_time' => ['required_if:end_lesson,custom', 'nullable', 'date_format:H:i'],
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

        $this->ensureNoEventCollision($data['room_id'], $data['date'], $endDate, $startTime, $endTime);
        $event = Event::create([
            ...$data,
            'end_date' => $endDate !== $data['date'] ? $endDate : null,
            'start_time' => $startTime,
            'end_time' => $endTime,
            'user_id' => $user->id,
            'status' => 'pending',
        ])->load(['room:id,name', 'user:id,name']);

        return response()->json([
            'message' => 'Agenda diajukan dan menunggu persetujuan.',
            'event' => $event,
        ], 201);
    }

    public function deleteEvent(Request $request, Event $event)
    {
        abort_unless(
            $event->canBeDeletedBy($request->user()),
            403,
            'Agenda hanya dapat dihapus setelah ditolak atau tiga hari setelah pelaksanaan.'
        );

        $event->delete();

        return response()->json(['message' => 'Agenda berhasil dihapus.']);
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
            'repair_notes' => ['required_if:status_repair,Sudah Diperbaiki', 'nullable', 'string'],
        ]);
        $complaint->update($data);

        return response()->json([
            'message' => 'Status pengaduan diperbarui.',
            'complaint' => $complaint->load(['room:id,name', 'reporter:id,name', 'event:id,title']),
        ]);
    }

    public function markNotificationRead(Request $request, SystemNotification $notification)
    {
        abort_unless($notification->user_id === $request->user()->id, 403);
        $notification->update(['read_at' => now()]);

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
            'photo' => ['required', 'image', 'max:5120'],
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
