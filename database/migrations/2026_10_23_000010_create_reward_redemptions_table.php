<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Each use of a rank reward (config/rewards.php): on a booking, as bonus points, or a perk handed over.
        Schema::create('reward_redemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiker_id')->constrained()->cascadeOnDelete();
            $table->string('reward', 50);
            $table->foreignId('event_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('discount_cents')->default(0);
            $table->string('note', 200)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            // Set when the booking is cancelled: the use is given back.
            $table->timestamp('released_at')->nullable();
            $table->timestamps();

            $table->index(['hiker_id', 'reward']);
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            // What this booking costs after a reward discount; null = the event's fee.
            $table->unsignedInteger('amount_due_cents')->nullable()->after('notes');
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropColumn('amount_due_cents');
        });
        Schema::dropIfExists('reward_redemptions');
    }
};
