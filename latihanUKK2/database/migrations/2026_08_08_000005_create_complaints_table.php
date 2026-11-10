<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Tabel 'complaints' (Pengaduan Fasilitas Rusak)
 *
 * Kolom:
 * - room_id      : Ruangan tempat barang rusak berada (terhubung ke rooms.id).
 * - reporter_id  : Pengguna yang melaporkan kerusakan (terhubung ke users.id).
 * - facility_name: Nama sarpras yang rusak (misal: Proyektor Epson, AC Sharp).
 * - description  : Penjelasan detail kerusakan fisik.
 * - photo_path   : Path foto barang rusak awal saat pertama kali dilaporkan.
 * - status_repair: Status fisik perbaikan (Belum Diperbaiki, Menunggu Spare Part, Sudah Diperbaiki).
 * - repair_notes : Catatan teknis dari PIC Ruangan saat melakukan perbaikan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('facility_name');
            $table->text('description');
            $table->string('photo_path')->nullable();
            $table->enum('status_repair', ['Belum Diperbaiki', 'Menunggu Spare Part', 'Sudah Diperbaiki'])->default('Belum Diperbaiki');
            $table->text('repair_notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
