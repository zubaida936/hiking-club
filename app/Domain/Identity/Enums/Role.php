<?php

namespace App\Domain\Identity\Enums;

/**
 * Roles are coarse-grained bundles of permissions. Authorization checks in
 * code must always target a Permission, never a Role, so roles can be
 * re-shaped from the seeder without touching application code.
 */
enum Role: string
{
    case SuperAdmin = 'super-admin';
    case Admin = 'admin';
    case Editor = 'editor';
    case Guide = 'guide';
    case Member = 'member';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'Super Admin',
            self::Admin => 'Admin',
            self::Editor => 'Content Editor',
            self::Guide => 'Guide',
            self::Member => 'Member',
        };
    }

    /**
     * @return list<Permission>
     */
    public function permissions(): array
    {
        return match ($this) {
            // Super admin bypasses every check through Gate::before.
            self::SuperAdmin => Permission::cases(),
            self::Admin => array_values(array_filter(
                Permission::cases(),
                fn (Permission $p) => $p !== Permission::ManageStaff,
            )),
            self::Editor => [
                Permission::AccessAdmin,
                Permission::ManageContent,
                Permission::ManageMedia,
            ],
            self::Guide => [
                Permission::AccessAdmin,
                Permission::ViewRegistrations,
                Permission::ManageAttendance,
                Permission::HandOverPerks,
            ],
            self::Member => [],
        };
    }

    /**
     * Roles that can be assigned to back-office (CMS) users.
     *
     * @return list<self>
     */
    public static function staff(): array
    {
        return [self::SuperAdmin, self::Admin, self::Editor, self::Guide];
    }
}
