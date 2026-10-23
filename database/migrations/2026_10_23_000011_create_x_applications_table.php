<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Applications to join X Events (form fields from ClickUp "X Events application form").
        Schema::create('x_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiker_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->index();

            // About you
            $table->string('name', 120);
            $table->string('phone', 20);
            $table->string('email', 190)->nullable();
            $table->unsignedTinyInteger('age');
            $table->string('city', 80);
            $table->string('guardian_name', 120)->nullable();
            $table->string('guardian_phone', 20)->nullable();

            // Experience
            $table->string('hike_frequency', 20);
            $table->string('hardest_route', 150);
            $table->date('hardest_date')->nullable();
            $table->decimal('hardest_km', 5, 1);
            $table->unsignedSmallInteger('hardest_elevation_m');
            $table->decimal('hardest_duration_hours', 4, 1);
            $table->decimal('longest_km', 5, 1);
            $table->unsignedSmallInteger('max_ascent_m');
            $table->string('pace', 20);
            $table->boolean('steady_pace');
            $table->string('main_challenge', 1000);
            $table->string('track_link', 255)->nullable();
            $table->json('experience')->nullable();
            $table->json('gear')->nullable();
            $table->string('limitations', 1000)->nullable();
            $table->timestamp('consent_at');

            // Review
            $table->text('internal_note')->nullable();
            $table->string('decision_note', 500)->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('access_sent_at')->nullable();
            $table->timestamps();
        });

        Schema::table('hikers', function (Blueprint $table) {
            // Approved for X Events (persists across events until revoked).
            $table->timestamp('x_approved_at')->nullable()->after('show_on_leaderboard');
        });
    }

    public function down(): void
    {
        Schema::table('hikers', function (Blueprint $table) {
            $table->dropColumn('x_approved_at');
        });
        Schema::dropIfExists('x_applications');
    }
};
