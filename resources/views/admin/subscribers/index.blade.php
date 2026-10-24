<x-admin.layout title="Newsletter subscribers">
    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <div class="rounded-lg bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-500">Get announcement emails</p>
            <p class="text-2xl font-semibold">{{ $counts['emails'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-500">Phone numbers (for WhatsApp)</p>
            <p class="text-2xl font-semibold">{{ $counts['phones'] }}</p>
        </div>
        <div class="rounded-lg bg-white p-4 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-500">Email not confirmed yet</p>
            <p class="text-2xl font-semibold">{{ $counts['pending'] }}</p>
        </div>
    </div>

    <div class="mb-4 flex flex-wrap items-end gap-3">
        <form method="GET" class="flex flex-wrap items-end gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Search email or phone" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
            <select name="status" class="rounded-md border border-slate-300 px-3 py-2 text-sm">
                @foreach (\App\Http\Controllers\Admin\SubscriberController::STATUSES as $value => $label)
                    <option value="{{ $value }}" @selected(request('status', '') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-admin.button variant="secondary">Filter</x-admin.button>
        </form>
        <div class="ml-auto">
            <x-admin.button variant="secondary" :href="route('admin.subscribers.export', request()->only('q', 'status'))">Download CSV</x-admin.button>
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Signed up</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($subscribers as $subscriber)
                    <tr>
                        <td class="px-4 py-3">{{ $subscriber->email ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $subscriber->phone ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-admin.badge :classes="match ($subscriber->statusLabel()) {
                                'Confirmed' => 'bg-emerald-100 text-emerald-800',
                                'Awaiting confirmation' => 'bg-amber-100 text-amber-800',
                                'Unsubscribed' => 'bg-slate-200 text-slate-700',
                                default => 'bg-sky-100 text-sky-800',
                            }">{{ $subscriber->statusLabel() }}</x-admin.badge>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $subscriber->created_at->setTimezone(config('site.timezone'))->format('j M Y') }}</td>
                        <td class="px-4 py-3">
                            <div class="flex justify-end gap-3">
                                @if ($subscriber->isActive())
                                    <form method="POST" action="{{ route('admin.subscribers.unsubscribe', $subscriber) }}" onsubmit="return confirm('Unsubscribe them from all announcements?')">
                                        @csrf
                                        <button class="text-xs font-medium text-slate-700 hover:underline">Unsubscribe</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('admin.subscribers.destroy', $subscriber) }}" onsubmit="return confirm('Delete this subscriber for good? Use this for &quot;delete my data&quot; requests.')">
                                    @csrf @method('DELETE')
                                    <button class="text-xs font-medium text-red-600 hover:underline">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">No subscribers found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">{{ $subscribers->links() }}</div>
</x-admin.layout>
