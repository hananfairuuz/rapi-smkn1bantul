<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Room;
use App\Models\SystemNotification;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Controller Manajemen Data Ruangan (Fasilitas Sekolah)
 *
 * Fungsi utama:
 * 1. Melihat daftar ruangan & ketersediaan statusnya secara realtime (Sedang Dipakai / Tersedia).
 * 2. Filter ruangan khusus untuk PIC (hanya melihat ruangan yang menjadi tanggung jawabnya).
 * 3. Kelola data master ruangan (Tambah, Ubah, Hapus) khusus untuk Admin.
 * 4. Menugaskan guru/staf dengan role PIC ke ruangan tertentu.
 */
class RoomController extends Controller
{
    /**
     * Helper Keamanan: Memastikan hanya user ber-role 'admin' yang dapat menambah/mengedit/menghapus ruangan.
     */
    private function canManageRooms(): void
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
    }

    /**
     * Menampilkan daftar ruangan lengkap dengan status pemakaian realtime detik ini.
     * Dapat diakses oleh Admin, PIC, Guru, dan Organisasi.
     */
    public function index()
    {
        abort_unless(in_array(auth()->user()->role?->name, ['admin', 'pic', 'guru', 'organisasi']), 403);
        $search = trim((string) request('search', ''));
        $statusFilter = request('status', 'all');

        // Query dasar ruangan beserta PIC penanggung jawabnya
        $query = Room::with('pic');

        // Jika user adalah PIC, hanya tampilkan ruangan binaannya
        if (auth()->user()->role?->name === 'pic') {
            $query->where('pic_id', auth()->id());
        }

        // Filter pencarian berdasarkan nama ruangan jika diisi
        $query->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        $rooms = $query->orderBy('name')->get();

        // Cek status pemakaian realtime & seluruh agenda dalam pembokingan aktif
        $now = CarbonImmutable::now('Asia/Jakarta');
        $today = $now->toDateString();

        $relevantEvents = Event::with('user:id,name')
            ->whereIn('status', ['pending', 'approved'])
            ->whereNull('deleted_by_creator_at')
            ->whereNull('returned_at')
            ->where(function ($q) use ($today, $now) {
                $q->whereDate('date', '>=', $now->subDay()->toDateString())
                    ->orWhereDate('end_date', '>=', $today);
            })
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        // Petakan event aktif yang sedang berlangsung detik ini per room_id
        $activeByRoom = $relevantEvents
            ->filter(fn (Event $ev) => $ev->status === 'approved' && $now->betweenIncluded($ev->startAt(), $ev->endAt()))
            ->keyBy('room_id');

        // Petakan seluruh agenda yang masih dalam pembokingan (sedang dipakai atau akan datang)
        $bookingsByRoom = Room::filterActiveBookings($relevantEvents, $now)->groupBy('room_id');

        // Tandai setiap ruangan status realtime dan daftar peminjaman aktifnya
        $rooms = $rooms->map(function ($room) use ($activeByRoom, $bookingsByRoom) {
            $currentEvent = $activeByRoom->get($room->id);
            $roomBookings = $bookingsByRoom->get($room->id, collect())->values();
            $room->is_in_use = $currentEvent !== null;
            $room->current_event = $currentEvent;
            $room->active_bookings = $roomBookings;
            $room->active_bookings_count = $roomBookings->count();
            $room->has_active_bookings = $roomBookings->isNotEmpty();

            return $room;
        });

        // Filter status ketersediaan jika dipilih user (tersedia vs sedang dipakai)
        if ($statusFilter === 'available') {
            $rooms = $rooms->where('is_in_use', false)->values();
        } elseif ($statusFilter === 'in_use') {
            $rooms = $rooms->where('is_in_use', true)->values();
        }

        return view('rooms.index', [
            'rooms' => $rooms,
            'allRooms' => Room::orderBy('name')->get(['id', 'name', 'location', 'capacity']),
            'search' => $search,
            'statusFilter' => $statusFilter,
            'canManage' => auth()->user()->role?->name === 'admin',
        ]);
    }

    /**
     * Form Tambah Ruangan Baru (Khusus Admin).
     */
    public function create()
    {
        $this->canManageRooms();

        return view('rooms.form', ['room' => new Room, 'pics' => $this->pics()]);
    }

    /**
     * Menyimpan data ruangan baru ke database dan mencatat log aktivitas.
     */
    public function store(Request $request)
    {
        $this->canManageRooms();
        $room = Room::create($this->validated($request));
        ActivityLog::record('Tambah Ruangan', auth()->user()->name . " menambahkan ruangan baru '{$room->name}'.");
        SystemNotification::notifyRoomPicAssigned($room, auth()->user(), null);

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil ditambahkan.');
    }

    /**
     * Form Edit Data Ruangan (Khusus Admin).
     */
    public function edit(Room $room)
    {
        $this->canManageRooms();

        return view('rooms.form', ['room' => $room, 'pics' => $this->pics()]);
    }

    /**
     * Menyimpan perubahan data ruangan dan mencatat log aktivitas.
     */
    public function update(Request $request, Room $room)
    {
        $this->canManageRooms();
        $previousPicId = $room->pic_id;
        $room->update($this->validated($request));
        ActivityLog::record('Edit Ruangan', auth()->user()->name . " memperbarui informasi ruangan '{$room->name}'.");
        SystemNotification::notifyRoomPicAssigned($room, auth()->user(), $previousPicId);

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    /**
     * Menghapus data ruangan dari database (Khusus Admin).
     * Ruangan yang sedang dipakai atau masih memiliki pembokingan aktif tidak dapat langsung dihapus
     * sebelum dilakukan pergantian ruangan untuk peminjamnya.
     */
    public function destroy(Request $request, Room $room)
    {
        $this->canManageRooms();

        $activeBookings = $room->activeBookings();
        if ($activeBookings->isNotEmpty()) {
            $replacementId = $request->input('replacement_room_id');
            if (! $replacementId) {
                return redirect()->route('rooms.index')->withErrors([
                    'room_delete' => "Ruangan '{$room->name}' tidak dapat dihapus karena sedang dipakai atau masih dalam pembokingan ({$activeBookings->count()} agenda aktif). Jika ingin menghapus ruangan, harus ada pergantian ruangan terlebih dahulu untuk yang meminjam ruangan tersebut.",
                ]);
            }

            $data = $request->validate([
                'replacement_room_id' => ['required', 'integer', 'exists:rooms,id', Rule::notIn([$room->id])],
                'move_reason' => ['required', 'string', 'max:500'],
            ], [
                'replacement_room_id.required' => 'Pilih ruangan pengganti untuk memindahkan peminjam ruangan ini.',
                'replacement_room_id.not_in' => 'Ruangan pengganti tidak boleh sama dengan ruangan yang akan dihapus.',
                'move_reason.required' => 'Alasan pergantian ruangan wajib diisi.',
            ]);

            $replacementRoom = Room::findOrFail($data['replacement_room_id']);
            try {
                $movedCount = $room->moveActiveBookingsTo($replacementRoom, trim($data['move_reason']));
            } catch (\RuntimeException $e) {
                return redirect()->route('rooms.index')->withErrors([
                    'room_delete' => $e->getMessage(),
                ]);
            }

            $name = $room->name;
            $room->delete();
            ActivityLog::record(
                'Hapus & Ganti Ruangan',
                auth()->user()->name . " memindahkan {$movedCount} peminjaman dari '{$name}' ke '{$replacementRoom->name}' (Alasan: {$data['move_reason']}) lalu menghapus '{$name}'."
            );

            return redirect()->route('rooms.index')->with(
                'success',
                "Sebanyak {$movedCount} peminjaman telah dipindahkan ke '{$replacementRoom->name}' (notifikasi dikirim ke peminjam) dan ruangan '{$name}' berhasil dihapus."
            );
        }

        $name = $room->name;
        $room->delete();
        ActivityLog::record('Hapus Ruangan', auth()->user()->name . " menghapus ruangan '{$name}'.");

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil dihapus.');
    }

    /**
     * Mengambil daftar seluruh pengguna yang memiliki hak akses sebagai PIC Ruangan.
     */
    private function pics()
    {
        return User::whereHas('role', fn ($q) => $q->where('name', 'pic'))->orderBy('name')->get();
    }

    /**
     * Validasi input form ruangan:
     * - name: Wajib, maksimal 255 karakter.
     * - capacity: Opsional, angka bulat positif (1-10.000).
     * - pic_id: Opsional, namun jika diisi harus merujuk ke user dengan role 'pic'.
     */
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
