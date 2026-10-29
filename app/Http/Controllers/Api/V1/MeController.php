<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\MemberResource;
use App\Models\User;
use Illuminate\Http\Request;

class MeController extends Controller
{
    /**
     * The logged-in member.
     *
     * With their points and rank when they have a hiker profile.
     */
    public function __invoke(Request $request): MemberResource
    {
        /** @var User $user */
        $user = $request->user();

        return MemberResource::forUser($user);
    }
}
