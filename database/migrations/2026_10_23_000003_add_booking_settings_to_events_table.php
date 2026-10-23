<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // UTC. Null = bookings close at midnight (Lebanon time) before the hike day.
            $table->timestamp('registration_closes_at')->nullable()->after('capacity');
            // When full, visitors can join a waitlist and move up when seats free up.
            $table->boolean('waitlist_enabled')->default(true)->after('registration_closes_at');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['registration_closes_at', 'waitlist_enabled']);
        });
    }
};
