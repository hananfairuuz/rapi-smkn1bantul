<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migrasi Tabel 'api_tokens'
 *
 * Fungsi: Menyimpan token autentikasi (Bearer Token) untuk aplikasi mobile Flutter.
 * - user_id     : Pemilik token (terhubung ke tabel users).
 * - token       : Hash SHA-256 dari token acak 80 karakter (aman, tidak plaintext).
 * - expires_at  : Batas kedaluwarsa token (30 hari setelah login).
 * - last_used_at: Mencatat kapan aplikasi mobile terakhir kali mengakses API.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Hapus token otomatis jika user dihapus
            $table->string('name')->default('mobile');
            $table->string('token', 64)->unique(); // Hash SHA-256 sepanjang 64 karakter heksadesimal
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at'); // Masa aktif token (30 hari)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
