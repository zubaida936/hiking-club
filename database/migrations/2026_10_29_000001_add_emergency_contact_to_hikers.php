<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The member's own emergency contact (My account / app profile), used to fill in the booking forms.
 * Each booking still keeps its own copy, as typed for that hike.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hikers', function (Blueprint $table) {
            $table->string('emergency_contact_name', 120)->nullable()->after('email');
            $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
        });
    }

    public function down(): void
    {
        Schema::table('hikers', function (Blueprint $table) {
            $table->dropColumn(['emergency_contact_name', 'emergency_contact_phone']);
        });
    }
};
