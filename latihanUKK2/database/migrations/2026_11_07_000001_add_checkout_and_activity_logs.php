<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->timestamp('returned_at')->nullable()->after('status');
            $table->string('checkout_condition')->nullable()->after('returned_at');
            $table->text('checkout_notes')->nullable()->after('checkout_condition');
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->text('description');
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index('created_at');
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');

        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['returned_at', 'checkout_condition', 'checkout_notes']);
        });
    }
};

