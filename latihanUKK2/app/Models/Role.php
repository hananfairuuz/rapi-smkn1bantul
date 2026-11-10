<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Model Master Hak Akses / Peran (Tabel 'roles')
 *
 * Nilai peran:
 * - 'admin'     : Pengelola utama seluruh sistem & sarpras.
 * - 'pic'       : Penanggung jawab ruangan & verifikator perbaikan.
 * - 'guru'      : Tenaga pendidik (peminjam ruangan & pelapor kerusakan).
 * - 'organisasi': Organisasi siswa (OSIS, MPK, Ekstrakurikuler).
 */
class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description'];

    /**
     * Relasi: Satu peran dimiliki oleh banyak akun pengguna (One-to-Many).
     */
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
