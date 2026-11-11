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

/**
 * Controller untuk mengelola seluruh siklus Pengaduan Fasilitas Rusak:
 * 1. Pelaporan awal oleh Guru/Organisasi (wajib foto kerusakan maks 10MB)
 * 2. Verifikasi/Persetujuan oleh PIC Ruangan
 * 3. Pembaruan status perbaikan & upload foto bukti selesai perbaikan oleh PIC
 * 4. Peringatan otomatis eskalasi WhatsApp untuk Admin jika > 24 jam belum selesai
 */
class ComplaintController extends Controller
{
    /**
     * Menampilkan daftar pengaduan berdasarkan peran (Role-based Filter)
     * - PIC hanya melihat pengaduan pada ruangan yang ditugaskan kepadanya
     * - Guru/Organisasi hanya melihat pengaduan yang mereka laporkan sendiri
     * - Admin melihat seluruh pengaduan sekolah
     */
    public function index()
    {
        $query = Complaint::with(['room.pic', 'reporter', 'event']);
        $role = auth()->user()->role?->name;

        if ($role === 'pic') {
            $query->whereHas('room', fn ($q) => $q->where('pic_id', auth()->id()));
        } elseif (in_array($role, ['guru', 'organisasi'])) {
            $query->where('reporter_id', auth()->id());
        }

        return view('complaints.index', [
            'complaints' => $query->orderByDesc('created_at')->orderByDesc('id')->get(),
            'whatsappDueAt' => now()->subDay(), // Batas waktu 24 jam untuk tombol eskalasi WhatsApp admin
        ]);
    }

    /**
     * Menampilkan formulir pembuatan laporan pengaduan baru
     */
    public function create(Request $request)
    {
        $event = null;
        $isAdmin = auth()->user()->role?->name === 'admin';

        // Jika pengaduan dibuat terkait agenda peminjaman tertentu
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

    /**
     * Menyimpan laporan pengaduan kerusakan fasilitas baru ke database
     */
    public function store(Request $request)
    {
        $isAdmin = auth()->user()->role?->name === 'admin';
        if (! $isAdmin) {
            $this->canCreateComplaint();
        }

        // 1. Validasi input: foto wajib maks 10MB (kecuali admin opsional)
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

        $data['approval_status'] = 'pending'; // Status awal menunggu review PIC
        $data['reporter_id'] = auth()->id();

        // 2. Simpan & kompres foto kerusakan ke kualitas 70% via Service
        if ($request->hasFile('photo')) {
            $data['photo_path'] = app(ComplaintPhotoStorage::class)->store($request->file('photo'));
        }
        unset($data['photo']);

        // 3. Simpan data pengaduan & catat di riwayat aktivitas (Activity Log)
        $complaint = Complaint::create($data);
        $complaint->load('room');
        ActivityLog::record('Laporan Kerusakan', auth()->user()->name . " melaporkan fasilitas rusak '{$complaint->facility_name}' di ruangan {$complaint->room->name}.");
        SystemNotification::notifyComplaintSubmitted($complaint, auth()->user());

        return redirect()->route('complaints.index')->with('success', 'Pengaduan kerusakan berhasil dicatat dan menunggu persetujuan PIC.');
    }

    /**
     * Menampilkan form edit tindak lanjut perbaikan untuk PIC
     */
    public function edit(Complaint $complaint)
    {
        $this->mayManage($complaint); // Cek hak akses PIC ruangan
        abort_unless($complaint->approval_status === 'approved', 403, 'Pengaduan harus disetujui sebelum status perbaikan diperbarui.');
        $complaint->load('room.pic');

        return view('complaints.form', ['complaint' => $complaint, 'rooms' => collect(), 'event' => null]);
    }

    /**
     * Memperbarui status perbaikan fasilitas oleh PIC
     * - Jika status 'Sudah Diperbaiki', PIC WAJIB melampirkan foto bukti perbaikan
     * - Catatan perbaikan bersifat opsional
     */
    public function update(Request $request, Complaint $complaint)
    {
        $this->mayManage($complaint);
        abort_unless($complaint->approval_status === 'approved', 403, 'Pengaduan harus disetujui PIC sebelum status perbaikan diperbarui.');

        // 1. Validasi status perbaikan dan foto bukti
        $data = $this->validatedForUpdate($request, $complaint);

        // 2. Jika ada unggahan foto bukti perbaikan baru, kompresi 70% dan simpan
        if ($request->hasFile('repair_photo')) {
            // Hapus file foto perbaikan lama jika sebelumnya sudah ada
            if ($complaint->repair_photo_path && Storage::disk('public')->exists($complaint->repair_photo_path)) {
                Storage::disk('public')->delete($complaint->repair_photo_path);
            }
            $data['repair_photo_path'] = app(ComplaintPhotoStorage::class)->store($request->file('repair_photo'), 'repair_');
        }
        unset($data['repair_photo']);

        // 3. Simpan perubahan ke database
        $complaint->update($data);
        $complaint->load('room');

        // 4. Kirim notifikasi sistem otomatis ke akun pelapor & Admin
        SystemNotification::notifyComplaintRepairUpdated($complaint, auth()->user());

        // 5. Catat jejak audit log
        ActivityLog::record('Tindak Lanjut Perbaikan', auth()->user()->name . " memperbarui status perbaikan '{$complaint->facility_name}' di {$complaint->room->name} menjadi '{$complaint->status_repair}'.");

        return redirect()->route('complaints.index')->with('success', 'Status pengaduan diperbarui.');
    }

    /**
     * Memproses persetujuan (Approve) atau penolakan (Reject) pengaduan oleh PIC
     */
    public function review(Request $request, Complaint $complaint)
    {
        $this->mayManage($complaint);
        abort_unless($complaint->approval_status === 'pending', 403, 'Pengaduan ini sudah diputuskan.');

        $data = $request->validate(['approval_status' => 'required|in:approved,rejected']);
        $complaint->update(['approval_status' => $data['approval_status']]);
        $complaint->load('room');

        // Kirim notifikasi status verifikasi ke pelapor & Admin
        SystemNotification::notifyComplaintReviewed($complaint, auth()->user(), $data['approval_status']);

        ActivityLog::record('Verifikasi Kerusakan', auth()->user()->name . ($data['approval_status'] === 'approved' ? ' menyetujui ' : ' menolak ') . "laporan kerusakan '{$complaint->facility_name}' di {$complaint->room->name}.");

        return redirect()->route('complaints.index')->with(
            'success',
            $data['approval_status'] === 'approved' ? 'Pengaduan disetujui. PIC sekarang dapat memperbarui status perbaikan.' : 'Pengaduan ditolak.'
        );
    }

    /**
     * Menghapus laporan pengaduan (Hanya Admin)
     */
    public function destroy(Complaint $complaint)
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);
        $complaint->delete();

