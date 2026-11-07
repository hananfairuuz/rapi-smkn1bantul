<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\SystemNotification;
use App\Services\ComplaintPhotoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ComplaintController extends Controller
{
    public function index()
    {
        $query = Complaint::with(['room.pic', 'reporter', 'event']);
        $role = auth()->user()->role?->name;
        if ($role === 'pic') {
            $query->whereHas('room', fn ($q) => $q->where('pic_id', auth()->id()));
        } elseif (in_array($role, ['guru', 'organisasi'])) {
            $query->where('reporter_id', auth()->id());
        }

return view('complaints.index', ['complaints' => $query->orderByDesc('created_at')->orderByDesc('id')->get(), 'whatsappDueAt' => now()->subDay()]);
    }

    public function create(Request $request)
    {
        $event = null;
        $isAdmin = auth()->user()->role?->name === 'admin';
        if ($request->filled('event_id')) {
            $data = $request->validate(['event_id' => 'required|exists:events,id']);
            $event = Event::with('room.pic')->findOrFail($data['event_id']);
            if (! $isAdmin) {
                $this->canCreateComplaint();
                abort_unless(
                    $event->user_id === auth()->id() && $event->status === 'approved',
                    403,
                    'Pengaduan hanya dapat dibuat untuk agenda milik Anda yang telah disetujui.'
                );
            }
        } else {
            if (! $isAdmin) {
                $this->canCreateComplaint();
            }
        }

        return view('complaints.form', [
            'complaint' => new Complaint,
            'rooms' => Room::with('pic')->orderBy('name')->get(),
            'events' => $isAdmin ? Event::with('room.pic')->orderByDesc('date')->orderByDesc('start_time')->get() : collect(),
            'event' => $event,
        ]);
    }

    public function store(Request $request)
    {
        $isAdmin = auth()->user()->role?->name === 'admin';
        if (! $isAdmin) {
            $this->canCreateComplaint();
        }

        $data = $this->validatedForCreate($request, $isAdmin);
        $event = isset($data['event_id']) ? Event::findOrFail($data['event_id']) : null;
        if ($event && ! $isAdmin) {
            abort_unless(
                $event->user_id === auth()->id() && $event->status === 'approved',
                403,
                'Pengaduan hanya dapat dibuat untuk agenda milik Anda yang telah disetujui.'
            );
        }
        if ($event) {
            $data['room_id'] = $event->room_id;
        } elseif (empty($data['room_id'])) {
            throw ValidationException::withMessages(['room_id' => 'Ruangan wajib dipilih.']);
        }
        $data['approval_status'] = 'pending';
        $data['reporter_id'] = auth()->id();
        if ($request->hasFile('photo')) {
            $data['photo_path'] = app(ComplaintPhotoStorage::class)->store($request->file('photo'));
        } unset($data['photo']);
        $complaint = Complaint::create($data);
        $complaint->load('room');
        ActivityLog::record('Laporan Kerusakan', auth()->user()->name . " melaporkan fasilitas rusak '{$complaint->facility_name}' di ruangan {$complaint->room->name}.");

        return redirect()->route('complaints.index')->with('success', 'Pengaduan kerusakan berhasil dicatat dan menunggu persetujuan PIC.');
    }

    public function edit(Complaint $complaint)
    {
        $this->mayManage($complaint);
        abort_unless($complaint->approval_status === 'approved', 403, 'Pengaduan harus disetujui sebelum status perbaikan diperbarui.');
        $complaint->load('room.pic');

        return view('complaints.form', ['complaint' => $complaint, 'rooms' => collect(), 'event' => null]);
    }

    public function update(Request $request, Complaint $complaint)
    {
        $this->mayManage($complaint);
        abort_unless($complaint->approval_status === 'approved', 403, 'Pengaduan harus disetujui PIC sebelum status perbaikan diperbarui.');
        $complaint->update($this->validatedForUpdate($request));
        $complaint->load('room');

        SystemNotification::create([
            'user_id' => $complaint->reporter_id,
            'title' => 'Update Perbaikan Fasilitas',
            'message' => "Status perbaikan fasilitas '{$complaint->facility_name}' di {$complaint->room->name} diperbarui menjadi: {$complaint->status_repair}.",
        ]);
        ActivityLog::record('Tindak Lanjut Perbaikan', auth()->user()->name . " memperbarui status perbaikan '{$complaint->facility_name}' di {$complaint->room->name} menjadi '{$complaint->status_repair}'.");

        return redirect()->route('complaints.index')->with('success', 'Status pengaduan diperbarui.');
    }

    public function review(Request $request, Complaint $complaint)
    {
        $this->mayManage($complaint);
        abort_unless($complaint->approval_status === 'pending', 403, 'Pengaduan ini sudah diputuskan.');

        $data = $request->validate(['approval_status' => 'required|in:approved,rejected']);
        $complaint->update(['approval_status' => $data['approval_status']]);
        $complaint->load('room');

        SystemNotification::create([
            'user_id' => $complaint->reporter_id,
            'title' => 'Status Verifikasi Pengaduan',
            'message' => "Laporan kerusakan '{$complaint->facility_name}' di {$complaint->room->name} telah " . ($data['approval_status'] === 'approved' ? 'disetujui oleh PIC.' : 'ditolak oleh PIC.'),
        ]);
        ActivityLog::record('Verifikasi Kerusakan', auth()->user()->name . ($data['approval_status'] === 'approved' ? ' menyetujui ' : ' menolak ') . "laporan kerusakan '{$complaint->facility_name}' di {$complaint->room->name}.");

        return redirect()->route('complaints.index')->with(
            'success',
            $data['approval_status'] === 'approved' ? 'Pengaduan disetujui. PIC sekarang dapat memperbarui status perbaikan.' : 'Pengaduan ditolak.'
        );
    }

    public function destroy(Complaint $complaint)
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
        $complaint->delete();

        return redirect()->route('complaints.index')->with('success', 'Pengaduan dihapus.');
    }

    private function mayManage(Complaint $complaint): void
    {
        abort_unless(auth()->user()->role?->name === 'pic' && $complaint->room->pic_id === auth()->id(), 403);
    }

    private function canCreateComplaint(): void
    {
        abort_unless(in_array(auth()->user()->role?->name, ['guru', 'organisasi']), 403);
    }

    private function validatedForCreate(Request $request, bool $admin): array
    {
        return $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'event_id' => 'nullable|exists:events,id',
            'facility_name' => 'required|string|max:255',
            'description' => 'required|string',
            'photo' => ($admin ? 'nullable' : 'required').'|image|max:5120',
        ]);
    }

    private function validatedForUpdate(Request $request): array
    {
        return $request->validate([
            'status_repair' => 'required|in:Belum Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki',
            'repair_notes' => 'required_if:status_repair,Sudah Diperbaiki|nullable|string',
        ]);
    }

    public function photo(Complaint $complaint)
    {
        abort_unless($complaint->photo_path && Storage::disk('public')->exists($complaint->photo_path), 404);

        return Storage::disk('public')->response($complaint->photo_path);
    }
}
