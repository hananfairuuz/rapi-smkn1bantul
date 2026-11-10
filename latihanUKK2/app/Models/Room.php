<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Master Ruangan (Tabel 'rooms')
 *
 * Kolom:
 * - name       : Nama fasilitas ruangan (misal: Lab RPL, Aula, Ruang Sidang).
 * - location   : Lokasi fisik gedung & lantai.
 * - capacity   : Daya tampung ruangan (orang).
 * - description: Fasilitas pendukung di dalam ruangan.
 * - pic_id     : ID pengguna ber-role 'pic' penanggung jawab ruangan.
 */
class Room extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'location', 'capacity', 'description', 'pic_id'];

    protected $casts = [
        'capacity' => 'integer',
    ];

    /**
     * Relasi: Setiap ruangan dipimpin/diawasi oleh satu PIC Ruang (User).
     */
    public function pic()
    {
        return $this->belongsTo(User::class, 'pic_id');
    }

    /**
     * Relasi: Satu ruangan memiliki banyak jadwal permohonan agenda (One-to-Many).
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relasi: Satu ruangan memiliki banyak riwayat pengaduan kerusakan (One-to-Many).
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }
}
