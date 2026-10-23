@props(['hiker'])

{{-- A hiker's account status with the actions organizers can take. --}}
@php($user = $hiker->user)
<div {{ $attributes->class('text-sm') }}>
    @if ($user === null)
        <x-admin.badge classes="bg-slate-100 text-slate-600">No account</x-admin.badge>
    @elseif ($user->isStaff())
        <x-admin.badge classes="bg-violet-100 text-violet-800">Staff account</x-admin.badge>
    @elseif ($user->is_active)
        <x-admin.badge classes="bg-emerald-100 text-emerald-800">Active</x-admin.badge>
    @else
        <x-admin.badge classes="bg-red-100 text-red-700">Deactivated</x-admin.badge>
    @endif
    @if ($user?->last_login_at)
        <span class="ml-1 text-xs text-slate-500">last login {{ $user->last_login_at->diffForHumans() }}</span>
    @endif

    @can('manageMembers', \App\Models\Hiker::class)
        <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs font-medium">
            @if ($user === null)
                <form method="POST" action="{{ route('admin.members.invite', $hiker) }}">@csrf
                    <button class="text-emerald-700 hover:underline">Invite to activate</button>
                </form>
            @elseif (! $user->isStaff())
                @if ($user->is_active)
                    <form method="POST" action="{{ route('admin.members.password-link', $hiker) }}">@csrf
                        <button class="text-emerald-700 hover:underline">Password link</button>
                    </form>
                    <form method="POST" action="{{ route('admin.members.deactivate', $hiker) }}" onsubmit="return confirm('Deactivate this account? They will be logged out and unable to log in.')">@csrf
                        <button class="text-red-600 hover:underline">Deactivate</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('admin.members.reactivate', $hiker) }}">@csrf
                        <button class="text-emerald-700 hover:underline">Reactivate</button>
                    </form>
                @endif
            @endif
        </div>
    @endcan
</div>
