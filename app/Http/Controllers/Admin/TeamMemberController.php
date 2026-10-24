<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Content\Actions\SaveTeamMember;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\TeamMemberRequest;
use App\Models\TeamMember;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * Guides and organizers shown on the About page.
 */
class TeamMemberController extends Controller
{
    public function index(): View
    {
        Gate::authorize('manage', TeamMember::class);

        return view('admin.team.index', ['members' => TeamMember::orderBy('position')->orderBy('name')->get()]);
    }

    public function create(): View
    {
        Gate::authorize('manage', TeamMember::class);

        return view('admin.team.create', [
            'member' => new TeamMember(['is_active' => true, 'position' => (int) TeamMember::max('position') + 1]),
        ]);
    }

    public function store(TeamMemberRequest $request, SaveTeamMember $action): RedirectResponse
    {
        Gate::authorize('manage', TeamMember::class);

        $action->handle($request->toData());

        return redirect()->route('admin.team.index')->with('success', 'Team member added.');
    }

    public function edit(TeamMember $member): View
    {
        Gate::authorize('manage', TeamMember::class);

        return view('admin.team.edit', ['member' => $member]);
    }

    public function update(TeamMemberRequest $request, TeamMember $member, SaveTeamMember $action): RedirectResponse
    {
        Gate::authorize('manage', TeamMember::class);

        $action->handle($request->toData(), $member);

        return redirect()->route('admin.team.index')->with('success', 'Team member saved.');
    }

    public function destroy(TeamMember $member): RedirectResponse
    {
        Gate::authorize('manage', TeamMember::class);

        $member->delete();

        return redirect()->route('admin.team.index')->with('success', 'Team member removed.');
    }
}
