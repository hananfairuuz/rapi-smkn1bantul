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

        if ($notification->event_id) {
            return redirect()->route('events.index');
        }

        $titleLower = strtolower($notification->title . ' ' . $notification->message);
        if (str_contains($titleLower, 'fasilitas') || str_contains($titleLower, 'kerusakan') || str_contains($titleLower, 'pengaduan')) {
            return redirect()->route('complaints.index');
        }

        return redirect()->back();
    }
}

