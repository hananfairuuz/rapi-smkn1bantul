<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Tabel 'rooms' (Master Ruangan Sekolah)
 *
 * Kolom:
 * - name       : Nama ruangan (Lab Komputer, Aula, Ruang Rapat).
 * - location   : Lokasi gedung/lantai ruangan.
 * - description: Keterangan fasilitas di dalam ruangan.
 * - pic_id     : ID user yang bertugas sebagai PIC ruangan (Foreign Key ke users.id).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rooms', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->foreignId('pic_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rooms');
    }
};
