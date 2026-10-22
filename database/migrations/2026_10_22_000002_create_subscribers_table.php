<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Next Destination Alerts". A subscriber has an email, a phone, or both.
        Schema::create('subscribers', function (Blueprint $table) {
            $table->id();
            $table->string('email', 190)->nullable()->unique();
            // Normalized to "+" and digits. Kept for manual WhatsApp broadcasts until an SMS/WhatsApp provider exists.
            $table->string('phone', 20)->nullable()->unique();
            // Secret for the confirm and unsubscribe links.
            $table->char('token', 64)->unique();
            // Double opt-in: emails are only sent once the address is confirmed.
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('unsubscribed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscribers');
    }
};
