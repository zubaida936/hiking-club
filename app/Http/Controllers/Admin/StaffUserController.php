<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Identity\Actions\CreateStaffUser;
use App\Domain\Identity\Actions\DeleteStaffUser;
use App\Domain\Identity\Actions\UpdateStaffUser;
use App\Domain\Identity\Enums\Role;
use App\Domain\Identity\Exceptions\StaffUserException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StaffUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class StaffUserController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);

        $role = Role::tryFrom((string) $request->query('role'));

        $users = User::staff()
            ->with('roles')
            ->search($request->string('q')->trim()->toString() ?: null)
            ->when($role, fn ($q) => $q->role($role->value))
            ->orderBy('name')
            ->paginate(config('cms.per_page'))
            ->withQueryString();

        return view('admin.staff.index', [
            'users' => $users,
            'roles' => Role::staff(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.staff.create', ['user' => new User(['is_active' => true]), 'roles' => Role::staff()]);
    }

    public function store(StaffUserRequest $request, CreateStaffUser $action): RedirectResponse
    {
        Gate::authorize('create', User::class);

        $user = $action->handle($request->toData());

        return redirect()->route('admin.staff.index')->with('success', "Staff user {$user->name} created.");
    }

    public function edit(User $user): View
    {
        $this->ensureStaff($user);
        Gate::authorize('update', $user);

        return view('admin.staff.edit', ['user' => $user, 'roles' => Role::staff()]);
    }

    public function update(StaffUserRequest $request, User $user, UpdateStaffUser $action): RedirectResponse
    {
        $this->ensureStaff($user);
        Gate::authorize('update', $user);

        try {
            $action->handle($user, $request->toData(), $request->user());
        } catch (StaffUserException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.staff.index')->with('success', "Staff user {$user->name} updated.");
    }

    public function destroy(Request $request, User $user, DeleteStaffUser $action): RedirectResponse
    {
        $this->ensureStaff($user);
        Gate::authorize('delete', $user);

        try {
            $action->handle($user, $request->user());
        } catch (StaffUserException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.staff.index')->with('success', "Staff user {$user->name} deleted.");
    }

    /**
     * Members are managed in their own module; super admins bypass policies,
     * so the staff boundary is enforced here as well.
     */
    private function ensureStaff(User $user): void
    {
        abort_unless($user->isStaff(), 404);
    }
}
