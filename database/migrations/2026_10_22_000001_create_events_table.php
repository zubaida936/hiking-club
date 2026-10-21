<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 150);
            $table->string('slug', 170)->unique();
            $table->string('season_tag', 40)->nullable();
            $table->string('location', 120);
            // Local (Lebanon) calendar date of the hike; pickup times are local wall-clock times.
            $table->date('starts_on');
            $table->string('summary', 300)->nullable();
            $table->longText('description')->nullable();

            $table->decimal('distance_km', 5, 1);
            $table->unsignedSmallInteger('elevation_gain_m')->default(0);
            $table->unsignedSmallInteger('elevation_loss_m')->default(0);
            $table->decimal('duration_hours', 3, 1)->nullable();
            // Derived from distance/elevation by DifficultyCalculator on every save.
            $table->unsignedSmallInteger('effort_score');
            $table->unsignedTinyInteger('difficulty_level');

            $table->unsignedInteger('fee_cents')->default(0);
            $table->unsignedSmallInteger('capacity');
            $table->json('included')->nullable();
            $table->json('what_to_bring')->nullable();
            $table->string('cover_image_path')->nullable();
            $table->boolean('is_xtreme')->default(false);

            $table->string('status', 20)->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason', 500)->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'starts_on']);
            $table->index('difficulty_level');
        });

        Schema::create('event_meeting_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->time('pickup_time');
            $table->unsignedTinyInteger('position')->default(0);
            $table->timestamps();

            $table->index(['event_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_meeting_points');
        Schema::dropIfExists('events');
    }
};
