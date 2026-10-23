@props(['updates'])

{{-- Messages from the organizers about a hike (weather, route, timing), newest first. --}}
@if ($updates->isNotEmpty())
    <section {{ $attributes->class('rounded-2xl border border-accent-400 bg-accent-400/10 p-5') }} aria-labelledby="updates-heading">
        <h2 id="updates-heading" class="font-display text-lg font-bold uppercase tracking-wide text-brand-900">📣 Updates from the organizers</h2>
        <ul class="mt-2 space-y-3">
            @foreach ($updates as $update)
                <li>
                    <p class="whitespace-pre-line text-slate-800">{{ $update->message }}</p>
                    <p class="text-xs text-slate-500">{{ $update->created_at->setTimezone(config('site.timezone'))->format('D j M, H:i') }}</p>
                </li>
            @endforeach
        </ul>
    </section>
@endif
