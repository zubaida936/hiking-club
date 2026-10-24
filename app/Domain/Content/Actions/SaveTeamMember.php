<?php

namespace App\Domain\Content\Actions;

use App\Domain\Content\Data\TeamMemberData;
use App\Domain\Content\SingleImage;
use App\Models\TeamMember;

final class SaveTeamMember
{
    public function handle(TeamMemberData $data, ?TeamMember $member = null): TeamMember
    {
        $member ??= new TeamMember;
        $photo = new SingleImage('team', 800);

        $member->fill([
            'name' => $data->name,
            'role' => $data->role,
            'bio' => $data->bio,
            'photo_path' => $photo->resolve($member->photo_path, $data->photo, $data->removePhoto),
            'position' => $data->position,
            'is_active' => $data->isActive,
        ])->save();

        $photo->deleteReplaced();

        return $member;
    }
}
