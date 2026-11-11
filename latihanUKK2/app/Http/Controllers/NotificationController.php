<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Controller Notifikasi Sistem (In-App Notifications)
 *
 * Mengelola tanda baca lonceng notifikasi (tandai satu dibaca, tandai semua dibaca, dan klik buka link aduan/agenda).
 */
class NotificationController extends Controller
{
    /**
     * Menandai satu notifikasi tertentu sebagai telah dibaca.
     */
    public function markAsRead(Request $request, SystemNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        $notification->update(['read_at' => now('Asia/Jakarta')]);

        return redirect()->back()->with('success', 'Notifikasi telah ditandai sudah dibaca.');
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        SystemNotification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now('Asia/Jakarta')]);

        return redirect()->back()->with('success', 'Semua notifikasi telah ditandai sudah dibaca.');
    }

    public function open(Request $request, SystemNotification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now('Asia/Jakarta')]);
        }

        $role = auth()->user()->role?->name;
        $titleLower = strtolower($notification->title . ' ' . $notification->message);

        if (str_contains($titleLower, 'fasilitas')
            || str_contains($titleLower, 'kerusakan')
            || str_contains($titleLower, 'pengaduan')
            || str_contains($titleLower, 'perbaikan')) {
            return redirect()->route('complaints.index');
        }

        if (str_contains($titleLower, 'penugasan pic') || str_contains($titleLower, 'pic ruangan')) {
            return redirect()->route('rooms.index');
        }

        if ($notification->event_id
            || str_contains($titleLower, 'agenda')
            || str_contains($titleLower, 'peminjaman')
            || str_contains($titleLower, 'jadwal')
            || str_contains($titleLower, 'dikembalikan')) {
            return in_array($role, ['admin', 'guru', 'organisasi'], true)
                ? redirect()->route('events.index')
                : redirect()->route('calendar');
        }

        return redirect()->route('dashboard');
    }
}

