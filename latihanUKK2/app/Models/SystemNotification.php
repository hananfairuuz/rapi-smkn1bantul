<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Notifikasi Sistem (Tabel 'system_notifications')
 *
 * Menampilkan pesan peringatan/lonceng untuk user:
 * - title   : Judul notifikasi.
 * - message : Isi pesan.
 * - read_at : Tanggal/jam notifikasi dibaca (null jika belum dibaca).
 */
class SystemNotification extends Model
{
    protected $fillable = ['user_id', 'event_id', 'title', 'message', 'read_at'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    /**
     * Relasi ke User penerima notifikasi.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi opsional ke agenda yang terkait dengan notifikasi.
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
