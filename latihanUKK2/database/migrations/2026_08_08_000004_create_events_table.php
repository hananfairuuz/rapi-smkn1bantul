<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Tabel 'events' (Peminjaman Ruangan & Jadwal Agenda)
 *
 * Kolom:
 * - user_id   : ID peminjam (terhubung ke tabel users).
 * - room_id   : ID ruangan yang dipinjam (terhubung ke tabel rooms).
 * - title     : Nama kegiatan/acara.
 * - date      : Tanggal pelaksanaan.
 * - start_time: Jam mulai.
 * - end_time  : Jam selesai.
 * - status    : Status persetujuan (pending = menunggu, approved = disetujui, rejected = ditolak).
 * - index     : Index gabungan (room_id, date) untuk mempercepat pencarian bentrok jadwal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('move_reason')->nullable();
            $table->timestamps();
            $table->index(['room_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
