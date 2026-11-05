<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('borrower_name')->nullable()->after('title');
            $table->string('borrower_whatsapp', 20)->nullable()->after('borrower_name');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['borrower_name', 'borrower_whatsapp']);
        });
    }
};
