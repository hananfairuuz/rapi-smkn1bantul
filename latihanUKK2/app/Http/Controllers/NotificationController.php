<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
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
}

