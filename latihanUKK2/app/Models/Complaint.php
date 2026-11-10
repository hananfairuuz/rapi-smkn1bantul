<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Complaint: Representasi tabel 'complaints' (Pengaduan Fasilitas Rusak)
 *
 * Kolom Penting:
 * - room_id: ID Ruangan tempat fasilitas berada (Foreign Key ke tabel rooms)
 * - event_id: ID Agenda terkait jika kerusakan terjadi saat kegiatan (nullable)
 * - reporter_id: ID User pelapor (Guru/Organisasi)
 * - facility_name: Nama fasilitas yang rusak (misal: AC, Proyektor, Kabel HDMI)
 * - description: Penjelasan rinci kerusakan oleh pelapor
 * - photo_path: Jalur foto bukti kerusakan awal
 * - approval_status: Persetujuan awal PIC ('pending', 'approved', 'rejected')
 * - status_repair: Status tindak lanjut ('Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki')
 * - repair_notes: Catatan atau keterangan PIC terkait perbaikan (opsional)
 * - repair_photo_path: Jalur foto bukti fisik barang yang sudah berhasil diperbaiki
 */
class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'room_id',
        'event_id',
        'reporter_id',
        'facility_name',
        'description',
        'photo_path',
        'approval_status',
        'status_repair',
        'repair_notes',
        'repair_photo_path',
    ];

    /**
     * Relasi ke model Room: Setiap pengaduan terjadi pada satu ruangan
     */
    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Relasi ke model User: Setiap pengaduan dilaporkan oleh satu pengguna
     */
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Relasi ke model Event: Pengaduan dapat terhubung ke agenda peminjaman tertentu
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
