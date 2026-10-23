<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One hiker per phone number: owns bookings and points. A login account
        // (users) is linked later by the account activation feature.
        Schema::create('hikers', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->string('name', 120);
            $table->string('email', 190)->nullable();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('event_registrations', function (Blueprint $table) {
            $table->foreignId('hiker_id')->nullable()->after('event_id')->constrained()->nullOnDelete();
            // The member who invited this hiker (bonus once the hiker completes a first hike).
            $table->foreignId('referred_by_hiker_id')->nullable()->after('hiker_id')->constrained('hikers')->nullOnDelete();
        });

        // Append-only: corrections are new rows (negative to reverse), never edits.
        Schema::create('point_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiker_id')->constrained()->cascadeOnDelete();
            $table->foreignId('event_registration_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 30);
            $table->integer('points');
            $table->string('description', 200);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['event_registration_id', 'type']);
        });

        // Existing bookings: one hiker per phone, named after their latest booking.
        DB::table('event_registrations')->orderByDesc('id')->get(['id', 'phone', 'name', 'email'])
            ->unique('phone')
            ->each(fn ($r) => DB::table('hikers')->insert([
                'phone' => $r->phone, 'name' => $r->name, 'email' => $r->email, 'created_at' => now(), 'updated_at' => now(),
            ]));

        DB::table('hikers')->get(['id', 'phone'])
            ->each(fn ($h) => DB::table('event_registrations')->where('phone', $h->phone)->update(['hiker_id' => $h->id]));
    }

    public function down(): void
    {
        Schema::dropIfExists('point_transactions');
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('referred_by_hiker_id');
            $table->dropConstrainedForeignId('hiker_id');
        });
        Schema::dropIfExists('hikers');
    }
};
