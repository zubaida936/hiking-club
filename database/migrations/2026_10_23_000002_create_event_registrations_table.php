<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Guest bookings for an event (member accounts come later).
        Schema::create('event_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            // Short code hikers quote on WhatsApp / at the bus, e.g. "K7F3Q2MZ".
            $table->char('reference', 8)->unique();
            // Secret for the hiker's confirmation page link.
            $table->char('token', 64)->unique();
            $table->string('status', 20);

            $table->string('name', 120);
            // Normalized to "+" and digits (App\Support\Phone).
            $table->string('phone', 20);
            $table->string('email', 190)->nullable();
            // Copied from the chosen meeting point: the CMS recreates meeting points on every save.
            $table->string('pickup_point', 120)->nullable();
            $table->time('pickup_time')->nullable();
            $table->string('emergency_contact_name', 120);
            $table->string('emergency_contact_phone', 20);
            $table->string('notes', 500)->nullable();

            $table->timestamp('terms_accepted_at');
            // Set when the hiker confirmed they meet a level 3+ hike's requirements.
            $table->timestamp('level_acknowledged_at')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_registrations');
    }
};
