<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Jejak Audit / Riwayat Aktivitas (Tabel 'activity_logs')
 *
 * Mencatat setiap aksi penting pengguna:
 * - Siapa pelakunya (user_id & IP address).
 * - Jenis aksi (Tambah Ruangan, Edit Jadwal, Konfirmasi Check-Out, dll).
 * - Deskripsi lengkap perubahan data.
 */
class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
        'ip_address',
    ];

    /**
     * Relasi ke User yang melakukan aktivitas.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper praktis untuk mencatat log aktivitas dari mana saja di kode:
     * Contoh: ActivityLog::record('Hapus Ruangan', 'Admin menghapus Lab 1');
     */
    public static function record(string $action, string $description, ?User $user = null): self
    {
        $currentUser = $user ?? auth()->user();

        return self::create([
            'user_id' => $currentUser?->id,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
        ]);
    }
}

