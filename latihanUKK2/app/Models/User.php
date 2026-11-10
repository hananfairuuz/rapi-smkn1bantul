<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model Data Pengguna (Tabel 'users')
 *
 * Kolom penting:
 * - name     : Nama lengkap pengguna.
 * - email    : Alamat email unik (login).
 * - password : Hash kata sandi akun (terenkripsi Bcrypt).
 * - role_id  : ID peran pengguna (Foreign Key ke tabel roles).
 * - whatsapp : Nomor WhatsApp aktif untuk notifikasi otomatis.
 * - google_id: ID akun Google jika login lewat Single Sign-On (OAuth).
 */
#[Fillable(['name', 'email', 'password', 'role_id', 'whatsapp', 'google_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Relasi: Satu user memiliki satu peran hak akses (admin/pic/guru/organisasi).
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relasi: Satu user bisa mengajukan banyak agenda peminjaman ruangan.
     */
    public function events()
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Relasi: Satu user bisa mengirimkan banyak laporan kerusakan fasilitas.
     */
    public function complaints()
    {
        return $this->hasMany(Complaint::class, 'reporter_id');
    }

    /**
     * Relasi: Khusus role PIC, seorang user bisa bertanggung jawab atas banyak ruangan.
     */
    public function roomsAsPic()
    {
        return $this->hasMany(Room::class, 'pic_id');
    }

    /**
     * Relasi: Notifikasi sistem yang ditujukan kepada user ini.
     */
    public function systemNotifications()
    {
        return $this->hasMany(SystemNotification::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
