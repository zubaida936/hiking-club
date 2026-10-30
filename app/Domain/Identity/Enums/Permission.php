<?php

namespace App\Domain\Identity\Enums;

enum Permission: string
{
    case AccessAdmin = 'admin.access';
    case ManageStaff = 'staff.manage';
    case ManageSettings = 'settings.manage';
    case ManageEvents = 'events.manage';
    case ViewRegistrations = 'registrations.view';
    case ManageRegistrations = 'registrations.manage';
    case ManageAttendance = 'attendance.manage';
    case ManageMembers = 'members.manage';
    case ManageLoyalty = 'loyalty.manage';
    case ReviewApplications = 'applications.review';
    case ManageShop = 'shop.manage';
    case ManageContent = 'content.manage';
    case ManageMedia = 'media.manage';
    // Newsletter subscriber list: personal data, so not part of content editing.
    case ManageSubscribers = 'subscribers.manage';
    // Hand over perks (gift card...) on the trail: guides too, without managing points.
    case HandOverPerks = 'rewards.hand_over';
}
