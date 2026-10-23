<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Messages organizers send to everyone booked on a hike (weather, route or date changes).
        Schema::create('event_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->text('message');
            $table->unsignedInteger('emailed_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            // The "see you tomorrow" reminder went out (reset when the hike's date changes).
            $table->timestamp('reminder_sent_at')->nullable()->after('refunded_at');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('reminder_sent_at');
        });
        Schema::dropIfExists('event_updates');
    }
};
