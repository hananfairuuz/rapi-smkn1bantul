<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use Illuminate\Http\Request;

/**
 * Controller Log Aktivitas Sistem (Audit Trail)
 *
 * Khusus Admin: Memantau jejak audit semua aktivitas pengguna (tambah ruangan, edit jadwal, verifikasi, dsb).
 */
class ActivityLogController extends Controller
{
    /**
     * Menampilkan riwayat log aktivitas beserta pencarian & paginasi 25 baris per halaman.
     */
    public function index(Request $request)
    {
        abort_unless(auth()->user()->role?->name === 'admin', 403);

        $search = trim((string) $request->get('search', ''));
        $query = ActivityLog::with('user.role')->latest();

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('action', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        return view('activity-logs.index', [
            'logs' => $query->paginate(25)->withQueryString(),
            'search' => $search,
        ]);
    }
}

