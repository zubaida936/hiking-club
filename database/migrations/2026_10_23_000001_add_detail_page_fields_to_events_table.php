<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('photo_credit', 120)->nullable()->after('cover_image_path');
            // One step per line, optionally starting with a time ("07:30 Start of the hike").
            $table->json('itinerary')->nullable()->after('what_to_bring');
            $table->string('gpx_path')->nullable()->after('photo_credit');
            // Simplified [lat, lng] track read from the GPX file on save, drawn on the map.
            $table->json('route_points')->nullable()->after('gpx_path');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['photo_credit', 'itinerary', 'gpx_path', 'route_points']);
        });
    }
};
