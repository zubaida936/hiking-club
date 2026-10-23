@php
    $seatsLeft = $event->seatsLeft();
    $waitlist = $event->acceptsWaitlist();
    $open = $event->isOpenForRegistration() && ($seatsLeft > 0 || $waitlist);
    $level = $event->difficulty_level;
@endphp

<x-site.layout :title="'Book: '.$event->title" :noindex="true">
    <section class="bg-brand-800 px-4 py-12 text-white sm:px-6">
        <div class="mx-auto max-w-3xl">
            <a href="{{ route('events.show', $event) }}" class="font-display text-sm font-semibold uppercase tracking-wide text-white/80 hover:text-white">&larr; Back to the hike</a>
            <p class="mt-6 font-display text-sm font-semibold uppercase tracking-widest text-accent-400">{{ $event->starts_on->format('l, j F Y') }}</p>
            <h1 class="mt-1 font-display text-3xl font-bold uppercase tracking-wide sm:text-4xl">{{ $waitlist ? 'Join the Waitlist' : $event->registerLabel() }}</h1>
            <p class="mt-2 text-white/85">{{ $event->title }} · {{ $event->location }} · {{ $event->formattedFee() }}</p>
        </div>
    </section>

    <div class="mx-auto max-w-3xl px-4 py-12 sm:px-6">
        @unless ($open)
            <div class="rounded-2xl bg-brand-50 p-8 text-center" role="status">
                <p class="text-lg font-semibold text-brand-900">
                    {{ $event->isOpenForRegistration() ? 'Sorry, this hike is fully booked.' : 'Registration for this hike is closed.' }}
                </p>
                <a href="{{ route('events.index') }}" class="mt-6 inline-block font-display font-semibold uppercase tracking-wide text-brand-600 hover:text-brand-800">See other upcoming hikes &rarr;</a>
            </div>
        @else
            @if (session('error'))
                <p class="mb-8 rounded-xl bg-red-50 px-5 py-4 font-medium text-red-800" role="alert">{{ session('error') }}</p>
            @endif

            <p class="mb-8 text-slate-600">
                @if ($waitlist)
                    <strong class="text-red-600">This hike is fully booked.</strong>
                    Join the waitlist: if a seat opens up, you move up in the order you joined and we let you know right away.
                @else
                    <strong class="text-brand-800">{{ $seatsLeft }} {{ Str::plural('seat', $seatsLeft) }} left.</strong>
                    Bookings close {{ $event->registrationDeadline()->setTimezone(config('site.timezone'))->format('D j M \a\t H:i') }} (Lebanon time).
                @endif
                @if ($event->is_xtreme)
                    This is an X Event: an organizer reviews every registration before confirming the seat.
                @endif
            </p>

            <form method="POST" action="{{ route('events.register.store', $event) }}" class="space-y-10" novalidate>
                @csrf

                <fieldset class="space-y-5">
                    <legend class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">Your details</legend>
                    <x-site.field name="name" label="Full name" required maxlength="120" autocomplete="name" />
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-site.field name="phone" label="Phone (WhatsApp)" type="tel" required placeholder="+961 70 123 456" autocomplete="tel" />
                        <x-site.field name="email" label="Email" type="email" :optional="true" placeholder="you@example.com" autocomplete="email"
                                      hint="We send your confirmation here." />
                    </div>
                </fieldset>

                @if ($event->meetingPoints->isNotEmpty())
                    <fieldset @error('meeting_point_id') aria-describedby="meeting_point_id-help" @enderror>
                        <legend class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">Pickup point</legend>
                        <p class="mt-1 text-sm text-slate-500">Times are Lebanon time. Please be there 5 minutes early.</p>
                        <div class="mt-4 grid gap-2 sm:grid-cols-2">
                            @foreach ($event->meetingPoints as $point)
                                <label class="flex cursor-pointer items-center justify-between gap-3 rounded-xl border border-slate-300 px-4 py-3 has-checked:border-brand-500 has-checked:bg-brand-50">
                                    <span class="flex items-center gap-3">
                                        <input type="radio" name="meeting_point_id" value="{{ $point->id }}" required class="text-brand-600 focus:ring-brand-500"
                                               @checked((string) old('meeting_point_id') === (string) $point->id)>
                                        <span class="text-slate-800">{{ $point->name }}</span>
                                    </span>
                                    <time class="font-display font-bold text-brand-800">{{ $point->pickupTimeShort() }}</time>
                                </label>
                            @endforeach
                        </div>
                        @error('meeting_point_id') <p id="meeting_point_id-help" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </fieldset>
                @endif

                <fieldset class="space-y-5">
                    <legend class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">Emergency contact</legend>
                    <p class="text-sm text-slate-500">Someone we can call if anything happens on the trail. They won't be contacted otherwise.</p>
                    <div class="grid gap-5 sm:grid-cols-2">
                        <x-site.field name="emergency_contact_name" label="Name" required maxlength="120" />
                        <x-site.field name="emergency_contact_phone" label="Phone" type="tel" required placeholder="+961 3 123 456" />
                    </div>
                </fieldset>

                <div>
                    <label for="notes" class="block text-sm font-semibold text-slate-700">Anything we should know? <span class="font-normal text-slate-400">(optional)</span></label>
                    <textarea id="notes" name="notes" rows="3" maxlength="500" placeholder="Health conditions, allergies, first hike with us…"
                              class="mt-1 block w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-slate-800 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-500">{{ old('notes') }}</textarea>
                    @error('notes') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                @if ($event->fee_cents > 0 && \App\Domain\Payments\Enums\PaymentMethod::available() !== [])
                    <fieldset @error('payment_method') aria-describedby="payment_method-help" @enderror>
                        <legend class="font-display text-xl font-bold uppercase tracking-wide text-brand-900">How will you pay {{ $event->formattedFee() }}?</legend>
                        <div class="mt-4 grid gap-2">
                            @foreach (\App\Domain\Payments\Enums\PaymentMethod::available() as $method)
                                <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-300 px-4 py-3 has-checked:border-brand-500 has-checked:bg-brand-50">
                                    <input type="radio" name="payment_method" value="{{ $method->value }}" required class="mt-1 text-brand-600 focus:ring-brand-500"
                                           @checked(old('payment_method') === $method->value)>
                                    <span>
                                        <span class="block font-semibold text-slate-800">{{ $method->label() }}</span>
                                        <span class="block text-sm text-slate-500">
                                            @if ($method === \App\Domain\Payments\Enums\PaymentMethod::Online)
                                                Card or wallet, on the next page once your seat is confirmed.
                                            @elseif ($method->isWalletTransfer())
                                                Send the fee to our {{ $method === \App\Domain\Payments\Enums\PaymentMethod::Whish ? 'Whish Money' : 'OMT Pay' }} account; the number is on the next page.
                                            @else
                                                Pay the organizers at the pickup point.
                                            @endif
                                        </span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                        @error('payment_method') <p id="payment_method-help" class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                    </fieldset>
                @endif

                <x-site.field name="referrer_phone" label="Invited by a friend? Their phone number" type="tel" :optional="true" placeholder="+961 70 123 456"
                              :hint="'They earn '.config('points.referral').' points once you complete your first hike with us.'" />

                <div class="space-y-4">
                    @if ($event->requiresLevelAcknowledgement())
                        <div class="rounded-xl border border-amber-300 bg-amber-50 p-5">
                            <p class="font-semibold text-amber-900">{{ $level->label() }}: check you're ready</p>
                            <p class="mt-1 text-sm text-amber-900">{{ $level->description() }}
                                This hike covers {{ $event->formattedDistance() }} with +{{ $event->elevation_gain_m }} m of climbing (effort score {{ $event->effort_score }}).
                                We recommend having completed a Level {{ $level->value - 1 }} hike recently.</p>
                            <label class="mt-3 flex items-start gap-3 text-sm text-amber-950">
                                <input type="checkbox" name="level_acknowledged" value="1" required class="mt-0.5 rounded border-amber-400 text-brand-600 focus:ring-brand-500" @checked(old('level_acknowledged'))>
                                <span>I meet the requirements for {{ $level->label() }} and I'm fit for this hike.</span>
                            </label>
                            @error('level_acknowledged') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
                        </div>
                    @endif

                    <label class="flex items-start gap-3 text-sm text-slate-700">
                        <input type="checkbox" name="terms" value="1" required class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500" @checked(old('terms'))>
                        <span>{{ config('site.booking.terms') }} See our <a href="{{ route('legal.privacy') }}" class="text-brand-700 underline" target="_blank">privacy policy</a>.</span>
                    </label>
                    @error('terms') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                </div>

                {{-- Honeypot: hidden from people, bots fill it in. --}}
                <div class="hidden" aria-hidden="true">
                    <label for="website">Website</label>
                    <input id="website" name="website" type="text" tabindex="-1" autocomplete="off">
                </div>

                <button type="submit" class="w-full rounded-full bg-accent-500 px-8 py-4 font-display text-lg font-bold uppercase tracking-wide text-white hover:bg-accent-600 sm:w-auto">
                    {{ $waitlist ? 'Join the waitlist' : ($event->is_xtreme ? 'Send my registration' : 'Book my seat') }}
                </button>
            </form>
        @endunless
    </div>
</x-site.layout>
