<?php

use App\Support\Phone;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Staff phones were stored as typed in the CMS ("+961 70 123 456"), while the app login looks numbers up in the
 * members' form ("+96170123456"). Store the existing ones that way too, so staff can log in to the app.
 * A number already used in its normal form by another account is left as it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->whereNotNull('phone')->orderBy('id')->each(function (object $user) {
            $normal = Phone::normalize($user->phone);
            if ($normal === null || $normal === $user->phone || DB::table('users')->where('phone', $normal)->exists()) {
                return;
            }

            DB::table('users')->where('id', $user->id)->update(['phone' => $normal]);
        });
    }

    public function down(): void
    {
        // Nothing to undo: the normal form is the one the app expects.
    }
};
