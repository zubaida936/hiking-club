<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Members\Actions\SetAccountActive;
use App\Domain\Members\Exceptions\MemberException;
use App\Domain\Members\MemberLinks;
use App\Domain\Rewards\RewardCatalog;
use App\Http\Controllers\Controller;
use App\Models\Hiker;
use App\Models\RewardRedemption;
use App\Support\Phone;
use App\Support\WhatsApp;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Hikers and their member accounts: list, invite to activate, password links,
 * deactivate / reactivate, and the log of rewards used.
 */
class MemberController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('manageMembers', Hiker::class);

        $search = $request->string('q')->trim()->toString();
        $digits = Phone::searchDigits($search);
        $account = (string) $request->query('account');

        $hikers = Hiker::query()
            ->withPointTotals()
            ->withMax('registrations as last_booked_at', 'created_at')
            ->with('user.roles')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->when($digits !== null, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%"))))
            ->when($account === 'active', fn ($q) => $q->whereHas('user', fn (Builder $u) => $u->where('is_active', true)))
            ->when($account === 'deactivated', fn ($q) => $q->whereHas('user', fn (Builder $u) => $u->where('is_active', false)))
            ->when($account === 'none', fn ($q) => $q->whereNull('user_id'))
            ->orderByDesc('last_booked_at')
            ->orderBy('name')
            ->paginate(config('cms.per_page'))
            ->withQueryString();

        return view('admin.members.index', ['hikers' => $hikers]);
    }

    /**
     * Shows an activation link to send to a hiker who has no account yet.
     */
    public function invite(Hiker $hiker, MemberLinks $links): RedirectResponse
    {
        Gate::authorize('manageMembers', Hiker::class);

        if ($hiker->user_id !== null) {
            return back()->with('error', 'This hiker already has an account: send a password link instead.');
        }

        $url = $links->activation($hiker);

        return back()->with('memberLink', [
            'hiker' => $hiker->name,
            'url' => $url,
            'whatsapp' => WhatsApp::to($hiker->phone, "Hi {$hiker->name}! Activate your ".config('app.name').' account here (valid for '.MemberLinks::INVITE_DAYS." days): {$url}"),
        ]);
    }

    public function passwordLink(Hiker $hiker, MemberLinks $links): RedirectResponse
    {
        Gate::authorize('manageMembers', Hiker::class);

        $user = $hiker->user;
        if ($user === null || ! $user->is_active || $user->isStaff()) {
            return back()->with('error', 'Password links are for active member accounts.');
        }

        $url = $links->password($user);

        return back()->with('memberLink', [
            'hiker' => $hiker->name,
            'url' => $url,
            'whatsapp' => WhatsApp::to($hiker->phone, "Hi {$hiker->name}! Choose a new password for your ".config('app.name').' account here (valid for '.MemberLinks::PASSWORD_HOURS." hours): {$url}"),
        ]);
    }

    public function deactivate(Hiker $hiker, SetAccountActive $action): RedirectResponse
    {
        return $this->setActive($hiker, false, $action, "{$hiker->name}'s account is deactivated.");
    }

    public function reactivate(Hiker $hiker, SetAccountActive $action): RedirectResponse
    {
        return $this->setActive($hiker, true, $action, "{$hiker->name}'s account is active again.");
    }

    /**
     * Every reward used, newest first, optionally for one reward.
     */
    public function rewards(Request $request, RewardCatalog $catalog): View
    {
        Gate::authorize('viewAny', Hiker::class);

        $reward = $catalog->find((string) $request->query('reward'));

        return view('admin.members.rewards', [
            'redemptions' => RewardRedemption::query()
                ->with(['hiker', 'registration.event', 'creator'])
                ->when($reward, fn ($q) => $q->where('reward', $reward?->key))
                ->latest('id')
                ->paginate(config('cms.per_page'))
                ->withQueryString(),
            'catalog' => $catalog,
            'selected' => $reward?->key,
        ]);
    }

    private function setActive(Hiker $hiker, bool $active, SetAccountActive $action, string $message): RedirectResponse
    {
        Gate::authorize('manageMembers', Hiker::class);

        try {
            $action->handle($hiker, $active);
        } catch (MemberException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $message);
    }
}
