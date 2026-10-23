<x-admin.layout :title="$hiker->name.' · Points'">
    <div class="grid gap-6 lg:grid-cols-3">
        <div class="space-y-6 lg:col-span-2">
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 font-semibold">Points history</h2>
                @if ($transactions->isEmpty())
                    <p class="text-sm text-slate-500">No points yet. Points are added when an organizer marks a booking as attended.</p>
                @else
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-slate-200 text-xs uppercase text-slate-500">
                            <tr><th class="py-2 pr-4">Date</th><th class="py-2 pr-4">What</th><th class="py-2 pr-4 text-right">Points</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach ($transactions as $transaction)
                                <tr class="align-top">
                                    <td class="py-2 pr-4 whitespace-nowrap text-slate-500">{{ $transaction->created_at->setTimezone(config('site.timezone'))->format('M d, Y') }}</td>
                                    <td class="py-2 pr-4">
                                        {{ $transaction->description }}
                                        <span class="block text-xs text-slate-500">
                                            {{ $transaction->type->label() }}{{ $transaction->creator ? ' · by '.$transaction->creator->name : '' }}
                                        </span>
                                    </td>
                                    <td @class(['py-2 text-right font-semibold whitespace-nowrap', 'text-emerald-700' => $transaction->points > 0, 'text-red-600' => $transaction->points < 0])>
                                        {{ $transaction->points > 0 ? '+' : '' }}{{ $transaction->points }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </section>

            <section class="rounded-lg bg-white p-6 shadow-sm">
                <h2 class="mb-4 font-semibold">Bookings</h2>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($bookings as $booking)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                            <span>
                                <a href="{{ route('admin.events.registrations', $booking->event_id) }}" class="hover:underline">{{ $booking->event->title }}</a>
                                <span class="text-slate-500">· {{ $booking->event->starts_on->format('M d, Y') }} · {{ $booking->reference }}</span>
                            </span>
                            <span class="flex gap-1">
                                <x-admin.badge :classes="$booking->status->badgeClasses()">{{ $booking->status->label() }}</x-admin.badge>
                                @if ($booking->attendance)<x-admin.badge :classes="$booking->attendance->badgeClasses()">{{ $booking->attendance->label() }}</x-admin.badge>@endif
                            </span>
                        </li>
                    @endforeach
                </ul>
            </section>
        </div>

        <div class="space-y-6">
            <section class="rounded-lg bg-white p-6 shadow-sm">
                <p class="text-xs uppercase text-slate-500">Total points</p>
                <p class="text-4xl font-bold">{{ $hiker->totalPoints() }}</p>
                <p class="mt-2 flex items-center gap-2 text-sm">
                    <x-admin.badge :classes="$hiker->tier()->badgeClasses()">{{ $hiker->tier()->label() }}</x-admin.badge>
                    <span class="text-slate-500">Rank #{{ $position }}</span>
                </p>
                @if ($hiker->pointsToNextTier() !== null)
                    <p class="mt-1 text-xs text-slate-500">{{ $hiker->pointsToNextTier() }} points to {{ $hiker->tier()->next()?->label() }}</p>
                @endif
                <dl class="mt-4 space-y-1 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Distance hiked</dt><dd>{{ rtrim(rtrim(number_format($hiker->totalDistanceKm(), 1), '0'), '.') }} km</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Phone</dt><dd>{{ $hiker->phone }}</dd></div>
                    @if ($hiker->email)<div class="flex justify-between gap-2"><dt class="text-slate-500">Email</dt><dd class="truncate">{{ $hiker->email }}</dd></div>@endif
                </dl>
            </section>

            @can('adjustPoints', $hiker)
                <form method="POST" action="{{ route('admin.points.adjust', $hiker) }}" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    @csrf
                    <h2 class="font-semibold">Adjust points</h2>
                    <x-admin.input name="points" label="Points" type="number" min="-1000" max="1000" required hint="Negative to remove, e.g. -10." />
                    <x-admin.input name="reason" label="Reason" maxlength="200" required hint="Shown in the history." />
                    <x-admin.button>Add adjustment</x-admin.button>
                </form>
            @endcan
        </div>
    </div>
</x-admin.layout>
