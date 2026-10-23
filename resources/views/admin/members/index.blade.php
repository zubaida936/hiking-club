<x-admin.layout title="Members">
    <x-admin.member-link />

    <form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
        <input name="q" value="{{ request('q') }}" placeholder="Name, phone or email" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
        <select name="account" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <option value="">All accounts</option>
            <option value="active" @selected(request('account') === 'active')>Active accounts</option>
            <option value="none" @selected(request('account') === 'none')>No account yet</option>
            <option value="deactivated" @selected(request('account') === 'deactivated')>Deactivated</option>
        </select>
        <x-admin.button variant="secondary">Filter</x-admin.button>
    </form>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Hiker</th>
                    <th class="px-4 py-3">Rank</th>
                    <th class="px-4 py-3">Hikes</th>
                    <th class="px-4 py-3">Last booking</th>
                    <th class="px-4 py-3">Account</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($hikers as $hiker)
                    <tr class="align-top">
                        <td class="px-4 py-3">
                            @can('viewAny', \App\Models\Hiker::class)
                                <a href="{{ route('admin.points.show', $hiker) }}" class="font-medium hover:underline">{{ $hiker->name }}</a>
                            @else
                                <span class="font-medium">{{ $hiker->name }}</span>
                            @endcan
                            <a href="https://wa.me/{{ ltrim($hiker->phone, '+') }}" target="_blank" rel="noopener" class="block text-xs text-emerald-700 hover:underline">{{ $hiker->phone }}</a>
                            @if ($hiker->email)<span class="block text-xs text-slate-500">{{ $hiker->email }}</span>@endif
                            @unless ($hiker->show_on_leaderboard)<span class="block text-xs text-slate-400">hidden from leaderboard</span>@endunless
                        </td>
                        <td class="px-4 py-3">
                            <x-admin.badge :classes="$hiker->tier()->badgeClasses()">{{ $hiker->tier()->label() }}</x-admin.badge>
                            <span class="block text-xs text-slate-500">{{ $hiker->totalPoints() }} points</span>
                        </td>
                        <td class="px-4 py-3">{{ $hiker->hikes_completed }}</td>
                        <td class="px-4 py-3 whitespace-nowrap text-slate-500">
                            {{ $hiker->last_booked_at ? \Illuminate\Support\Carbon::parse($hiker->last_booked_at)->setTimezone(config('site.timezone'))->format('M d, Y') : '—' }}
                        </td>
                        <td class="px-4 py-3"><x-admin.member-account :hiker="$hiker" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No members found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $hikers->links() }}</div>

    <p class="mt-4 text-xs text-slate-500">
        A hiker is created with their first booking (one per phone number). "Invite to activate" gives a link to send on WhatsApp
        (valid {{ \App\Domain\Members\MemberLinks::INVITE_DAYS }} days) to choose a password; "Password link" lets a member choose a new
        one (valid {{ \App\Domain\Members\MemberLinks::PASSWORD_HOURS }} hours, works once).
    </p>
</x-admin.layout>
