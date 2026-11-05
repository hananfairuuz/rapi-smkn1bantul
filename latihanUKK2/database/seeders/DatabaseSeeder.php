<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = collect([
            'admin' => 'Waka / Admin yang mengelola seluruh sistem.',
            'pic' => 'PIC Ruang yang bertanggung jawab atas fasilitas.',
            'guru' => 'Guru yang dapat mengajukan agenda dan melaporkan kerusakan.',
            'organisasi' => 'Organisasi siswa yang dapat mengajukan agenda dan melaporkan kerusakan.',
        ])->map(fn ($description, $name) => Role::firstOrCreate(['name' => $name], ['description' => $description]));

        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $users = ['admin' => ['Admin RAPI', 'admin@rapi.test', '628111111111'], 'pic' => ['PIC Ruang', 'pic@rapi.test', '628222222222'], 'guru' => ['Guru RAPI', 'guru@rapi.test', '628333333333'], 'organisasi' => ['Organisasi RAPI', 'organisasi@rapi.test', '628444444444']];
        foreach ($users as $role => $data) {
            User::firstOrCreate(['email' => $data[1]], ['name' => $data[0], 'password' => Hash::make('password123'), 'role_id' => $roles[$role]->id, 'whatsapp' => $data[2]]);
        }
        Room::firstOrCreate(['name' => 'Laboratorium Komputer'], ['location' => 'Gedung A Lt. 2', 'description' => 'Ruang praktik komputer', 'pic_id' => User::where('email', 'pic@rapi.test')->value('id')]);
    }
}
