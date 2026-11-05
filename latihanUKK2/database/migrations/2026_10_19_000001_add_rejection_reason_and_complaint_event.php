<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->text('rejection_reason')->nullable();
        });

        Schema::table('complaints', function (Blueprint $table) {
            $table->foreignId('event_id')->nullable()->after('room_id')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('complaints', function (Blueprint $table) {
            $table->dropConstrainedForeignId('event_id');
        });

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('rejection_reason');
        });
    }
};