        return redirect()->route('complaints.index')->with('success', 'Pengaduan dihapus.');
    }

    /**
     * Helper: Memastikan pengguna adalah PIC dari ruangan yang bersangkutan
     */
    private function mayManage(Complaint $complaint): void
    {
        abort_unless(auth()->user()->role?->name === 'pic' && $complaint->room->pic_id === auth()->id(), 403);
    }

    /**
     * Helper: Memastikan hanya Guru atau Organisasi yang dapat membuat pengaduan
     */
    private function canCreateComplaint(): void
    {
        abort_unless(in_array(auth()->user()->role?->name, ['guru', 'organisasi']), 403);
    }

    /**
     * Aturan validasi pembuatan pengaduan baru (Foto maks 10MB)
     */
    private function validatedForCreate(Request $request, bool $admin): array
    {
        return $request->validate([
            'room_id' => 'nullable|exists:rooms,id',
            'event_id' => 'nullable|exists:events,id',
            'facility_name' => 'required|string|max:255',
            'description' => 'required|string',
            'photo' => ($admin ? 'nullable' : 'required').'|image|max:10240', // Maksimal 10 MB (10.240 KB)
        ]);
    }

    /**
     * Aturan validasi pembaruan status perbaikan PIC:
     * - Jika status 'Sudah Diperbaiki' dan belum ada foto sebelumnya: Foto bukti WAJIB disertakan.
     * - Catatan perbaikan bersifat opsional (nullable).
     */
    private function validatedForUpdate(Request $request, Complaint $complaint): array
    {
        return $request->validate([
            'status_repair' => 'required|in:Belum Diperbaiki,Menunggu Spare Part,Sudah Diperbaiki',
            'repair_notes' => 'nullable|string', // Catatan bersifat opsional
            'repair_photo' => ($request->status_repair === 'Sudah Diperbaiki' && !$complaint->repair_photo_path ? 'required' : 'nullable') . '|image|max:10240', // Maks 10 MB
        ]);
    }

    /**
     * Menampilkan / mengunduh foto bukti kerusakan awal
     */
    public function photo(Complaint $complaint)
    {
        abort_unless($complaint->photo_path && Storage::disk('public')->exists($complaint->photo_path), 404);

        return Storage::disk('public')->response($complaint->photo_path);
    }

    /**
     * Menampilkan / mengunduh foto bukti barang yang sudah selesai diperbaiki
     */
    public function repairPhoto(Complaint $complaint)
    {
        abort_unless($complaint->repair_photo_path && Storage::disk('public')->exists($complaint->repair_photo_path), 404);

        return Storage::disk('public')->response($complaint->repair_photo_path);
    }
}
