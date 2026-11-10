<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Event;
use App\Models\Room;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;

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

        // Cek status pemakaian realtime: apakah detik ini ada jadwal yang disetujui sedang berlangsung
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

        // Petakan event aktif per room_id
        $activeByRoom = $activeEvents->keyBy('room_id');

        // Tandai setiap ruangan apakah sedang terpakai (is_in_use = true/false)
        $rooms = $rooms->map(function ($room) use ($activeByRoom) {
            $currentEvent = $activeByRoom->get($room->id);
            $room->is_in_use = $currentEvent !== null;
            $room->current_event = $currentEvent;

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
        $room->update($this->validated($request));
        ActivityLog::record('Edit Ruangan', auth()->user()->name . " memperbarui informasi ruangan '{$room->name}'.");

        return redirect()->route('rooms.index')->with('success', 'Ruangan berhasil diperbarui.');
    }

    /**
     * Menghapus data ruangan dari database (Khusus Admin).
     */
    public function destroy(Room $room)
    {
        $this->canManageRooms();
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
