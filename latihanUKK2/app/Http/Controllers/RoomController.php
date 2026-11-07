<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\User;
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
        $query = Room::with('pic');
        if (auth()->user()->role?->name === 'pic') {
            $query->where('pic_id', auth()->id());
        }
        $query->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        return view('rooms.index', [
            'rooms' => $query->latest()->get(),
            'search' => $search,
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
        Room::create($this->validated($request));

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

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    public function destroy(Room $room)
    {
        $this->canManageRooms();
        $room->delete();

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
