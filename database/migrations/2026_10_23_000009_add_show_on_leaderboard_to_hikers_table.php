<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hikers', function (Blueprint $table) {
            // Members can hide themselves from the public leaderboard (My account).
            $table->boolean('show_on_leaderboard')->default(true)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('hikers', function (Blueprint $table) {
            $table->dropColumn('show_on_leaderboard');
        });
    }
};
