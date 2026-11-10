<?php

namespace App\Http\Controllers;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Room;
use App\Models\SystemNotification;
use Carbon\CarbonImmutable;

/**
 * Controller Dashboard Utama
 *
 * Menghitung dan menyajikan data ringkasan (metrik) untuk halaman beranda:
 * - Total ruangan & jumlah ruangan yang terpakai hari ini
 * - Jumlah kegiatan hari ini & kegiatan yang sedang berlangsung detik ini
 * - Jumlah fasilitas/barang yang masih belum diperbaiki
 * - 5 jadwal kegiatan terdekat
 * - Notifikasi sistem yang belum dibaca oleh user yang login
 */
class DashboardController extends Controller
{
    /**
     * Menampilkan halaman dashboard beserta metrik statistiknya.
     */
    public function index()
    {
        $now = now('Asia/Jakarta');
        // Ambil query semua event yang jatuh pada hari ini
        $todayEvents = $this->eventsOnDate($now->toDateString());

        // Hitung berapa event yang benar-benar SEDANG BERLANGSUNG saat detik ini
        $ongoingEvents = (clone $todayEvents)->get()
            ->filter(fn (Event $event) => $event->endAt()->gt($now)
                && CarbonImmutable::parse($event->date->toDateString().' '.$event->start_time, 'Asia/Jakarta')->lte($now))
            ->count();

        return view('dashboard', [
            // Total seluruh ruangan yang terdaftar di sekolah
            'totalRooms' => Room::count(),
            // Jumlah ruangan berbeda yang statusnya pending atau disetujui untuk hari ini
            'roomsInUse' => (clone $todayEvents)->whereIn('status', ['pending', 'approved'])->distinct('room_id')->count('room_id'),
            // Total permohonan / peminjaman untuk hari ini
            'todayEvents' => $todayEvents->count(),
            // Jadwal yang detik ini sedang berjalan
            'ongoingEvents' => $ongoingEvents,
            // Pengaduan barang/sarpras rusak yang butuh penanganan PIC
            'pendingComplaints' => Complaint::where('status_repair', 'Belum Diperbaiki')->count(),
            // 5 jadwal terbaru yang akan datang
            'recentEvents' => Event::with(['room', 'user'])->orderBy('date')->orderBy('start_time')->take(5)->get(),
            // Notifikasi sistem yang belum dibaca khusus milik user yang sedang login
            'notifications' => SystemNotification::where('user_id', auth()->id())->whereNull('read_at')->latest()->get(),
        ]);
    }

    /**
     * Query Cerdas: Mencari jadwal yang aktif pada tanggal tertentu ($date).
     * Memperhitungkan 3 kondisi:
     * 1. Jadwal rentang hari (ada end_date).
     * 2. Jadwal 1 hari penuh biasa.
     * 3. Jadwal yang dimulai kemarin malam dan berakhir subuh/dini hari ini (lintas tengah malam).
     */
    private function eventsOnDate(string $date)
    {
        $previousDate = CarbonImmutable::parse($date)->subDay()->toDateString();

        return Event::whereDate('date', '<=', $date)
            ->where(function ($query) use ($date, $previousDate) {
                // Kasus 1: Jadwal rentang tanggal multi-hari
                $query->whereDate('end_date', '>=', $date)
                    // Kasus 2: Jadwal satu hari pada tanggal ini
                    ->orWhere(function ($query) use ($date) {
                        $query->whereNull('end_date')->whereDate('date', '>=', $date);
                    })
                    // Kasus 3: Jadwal dimulai kemarin malam tetapi melewati jam 00:00 (lintas hari)
                    ->orWhere(function ($query) use ($previousDate) {
                        $query->whereNull('end_date')
                            ->whereColumn('end_time', '<', 'start_time')
                            ->whereDate('date', $previousDate);
                    });
            });
    }
}
