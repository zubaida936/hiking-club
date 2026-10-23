<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            // whish | omt | cash | online, chosen by the hiker when booking a paid hike.
            $table->string('payment_method', 20)->nullable()->after('paid_at');
            // Transaction number of a Whish / OMT transfer, as reported by the hiker.
            $table->string('payment_reference', 60)->nullable()->after('payment_method');
        });

        // Online payment attempts (one per "Pay now"); the gateway confirms them.
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_registration_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 30);
            // Our id sent to the gateway, used in return URLs and webhooks.
            $table->uuid('external_id')->unique();
            // The gateway's own transaction id, once known.
            $table->string('gateway_reference')->nullable();
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3);
            $table->string('status', 20);
            $table->text('checkout_url')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['event_registration_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'payment_reference']);
        });
    }
};
