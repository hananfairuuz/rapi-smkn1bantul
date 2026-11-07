<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

class RoomController extends Controller
{
    private function canManageRooms(): void
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
    }

    public function index()
    {
        abort_unless(in_array(auth()->user()->role?->name, ['admin', 'pic', 'guru', 'organisasi']), 403);
        $search = trim((string) request('search', ''));
        $statusFilter = request('status', 'all');

        $query = Room::with('pic');
        if (auth()->user()->role?->name === 'pic') {
            $query->where('pic_id', auth()->id());
        }
        $query->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        $rooms = $query->orderBy('name')->get();

        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->toDateString();

        $activeEvents = Event::with('user')
            ->where('status', 'approved')
            ->whereDate('date', '<=', $today)
            ->where(function ($q) use ($today) {
                $q->whereNull('end_date')->orWhereDate('end_date', '>=', $today);
            })
            ->get()
            ->filter(fn (Event $ev) => $now->betweenIncluded($ev->startAt(), $ev->endAt()));

        $activeByRoom = $activeEvents->keyBy('room_id');

        $rooms = $rooms->map(function ($room) use ($activeByRoom) {
            $currentEvent = $activeByRoom->get($room->id);
            $room->is_in_use = $currentEvent !== null;
            $room->current_event = $currentEvent;

            return $room;
        });

        if ($statusFilter === 'available') {
            $rooms = $rooms->where('is_in_use', false)->values();
        } elseif ($statusFilter === 'in_use') {
            $rooms = $rooms->where('is_in_use', true)->values();
        }

        return view('rooms.index', [
            'rooms' => $rooms,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'canManage' => auth()->user()->role?->name === 'admin',
        ]);
    }

    public function create()
    {
        $this->canManageRooms();

        return view('rooms.form', ['room' => new Room, 'pics' => $this->pics()]);
    }

    public function store(Request $request)
    {
        $this->canManageRooms();
        $room = Room::create($this->validated($request));
        ActivityLog::record('Tambah Ruangan', auth()->user()->name . " menambahkan ruangan baru '{$room->name}'.");

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    public function edit(Room $room)
    {
        $this->canManageRooms();

        return view('rooms.form', ['room' => $room, 'pics' => $this->pics()]);
    }

    public function update(Request $request, Room $room)
    {
        $this->canManageRooms();
        $room->update($this->validated($request));
        ActivityLog::record('Edit Ruangan', auth()->user()->name . " memperbarui informasi ruangan '{$room->name}'.");

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(Room $room)
    {
        $this->canManageRooms();
        $name = $room->name;
        $room->delete();
        ActivityLog::record('Hapus Ruangan', auth()->user()->name . " menghapus ruangan '{$name}'.");

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil dihapus.');
    }

    private function pics()
    {
        return User::whereHas('role', fn ($q) => $q->where('name', 'pic'))->orderBy('name')->get();
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:255',
            'capacity' => 'nullable|integer|min:1|max:10000',
            'description' => 'nullable|string',
            'pic_id' => ['nullable', 'exists:users,id', function (string $attribute, mixed $value, \Closure $fail): void {
                if ($value && ! User::whereKey($value)->whereHas('role', fn ($query) => $query->where('name', 'pic'))->exists()) {
                    $fail('Pengguna yang dipilih harus memiliki peran PIC Ruang.');
                }
            }],
        ], [
            'capacity.integer' => 'Kapasitas ruangan harus berupa angka bulat.',
            'capacity.min' => 'Kapasitas ruangan minimal 1 orang.',
        ]);
    }
}
