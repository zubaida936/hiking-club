<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            // Set by organizers in the CMS; how hikers pay is decided in the "Event payments" task.
            $table->timestamp('paid_at')->nullable()->after('level_acknowledged_at');
            // attended | no_show, marked from the hike day on. Attended hikes count for points.
            $table->string('attendance', 20)->nullable()->after('paid_at');
            $table->timestamp('attendance_marked_at')->nullable()->after('attendance');
            $table->timestamp('cancelled_at')->nullable()->after('attendance_marked_at');
            $table->string('cancellation_reason', 500)->nullable()->after('cancelled_at');
            // Last staff member who changed the booking (null = only the hiker so far).
            $table->foreignId('updated_by')->nullable()->after('cancellation_reason')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('event_registrations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('updated_by');
            $table->dropColumn(['paid_at', 'attendance', 'attendance_marked_at', 'cancelled_at', 'cancellation_reason']);
        });
    }
};
