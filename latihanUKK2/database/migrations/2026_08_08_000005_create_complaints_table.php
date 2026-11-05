<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

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
