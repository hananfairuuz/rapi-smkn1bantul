<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Seeder Data Awal Sistem (Database Seeder)
 *
 * Fungsi: Mengisi data permulaan yang dibutuhkan aplikasi saat baru pertama kali dipasang (deploy):
 * 1. Empat peran pengguna utama (admin, pic, guru, organisasi).
 * 2. Akun default untuk pengujian & demo penguji UKK (password: password123).
 * 3. Ruangan master awal ('Laboratorium Komputer') yang sudah ditetapkan PIC-nya.
 */
class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Jalankan proses pengisian data awal (seeding).
     */
    public function run(): void
    {
        // 1. Buat master data peran (Role)
        $roles = collect([
            'admin' => 'Waka / Admin yang mengelola seluruh sistem.',
            'pic' => 'PIC Ruang yang bertanggung jawab atas fasilitas.',
            'guru' => 'Guru yang dapat mengajukan agenda dan melaporkan kerusakan.',
            'organisasi' => 'Organisasi siswa yang dapat mengajukan agenda dan melaporkan kerusakan.',
        ])->map(fn ($description, $name) => Role::firstOrCreate(['name' => $name], ['description' => $description]));

        // 2. Buat akun-akun pengguna untuk setiap peran (beserta no WhatsApp & role_id)
        $users = [
            'admin' => ['Admin RAPI', 'admin@rapi.test', '628111111111'],
            'pic' => ['PIC Ruang', 'pic@rapi.test', '628222222222'],
            'guru' => ['Guru RAPI', 'guru@rapi.test', '628333333333'],
            'organisasi' => ['Organisasi RAPI', 'organisasi@rapi.test', '628444444444'],
            'hanan' => ['Hanan Musyaffa', 'hananmusyaffa@gmail.com', '628111111111'],
            'danu' => ['Lega Danu Kumara', 'legadanukumara@gmail.com', '6281234567001'],
            'wahid' => ['Wahid Abduraman', 'wahidabduraman@gmail.com', '6281234567002'],
            'heri' => ['Heri Prasetyo', 'heriprasetyo@gmail.com', '6281234567003'],
            'arif' => ['Arif Kurniawan', 'arifkurniawan@gmail.com', '6281234567004'],
        ];
        foreach ($users as $key => $data) {
            $roleKey = in_array($key, ['danu', 'wahid', 'heri', 'arif']) ? 'pic' : ($key === 'hanan' ? 'admin' : $key);
            User::firstOrCreate(
                ['email' => $data[1]],
                [
                    'name' => $data[0],
                    'password' => Hash::make('password123'),
                    'role_id' => $roles[$roleKey]->id,
                    'whatsapp' => $data[2],
                    'email_verified_at' => now(),
                ]
            );
        }

        // 3. Buat master ruangan sekolah & lab komputer beserta penugasan PIC
        $picDanuId = User::where('email', 'legadanukumara@gmail.com')->value('id');
        $picWahidId = User::where('email', 'wahidabduraman@gmail.com')->value('id');
        $picHeriId = User::where('email', 'heriprasetyo@gmail.com')->value('id');
        $picArifId = User::where('email', 'arifkurniawan@gmail.com')->value('id');

        $rooms = [
            // PIC: Heri Prasetyo
            ['name' => 'Lab 1',  'pic_id' => $picHeriId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 1', 'description' => 'Laboratorium Praktik Komputer & Jaringan Dasar'],
            ['name' => 'Lab 5',  'pic_id' => $picHeriId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 2', 'description' => 'Laboratorium Komputer & Rekayasa Perangkat Lunak'],
            ['name' => 'Lab 7',  'pic_id' => $picHeriId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 2', 'description' => 'Laboratorium Desain Komunikasi Visual & Multimedia'],
            ['name' => 'Lab 10', 'pic_id' => $picHeriId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 3', 'description' => 'Laboratorium Pemrograman & Basis Data'],
            ['name' => 'Lab 12', 'pic_id' => $picHeriId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 3', 'description' => 'Laboratorium Perakitan Komputer & Hardware'],
            ['name' => 'Lab 13', 'pic_id' => $picHeriId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 3', 'description' => 'Laboratorium Sistem Informasi & Administrasi'],
            ['name' => 'Lab 18', 'pic_id' => $picHeriId,  'capacity' => 36, 'location' => 'Gedung CBT Lt. 2', 'description' => 'Laboratorium Komputer & Uji Kompetensi Keahlian'],

            // PIC: Lega Danu Kumara
            ['name' => 'Lab 2',  'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 1', 'description' => 'Laboratorium Pemrograman Web & Mobile'],
            ['name' => 'Lab 4',  'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 1', 'description' => 'Laboratorium Praktik Algoritma & Cloud Computing'],
            ['name' => 'Lab 6',  'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 2', 'description' => 'Laboratorium Jaringan Komputer & Cyber Security'],
            ['name' => 'Lab 8',  'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 2', 'description' => 'Laboratorium Database & Pengembangan Aplikasi'],
            ['name' => 'Lab 11', 'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 3', 'description' => 'Laboratorium Animasi & Grafika Digital'],
            ['name' => 'Lab 14', 'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung TI Lt. 3', 'description' => 'Laboratorium Riset & Inovasi Perangkat Lunak'],
            ['name' => 'Lab 19', 'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung CBT Lt. 2', 'description' => 'Laboratorium CBT & Simulasi Ujian Mandiri'],
            ['name' => 'Lab 21', 'pic_id' => $picDanuId,  'capacity' => 36, 'location' => 'Gedung CBT Lt. 3', 'description' => 'Laboratorium Terpadu Komputer & Asesmen Nasional'],

            // PIC: Wahid Abduraman
            ['name' => 'Lab 3',  'pic_id' => $picWahidId, 'capacity' => 36, 'location' => 'Gedung TI Lt. 1', 'description' => 'Laboratorium Pemrograman Dasar & Logika Komputer'],
            ['name' => 'Lab 9',  'pic_id' => $picWahidId, 'capacity' => 36, 'location' => 'Gedung TI Lt. 2', 'description' => 'Laboratorium Jaringan Nirkabel & Mikrotik'],
            ['name' => 'Lab 16', 'pic_id' => $picWahidId, 'capacity' => 36, 'location' => 'Gedung CBT Lt. 1', 'description' => 'Laboratorium Komputerisasi Akuntansi & Administrasi'],
            ['name' => 'Lab 17', 'pic_id' => $picWahidId, 'capacity' => 36, 'location' => 'Gedung CBT Lt. 1', 'description' => 'Laboratorium Uji CBT Terpadu & Simulasi'],
            ['name' => 'Lab 20', 'pic_id' => $picWahidId, 'capacity' => 36, 'location' => 'Gedung CBT Lt. 2', 'description' => 'Laboratorium Sertifikasi Profesi & BNSP'],
            ['name' => 'Lab 22', 'pic_id' => $picWahidId, 'capacity' => 36, 'location' => 'Gedung CBT Lt. 3', 'description' => 'Laboratorium Komputer Terpadu & Server Pusat'],

            // PIC: Arif Kurniawan
            ['name' => 'Ruang Wijaya Kusuma', 'pic_id' => $picArifId, 'capacity' => 144, 'location' => 'Gedung Utama Lt. 1', 'description' => 'Aula serbaguna pertemuan rapat dinas, seminar besar, dan presentasi sekolah'],
        ];

        foreach ($rooms as $room) {
            Room::firstOrCreate(['name' => $room['name']], $room);
        }
    }
}
